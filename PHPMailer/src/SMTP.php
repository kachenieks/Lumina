<?php
namespace PHPMailer\PHPMailer;
class SMTP {
    const VERSION = '6.9.1';
    const CRLF = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;
    public $Debugoutput = 'echo';
    public $do_debug = 0;
    public $Timeout = 300;
    public $Timelimit = 300;
    protected $smtp_conn;
    protected $error = ['error'=>'','detail'=>'','smtp_code'=>'','smtp_code_ex'=>''];
    protected $helo_rply = null;
    protected $server_caps = null;
    protected $last_reply = '';

    public function connect($host,$port=null,$timeout=30,$options=[]): bool {
        if($port===null) $port=self::DEFAULT_PORT;
        $this->error=['error'=>'','detail'=>'','smtp_code'=>'','smtp_code_ex'=>''];
        if($this->connected()) $this->close();
        if(empty($host)) { $this->setError('Connect called with an empty hostname'); return false; }
        $socket_context = stream_context_create($options);
        $errno=0; $errstr='';
        $this->smtp_conn = @stream_socket_client($host.':'.$port,$errno,$errstr,$timeout,STREAM_CLIENT_CONNECT,$socket_context);
        if(!is_resource($this->smtp_conn)) { $this->setError('Failed to connect to server',$errstr,$errno); return false; }
        stream_set_timeout($this->smtp_conn,$timeout,0);
        $announce=$this->get_lines();
        $code=substr($announce,0,3);
        if('220'!=$code) { $this->setError('Connection failed. Got: '.$announce); $this->close(); return false; }
        return true;
    }
    public function startTLS(): bool {
        if(!$this->sendCommand('STARTTLS','STARTTLS',220)) return false;
        $crypto_method=STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
        if(defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) $crypto_method|=STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if(defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT')) $crypto_method|=STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        set_error_handler([$this,'errorHandler']);
        $crypto_ok=stream_socket_enable_crypto($this->smtp_conn,true,$crypto_method);
        restore_error_handler();
        return (bool)$crypto_ok;
    }
    public function authenticate($username,$password,$authtype=null,$OAuth=null): bool {
        if(!$this->server_caps) { $this->setError('Authentication is not allowed before HELO/EHLO'); return false; }
        if(array_key_exists('EHLO',$this->server_caps)) {
            if(!array_key_exists('AUTH',$this->server_caps)) {
                $this->setError('Authentication is not allowed at this stage');
                return false;
            }
        }
        if(empty($authtype)) {
            $authtype='LOGIN';
            if(array_key_exists('AUTH',$this->server_caps)) {
                if(is_array($this->server_caps['AUTH'])) {
                    if(in_array('XOAUTH2',$this->server_caps['AUTH'])&&!empty($OAuth)) $authtype='XOAUTH2';
                    elseif(in_array('PLAIN',$this->server_caps['AUTH'])) $authtype='PLAIN';
                    elseif(in_array('LOGIN',$this->server_caps['AUTH'])) $authtype='LOGIN';
                }
            }
        }
        switch($authtype) {
            case 'PLAIN':
                if(!$this->sendCommand('AUTH','AUTH PLAIN '.base64_encode("\0$username\0$password"),235)) return false;
                break;
            case 'LOGIN':
                if(!$this->sendCommand('AUTH','AUTH LOGIN',334)) return false;
                if(!$this->sendCommand('User & Password',base64_encode($username),334)) return false;
                if(!$this->sendCommand('Password',base64_encode($password),235)) return false;
                break;
            case 'XOAUTH2':
                $oauth=base64_encode("user=$username\001auth=Bearer $password\001\001");
                if(!$this->sendCommand('AUTH','AUTH XOAUTH2 '.$oauth,235)) return false;
                break;
            default:
                $this->setError('Authentication method "'.$authtype.'" is not supported');
                return false;
        }
        return true;
    }
    public function connected(): bool { return is_resource($this->smtp_conn) && stream_get_meta_data($this->smtp_conn)['eof']===false; }
    public function close() { $this->server_caps=null; $this->helo_rply=null; if(is_resource($this->smtp_conn)) { fclose($this->smtp_conn); $this->smtp_conn=null; } }
    public function data($msg_data): bool {
        if(!$this->sendCommand('DATA','DATA',354)) return false;
        $lines=explode("\n",str_replace(["\r\n","\r"],"\n",$msg_data));
        foreach($lines as $field) {
            if(strlen($field)>0&&$field[0]==='.') $field='.'.$field;
            $this->client_send($field.self::CRLF);
        }
        return $this->sendCommand('DATA END','.',250);
    }
    public function hello($host=''): bool { return $this->sendHello(array_key_exists('EHLO',$this->server_caps??[])&&$this->server_caps['EHLO']?'EHLO':'HELO',$host); }
    public function mail($from): bool { return $this->sendCommand('MAIL FROM','MAIL FROM:<'.$from.'>',250); }
    public function quit($close_on_error=true): bool {
        $noerror=$this->sendCommand('QUIT','QUIT',221);
        $err=$this->error;
        if($noerror||$close_on_error) $this->close();
        $this->error=$err;
        return $noerror;
    }
    public function recipient($address,$dsn=''): bool { return $this->sendCommand('RCPT TO','RCPT TO:<'.$address.'>',250); }
    public function reset(): bool { return $this->sendCommand('RSET','RSET',250); }
    public function sendCommand($command,$commandstring,$expect): bool {
        if(!$this->connected()) { $this->setError("Called $command without being connected"); return false; }
        $this->client_send($commandstring.self::CRLF,$command);
        $this->last_reply=$this->get_lines();
        $matches=[];
        if(preg_match('/^(\d{3})[ -]/',$this->last_reply,$matches)) {
            $code=(int)$matches[1];
            if(in_array($code,(array)$expect)) return true;
        }
        $this->setError("$command command failed",$this->last_reply);
        return false;
    }
    public function sendHello($hello,$host): bool {
        $noerror=$this->sendCommand($hello,$hello.' '.$host,250);
        $this->helo_rply=$this->last_reply;
        if($noerror) $this->parseHelloFields($hello);
        else $this->server_caps=null;
        return $noerror;
    }
    protected function parseHelloFields($type) {
        $this->server_caps=[];
        $lines=explode("\n",$this->helo_rply);
        foreach($lines as $n=>$s) {
            $s=trim(substr($s,4));
            if(!$s) continue;
            $fields=explode(' ',$s);
            if($fields) {
                if(!$n) { $this->server_caps['HELO']=$s; continue; }
                $name=array_shift($fields);
                $this->server_caps[$name]=$fields?$fields:true;
            }
        }
    }
    public function client_send($data,$verbose='') { return fwrite($this->smtp_conn,$data); }
    public function getError(): array { return $this->error; }
    public function getServerExtList(): ?array { return $this->server_caps; }
    public function getServerExt($name): bool|array|null {
        if(!$this->server_caps) { $this->setError('No HELO/EHLO was sent'); return null; }
        return array_key_exists($name,$this->server_caps) ? $this->server_caps[$name] : null;
    }
    public function getLastReply(): string { return $this->last_reply; }
    protected function get_lines(): string {
        if(!is_resource($this->smtp_conn)) return '';
        $data=''; $endtime=0;
        stream_set_timeout($this->smtp_conn,$this->Timeout);
        if($this->Timelimit>0) $endtime=time()+$this->Timelimit;
        $selR=[$this->smtp_conn]; $selW=null;
        while(is_resource($this->smtp_conn)&&!feof($this->smtp_conn)) {
            set_error_handler([$this,'errorHandler']);
            $n=stream_select($selR,$selW,$selW,floor($this->Timelimit),$this->Timelimit==floor($this->Timelimit)?500000:0);
            restore_error_handler();
            if($n===false) break;
            $str=fgets($this->smtp_conn,515);
            $data.=$str;
            if(isset($str[3])&&$str[3]==' '&&(int)substr($str,0,3)>=100) break;
            if($endtime&&time()>$endtime) break;
        }
        return $data;
    }
    protected function setError($message,$detail='',$smtp_code='',$smtp_code_ex='') {
        $this->error=['error'=>$message,'detail'=>$detail,'smtp_code'=>$smtp_code,'smtp_code_ex'=>$smtp_code_ex];
    }
    public function errorHandler($errno,$errmsg,$errfile='',$errline=0) { $this->setError($errmsg); }
    public function getDebugOutput(): string { return ''; }
    public function setDebugOutput($method='echo') { $this->Debugoutput=$method; }
    public function setDebugLevel($level=0) { $this->do_debug=$level; }
    public function getTimeout(): int { return $this->Timeout; }
    public function setTimeout($timeout=300) { $this->Timeout=$timeout; }
}
