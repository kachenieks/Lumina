<?php
namespace PHPMailer\PHPMailer;

class PHPMailer {
    const VERSION = '6.9.1';
    const ENCRYPTION_SMTPS = 'ssl';
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';
    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CHARSET_UTF8 = 'UTF-8';

    public bool $exceptions = false;
    public string $CharSet = 'utf-8';
    public string $ContentType = 'text/plain';
    public string $Encoding = 'base64';
    public string $ErrorInfo = '';
    public string $From = 'root@localhost';
    public string $FromName = 'Root User';
    public string $Sender = '';
    public string $Subject = '';
    public string $Body = '';
    public string $AltBody = '';
    public string $Host = 'smtp.gmail.com';
    public int $Port = 25;
    public string $Hostname = '';
    public string $Username = '';
    public string $Password = '';
    public string $AuthType = '';
    public bool $SMTPAuth = false;
    public string $SMTPSecure = '';
    public bool $SMTPAutoTLS = true;
    public int $SMTPDebug = 0;
    public bool $SMTPKeepAlive = false;
    public bool $SingleTo = false;
    public bool $do_verp = false;
    public bool $AllowEmpty = false;
    public string $DKIM_domain = '';
    public string $DKIM_private = '';
    public string $DKIM_private_string = '';
    public string $DKIM_selector = '';
    public string $DKIM_passphrase = '';
    public string $DKIM_identity = '';
    public bool $DKIM_copyHeaderFields = true;
    public array $DKIM_extraHeaders = [];
    public int $Priority = 3;
    public string $ConfirmReadingTo = '';
    public string $XMailer = '';
    public bool $isHtml = false;
    public int $Timeout = 300;
    public int $WordWrap = 0;
    public string $Mailer = 'mail';
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected array $ReplyTo = [];
    protected array $RecipientsQueue = [];
    protected array $ReplyToQueue = [];
    protected array $attachment = [];
    protected array $CustomHeader = [];
    protected array $headerLines = [];
    protected string $lastMessageID = '';
    protected string $message_type = '';
    protected array $boundary = [];
    protected string $language = 'en';
    protected int $error_count = 0;
    protected string $sign_cert_file = '';
    protected string $sign_key_file = '';
    protected string $sign_extracerts_file = '';
    protected string $sign_key_pass = '';
    protected bool $exceptions_enabled = false;
    protected SMTP $smtp;
    protected string $MIMEHeader = '';
    protected string $mailHeader = '';
    protected string $MIMEBody = '';
    protected array $server_caps = [];

    public function __construct(bool $exceptions = false) {
        $this->exceptions = $exceptions;
    }

    public function isHTML(bool $isHtml = true): void { $this->isHtml = $isHtml; $this->ContentType = $isHtml ? 'text/html' : 'text/plain'; }
    public function isSMTP(): void { $this->Mailer = 'smtp'; }
    public function isMail(): void { $this->Mailer = 'mail'; }

    public function setFrom(string $address, string $name = '', bool $auto = true): bool {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function addAddress(string $address, string $name = ''): bool {
        $this->to[] = [$address, $name];
        return true;
    }

    public function addCC(string $address, string $name = ''): bool { $this->cc[] = [$address, $name]; return true; }
    public function addBCC(string $address, string $name = ''): bool { $this->bcc[] = [$address, $name]; return true; }
    public function addReplyTo(string $address, string $name = ''): bool { $this->ReplyTo[] = [$address, $name]; return true; }

    public function clearAddresses(): void { $this->to = []; }
    public function clearAllRecipients(): void { $this->to = []; $this->cc = []; $this->bcc = []; }
    public function clearAttachments(): void { $this->attachment = []; }
    public function clearCustomHeaders(): void { $this->CustomHeader = []; }
    public function addCustomHeader(string $name, string $value = null): bool { $this->CustomHeader[] = [$name, $value]; return true; }

    public function send(): bool {
        try {
            if (empty($this->to)) throw new Exception('No recipients');
            return $this->Mailer === 'smtp' ? $this->smtpSend($this->MIMEHeader, $this->MIMEBody) : $this->mailSend($this->MIMEHeader, $this->MIMEBody);
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if ($this->exceptions) throw $e;
            return false;
        }
        return $this->preSend() && $this->postSend();
    }

    public function preSend(): bool {
        try {
            if (empty($this->to) && empty($this->cc) && empty($this->bcc)) throw new Exception('No recipients');
            $this->MIMEHeader = '';
            $this->MIMEBody = $this->createBody();
            $this->MIMEHeader = $this->createHeader();
            return true;
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if ($this->exceptions) throw $e;
            return false;
        }
    }

    public function postSend(): bool {
        try {
            if ($this->Mailer === 'smtp') return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
            if ($this->Mailer === 'sendmail' || $this->Mailer === 'qmail') return $this->sendmailSend($this->MIMEHeader, $this->MIMEBody);
            return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            if ($this->exceptions) throw $e;
            return false;
        }
    }

    protected function mailSend(string $header, string $body): bool {
        $toArr = [];
        foreach ($this->to as $toaddr) $toArr[] = $this->addrFormat($toaddr);
        $to = implode(', ', $toArr);
        $params = null;
        if (!empty($this->Sender) && ini_get('safe_mode') == 0)
            $params = sprintf('-f%s', escapeshellarg($this->Sender));
        if (!empty($this->Sender)) {
            $old_from = ini_get('sendmail_from');
            ini_set('sendmail_from', $this->Sender);
        }
        $result = false;
        if ($params !== null) {
            $result = @mail($to, $this->encodeHeader($this->Subject), $body, $header, $params);
        } else {
            $result = @mail($to, $this->encodeHeader($this->Subject), $body, $header);
        }
        if (isset($old_from)) ini_set('sendmail_from', $old_from);
        if (!$result) throw new Exception('mail() function returned false');
        return true;
    }

    protected function smtpSend(string $header, string $body): bool {
        $bad_rcpt = [];
        if (!$this->smtpConnect()) throw new Exception('SMTP connect() failed.');
        $smtp_from = ($this->Sender === '') ? $this->From : $this->Sender;
        if (!$this->smtp->mail($smtp_from)) {
            $this->setError('SMTP Error: The following From address failed: '.$smtp_from.' : '.implode(',', $this->smtp->getError()));
            throw new Exception($this->ErrorInfo);
        }
        foreach ([$this->to, $this->cc, $this->bcc] as $togroup) {
            foreach ($togroup as $to) {
                if (!$this->smtp->recipient($to[0])) $bad_rcpt[] = $to[0];
            }
        }
        if ((count($bad_rcpt) > 0) && (count($bad_rcpt) == count($this->to) + count($this->cc) + count($this->bcc))) {
            throw new Exception('SMTP Error: The following recipients failed: '.implode(', ', $bad_rcpt));
        }
        if (!$this->smtp->data($header."\r\n".$body)) {
            throw new Exception('SMTP Error: data not accepted: '.$this->smtp->getError()['error']);
        }
        if ($this->SMTPKeepAlive) $this->smtp->reset();
        else $this->smtpClose();
        return true;
    }

    public function smtpConnect(array $options = []): bool {
        if (!isset($this->smtp) || !is_object($this->smtp)) $this->smtp = $this->getSMTPInstance();
        if ($this->smtp->connected()) return true;
        $this->smtp->setDebugLevel($this->SMTPDebug);
        $this->smtp->setDebugOutput($this->Debugoutput ?? 'echo');
        $this->smtp->setDebugOutput($this->SMTPDebug > 0 ? 'echo' : function(){});
        $hosts = explode(';', $this->Host);
        $lastexception = null;
        foreach ($hosts as $hostentry) {
            $hostinfo = [];
            if (!preg_match('/^((ssl|tls):\/\/)*([a-zA-Z0-9\.-]*|\[[a-fA-F0-9:]+\])(:\d+)?$/', trim($hostentry), $hostinfo)) continue;
            $prefix = '';
            $secure = $this->SMTPSecure;
            $tls = (self::ENCRYPTION_STARTTLS === $this->SMTPSecure);
            if ('ssl://' === $hostinfo[2] || ('' === $hostinfo[2] && self::ENCRYPTION_SMTPS === $this->SMTPSecure)) { $prefix = 'ssl://'; $tls = false; $secure = self::ENCRYPTION_SMTPS; }
            if ('tls://' === $hostinfo[2]) { $tls = true; $secure = self::ENCRYPTION_STARTTLS; }
            $sslContext = array_merge(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]], $options);
            $port = $this->Port;
            $hostentry = trim($hostinfo[3]);
            if (!empty($hostinfo[4])) $port = (int)substr($hostinfo[4], 1);
            if ($this->smtp->connect($prefix.$hostentry, $port, $this->Timeout, $sslContext)) {
                try {
                    $hello = ($this->Hostname) ?: $this->serverHostname();
                    $this->smtp->hello($hello);
                    $this->server_caps = $this->smtp->getServerExtList() ?? [];
                    if ($tls) {
                        if (!$this->smtp->startTLS()) throw new Exception('Failed to start TLS encryption.');
                        $this->smtp->hello($hello);
                        $this->server_caps = $this->smtp->getServerExtList() ?? [];
                    }
                    if ($this->SMTPAuth) {
                        if (!$this->smtp->authenticate($this->Username, $this->Password, $this->AuthType, null))
                            throw new Exception('SMTP Error: Could not authenticate.');
                    }
                    return true;
                } catch (Exception $e) { $lastexception = $e; $this->smtp->quit(); continue; }
            }
        }
        $this->smtp->close();
        if ($lastexception !== null) throw $lastexception;
        throw new Exception('SMTP Error: Could not connect to SMTP host.');
    }

    public function smtpClose(): void { if (isset($this->smtp) && $this->smtp instanceof SMTP && $this->smtp->connected()) { $this->smtp->quit(); $this->smtp->close(); } }
    public function getSMTPInstance(): SMTP { return new SMTP(); }

    protected function serverHostname(): string {
        $result = 'localhost.localdomain';
        if (!empty($this->Hostname)) $result = $this->Hostname;
        elseif (isset($_SERVER) && array_key_exists('SERVER_NAME', $_SERVER)) $result = $_SERVER['SERVER_NAME'];
        elseif (function_exists('gethostname') && gethostname() !== false) $result = gethostname();
        return $result;
    }

    protected function createHeader(): string {
        $result = '';
        $result .= $this->headerLine('Date', self::rfcDate());
        if (!empty($this->Sender)) $result .= $this->headerLine('Return-Path', $this->trimQFILENAME($this->Sender));
        if ($this->Mailer !== 'mail') foreach ($this->to as $toaddr) $result .= $this->addrAppend('To', [$toaddr]);
        $result .= $this->addrAppend('From', [[$this->From, $this->FromName]]);
        if (count($this->cc) > 0) $result .= $this->addrAppend('Cc', $this->cc);
        if ($this->Mailer !== 'mail' && count($this->bcc) > 0) $result .= $this->addrAppend('Bcc', $this->bcc);
        if (count($this->ReplyTo) > 0) $result .= $this->addrAppend('Reply-To', $this->ReplyTo);
        if ($this->Mailer !== 'mail') $result .= $this->headerLine('Subject', $this->encodeHeader($this->Subject));
        $result .= $this->headerLine('Message-ID', $this->generateId());
        $result .= $this->headerLine('X-Mailer', 'PHPMailer '.self::VERSION.' (https://github.com/PHPMailer/PHPMailer)');
        if ($this->Priority) $result .= $this->headerLine('X-Priority', (string)$this->Priority);
        $result .= $this->headerLine('MIME-Version', '1.0');
        $result .= $this->headerLine('Content-Type', $this->ContentType.'; charset='.$this->CharSet);
        $result .= $this->headerLine('Content-Transfer-Encoding', $this->Encoding);
        $result .= $this->LE;
        return $result;
    }

    protected function createBody(): string {
        if ($this->isHtml) {
            if ($this->AltBody) {
                $this->ContentType = 'multipart/alternative';
                $boundary = 'b1_'.md5(uniqid(time()));
                return "--$boundary\r\nContent-Type: text/plain; charset=".$this->CharSet."\r\nContent-Transfer-Encoding: ".$this->Encoding."\r\n\r\n".$this->encodeString($this->AltBody, $this->Encoding)."\r\n--$boundary\r\nContent-Type: text/html; charset=".$this->CharSet."\r\nContent-Transfer-Encoding: ".$this->Encoding."\r\n\r\n".$this->encodeString($this->Body, $this->Encoding)."\r\n--$boundary--\r\n";
            }
        }
        return $this->encodeString($this->Body, $this->Encoding);
    }

    public function encodeString(string $str, string $encoding = self::ENCODING_BASE64): string {
        $encoded = '';
        switch (strtolower($encoding)) {
            case self::ENCODING_BASE64: return chunk_split(base64_encode($str), 76, "\r\n");
            case self::ENCODING_QUOTED_PRINTABLE: return quoted_printable_encode($str);
            case '7bit': case '8bit': return $this->fixEOL($str);
            default: return $str;
        }
    }

    public function encodeHeader(string $str, string $position = 'text'): string {
        $matchcount = preg_match_all('/[\x80-\xFF]/', $str, $matches);
        if (0 === $matchcount) return $str;
        return '=?'.$this->CharSet.'?B?'.base64_encode($str).'?=';
    }

    protected function headerLine(string $name, string $value): string { return $name.': '.$value."\r\n"; }
    protected function fixEOL(string $str): string { $nstr = str_replace(["\r\n", "\r", "\n"], "\n", $str); return str_replace("\n", "\r\n", $nstr); }
    protected function addrAppend(string $type, array $addr): string {
        $addresses = [];
        foreach ($addr as $address) $addresses[] = $this->addrFormat($address);
        return $type.': '.implode(', ', $addresses)."\r\n";
    }
    protected function addrFormat(array $addr): string { return empty($addr[1]) ? $addr[0] : '"'.addslashes($addr[1]).'" <'.$addr[0].'>'; }
    protected function generateId(): string { $this->lastMessageID = sprintf('<%s@%s>', base64_encode(random_bytes(12)), $this->serverHostname()); return $this->lastMessageID; }
    protected function trimQFILENAME(string $str): string { return $str; }
    public static function rfcDate(): string { return date('D, j M Y H:i:s O'); }
    protected function setError(string $msg): void { ++$this->error_count; $this->ErrorInfo = $msg; }
    protected $LE = "\r\n";
    protected $Debugoutput = null;

    // Static helpers
    public static function validateAddress(string $address, string $patternselect = 'auto'): bool { return (bool)filter_var($address, FILTER_VALIDATE_EMAIL); }
    public static function idnSupported(): bool { return function_exists('idn_to_ascii'); }
    public static function punyencodeAddress(string $address): string { return $address; }
    public function getLastMessageID(): string { return $this->lastMessageID; }
}
