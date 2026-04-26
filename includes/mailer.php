<?php
/**
 * LUMINA — SMTP e-pasta sūtītājs (bez bibliotēkām)
 */

if (!defined('MAIL_FROM'))      define('MAIL_FROM',      'katrinablazkova06@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Katrīna · LUMINA');
if (!defined('MAIL_ADMIN'))     define('MAIL_ADMIN',     'katrinablazkova06@gmail.com');
if (!defined('SITE_URL'))       define('SITE_URL',       'https://kristovskis.lv/4pt/blazkova/lumina/Lumina');

// Gmail SMTP konfigurācija
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);           // STARTTLS
define('SMTP_USER', 'katrinablazkova06@gmail.com');
define('SMTP_PASS', 'mknh tdre wnbs wovh');  // Gmail App Password

// ── SMTP sūtīšanas funkcija ───────────────────────────────
function luminaMail(string $to, string $toName, string $subject, string $html): bool {
  try {
    $sock = fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 15);
    if (!$sock) {
      // Fallback: try port 587 with STARTTLS
      $sock = fsockopen('smtp.gmail.com', 587, $errno, $errstr, 15);
      if (!$sock) throw new Exception("Connect failed: $errstr ($errno)");
      _smtp($sock, null, '220');           // greeting
      _smtp($sock, 'EHLO kristovskis.lv', '250');
      _smtp($sock, 'STARTTLS', '220');
      stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    } else {
      _smtp($sock, null, '220');           // greeting SSL
    }

    _smtp($sock, 'EHLO kristovskis.lv', '250');
    _smtp($sock, 'AUTH LOGIN', '334');
    _smtp($sock, base64_encode(SMTP_USER), '334');
    _smtp($sock, base64_encode(SMTP_PASS), '235');
    _smtp($sock, 'MAIL FROM:<' . MAIL_FROM . '>', '250');
    _smtp($sock, 'RCPT TO:<' . $to . '>', '250');
    _smtp($sock, 'DATA', '354');

    $boundary = 'lum_' . md5(uniqid());
    $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $from = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';

    $msg  = "From: $from <" . MAIL_FROM . ">\r\n";
    $msg .= "To: =?UTF-8?B?" . base64_encode($toName ?: $to) . "?= <$to>\r\n";
    $msg .= "Subject: $subj\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
    $msg .= "X-Mailer: LUMINA\r\n\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)))) . "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $msg .= chunk_split(base64_encode(emailLayout($subject, $html))) . "\r\n";
    $msg .= "--$boundary--\r\n";
    $msg .= "\r\n.";

    _smtp($sock, $msg, '250');
    _smtp($sock, 'QUIT', '221');
    fclose($sock);
    return true;

  } catch (Exception $e) {
    error_log('LUMINA mail error: ' . $e->getMessage());
    fclose($sock ?? null);
    return false;
  }
}

// Nosūta SMTP komandu un pārbauda atbildi
function _smtp($sock, ?string $cmd, string $expect): string {
  if ($cmd !== null) fwrite($sock, $cmd . "\r\n");
  $resp = '';
  while ($line = fgets($sock, 512)) {
    $resp .= $line;
    if (strlen($line) >= 4 && $line[3] === ' ') break; // last line
  }
  if (!str_starts_with(trim($resp), $expect)) {
    throw new Exception("SMTP expected $expect, got: " . trim($resp));
  }
  return $resp;
}

// ── HTML izkārtojums ─────────────────────────────────────
function emailLayout(string $title, string $content): string {
  return '<!DOCTYPE html><html lang="lv"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . '</title></head>
<body style="margin:0;padding:0;background:#FAF8F4;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:32px 16px;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;">
  <tr><td style="background:#1C1C1C;padding:28px 36px;text-align:center;">
    <p style="font-family:Georgia,serif;font-size:24px;letter-spacing:10px;color:#FAF8F4;margin:0 0 4px;">LUMIN<span style="color:#B8975A;">A</span></p>
    <p style="font-size:9px;letter-spacing:4px;color:rgba(255,255,255,.35);margin:0;font-family:Arial,sans-serif;text-transform:uppercase;">Profesionālā Fotogrāfija</p>
  </td></tr>
  <tr><td style="height:3px;background:#B8975A;"></td></tr>
  <tr><td style="background:#FFFFFF;padding:40px 44px;font-size:14px;line-height:1.7;color:#3D3730;font-family:Arial,sans-serif;">
    ' . $content . '
  </td></tr>
  <tr><td style="background:#1C1C1C;padding:24px 36px;text-align:center;">
    <p style="font-size:11px;color:rgba(255,255,255,.4);line-height:1.9;margin:0;font-family:Arial,sans-serif;">
      <strong style="color:rgba(255,255,255,.65);">LUMINA</strong> · Katrīna Blažkova<br>
      <a href="mailto:' . MAIL_FROM . '" style="color:#B8975A;text-decoration:none;">' . MAIL_FROM . '</a><br>
      <span style="font-size:10px;color:rgba(255,255,255,.2);">Šis e-pasts tika nosūtīts automātiski.</span>
    </p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';
}

// Palīgfunkcijas
function emailBtn(string $url, string $text): string {
  return '<p style="text-align:center;margin:28px 0 8px;">
    <a href="' . $url . '" style="background:#B8975A;color:#ffffff;padding:13px 34px;text-decoration:none;
    font-size:12px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;
    display:inline-block;">' . $text . ' &rarr;</a></p>';
}

function emailRow(string $label, string $value, bool $alt = false): string {
  $bg = $alt ? '#FAF8F4' : '#FFFFFF';
  return '<tr>
    <td style="background:' . $bg . ';padding:11px 16px;font-size:10px;letter-spacing:2px;
      text-transform:uppercase;color:#B8975A;width:130px;font-family:Arial,sans-serif;">' . $label . '</td>
    <td style="background:' . $bg . ';padding:11px 16px;font-size:15px;color:#1C1C1C;">' . $value . '</td>
  </tr>';
}

function emailDivider(): string {
  return '<tr><td colspan="2" style="height:1px;background:#F3EEE6;"></td></tr>';
}

// ══════════════════════════════════════════════════════════
// E-PASTA ŠABLONI
// ══════════════════════════════════════════════════════════

function mailRegistracija(string $epasts, string $vards): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:28px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Laipni lūgti, ' . htmlspecialchars($vards) . '!</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <p style="color:#7A7267;margin:0 0 20px;">Jūsu LUMINA konts ir veiksmīgi izveidots. Tagad varat:</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
      <tr><td style="padding:10px 0;border-bottom:1px solid #F3EEE6;font-size:14px;color:#3D3730;"><span style="color:#B8975A;">✦</span>&nbsp; Rezervēt fotosesijas</td></tr>
      <tr><td style="padding:10px 0;border-bottom:1px solid #F3EEE6;font-size:14px;color:#3D3730;"><span style="color:#B8975A;">✦</span>&nbsp; Skatīt savas privātās galerijas</td></tr>
      <tr><td style="padding:10px 0;font-size:14px;color:#3D3730;"><span style="color:#B8975A;">✦</span>&nbsp; Pasūtīt foto druku un canvas darbus</td></tr>
    </table>
    ' . emailBtn(SITE_URL . '/profils.php', 'Mans profils') . '
    <p style="font-size:12px;color:#AAA49C;margin-top:28px;padding-top:20px;border-top:1px solid #F3EEE6;">Ja neesat reģistrējušies, ignorējiet šo ziņu.</p>';
  return luminaMail($epasts, $vards, 'Laipni lūgti LUMINA', $html);
}

function mailRezervacijaKlients(string $epasts, string $vards, array $rez): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:28px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Rezervācija saņemta ✓</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <p style="color:#7A7267;margin:0 0 24px;">Paldies, <strong>' . htmlspecialchars($vards) . '</strong>! Sazināsimies 24 stundu laikā.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:28px;">
      ' . emailRow('Pakalpojums', htmlspecialchars($rez['pakalpojums']), true) . emailDivider() .
      emailRow('Datums', date('d.m.Y', strtotime($rez['datums'])) . ' · ' . substr($rez['laiks'], 0, 5)) .
      ($rez['vieta'] ? emailDivider() . emailRow('Vieta', htmlspecialchars($rez['vieta']), true) : '') .
      ($rez['cena'] ? emailDivider() . emailRow('Cena', '<span style="font-family:Georgia,serif;font-size:22px;color:#B8975A;">no &euro;' . number_format($rez['cena'], 0) . '</span>') : '') . '
    </table>
    ' . emailBtn(SITE_URL . '/profils.php?tab=rezervacijas', 'Skatīt rezervāciju');
  return luminaMail($epasts, $vards, 'Rezervācija saņemta — LUMINA', $html);
}

function mailRezervacijaAdmin(array $rez, string $vards, string $email, string $talrunis = ''): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:24px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Jauna rezervācija</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:28px;">
      ' . emailRow('Klients', '<strong>' . htmlspecialchars($vards) . '</strong>', true) . emailDivider() .
      emailRow('E-pasts', '<a href="mailto:' . htmlspecialchars($email) . '" style="color:#B8975A;">' . htmlspecialchars($email) . '</a>') .
      ($talrunis ? emailDivider() . emailRow('Tālrunis', htmlspecialchars($talrunis), true) : '') . emailDivider() .
      emailRow('Pakalpojums', htmlspecialchars($rez['pakalpojums']), !$talrunis) . emailDivider() .
      emailRow('Datums', date('d.m.Y', strtotime($rez['datums'])) . ' · ' . substr($rez['laiks'], 0, 5)) .
      ($rez['vieta'] ? emailDivider() . emailRow('Vieta', htmlspecialchars($rez['vieta']), true) : '') .
      ($rez['papildu_info'] ? emailDivider() . emailRow('Piezīmes', '<em>' . htmlspecialchars($rez['papildu_info']) . '</em>') : '') . '
    </table>
    ' . emailBtn(SITE_URL . '/admin/rezervacijas.php', 'Apstiprināt');
  return luminaMail(MAIL_ADMIN, 'Admin', 'Jauna rezervācija — ' . $vards, $html);
}

function mailRezervacijaStatuss(string $epasts, string $vards, array $rez, string $statuss): bool {
  $info = [
    'apstiprinats' => ['Rezervācija apstiprināta', '#27AE60', 'Jūsu rezervācija ir apstiprināta! Saglabājiet datumu savā kalendārā.'],
    'pabeigts'     => ['Sesija pabeigta', '#2980B9', 'Paldies, ka izvēlējāties LUMINA! Fotogrāfijas nosūtīsim drīzumā.'],
    'atcelts'      => ['Rezervācija atcelta', '#C0392B', 'Jūsu rezervācija ir atcelta. Sazinieties ar mums, lai pārplānotu.'],
  ];
  $i = $info[$statuss] ?? ['Statuss mainīts', '#B8975A', 'Jūsu rezervācijas statuss ir atjaunināts.'];
  $html = '
    <div style="text-align:center;padding:16px;background:' . $i[1] . '14;border:1px solid ' . $i[1] . '44;margin-bottom:28px;">
      <strong style="font-size:16px;color:' . $i[1] . ';">' . $i[0] . '</strong>
    </div>
    <p style="color:#7A7267;margin:0 0 24px;">' . htmlspecialchars($vards) . ', ' . $i[2] . '</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:28px;">
      ' . emailRow('Pakalpojums', htmlspecialchars($rez['pakalpojums']), true) . emailDivider() .
      emailRow('Datums', date('d.m.Y', strtotime($rez['datums'])) . ' · ' . substr($rez['laiks'], 0, 5)) . '
    </table>
    ' . emailBtn(SITE_URL . '/profils.php?tab=rezervacijas', 'Mans profils');
  return luminaMail($epasts, $vards, $i[0] . ' — LUMINA', $html);
}

function mailPasutijumsKlients(string $epasts, string $vards, array $items, float $total, string $orderId): bool {
  $rows = '';
  foreach ($items as $item) {
    $rows .= '<tr>
      <td style="padding:11px 14px;border-bottom:1px solid #F3EEE6;font-size:14px;color:#3D3730;">' . htmlspecialchars($item['name']) . '</td>
      <td style="padding:11px 14px;border-bottom:1px solid #F3EEE6;text-align:center;color:#AAA49C;font-size:13px;">&times;' . (int)$item['qty'] . '</td>
      <td style="padding:11px 14px;border-bottom:1px solid #F3EEE6;text-align:right;color:#B8975A;font-family:Georgia,serif;font-size:16px;">&euro;' . number_format($item['cena'] * $item['qty'], 2) . '</td>
    </tr>';
  }
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:28px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Pasūtījums apstiprināts ✓</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <p style="color:#7A7267;margin:0 0 6px;">Paldies, <strong>' . htmlspecialchars($vards) . '</strong>!</p>
    <p style="font-size:12px;color:#AAA49C;margin:0 0 24px;">Pasūtījuma Nr: <strong style="color:#B8975A;">#' . htmlspecialchars($orderId) . '</strong></p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:24px;">
      <tr style="background:#1C1C1C;">
        <td style="padding:10px 14px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#B8975A;font-family:Arial,sans-serif;">Produkts</td>
        <td style="padding:10px 14px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#B8975A;text-align:center;font-family:Arial,sans-serif;">Gb.</td>
        <td style="padding:10px 14px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#B8975A;text-align:right;font-family:Arial,sans-serif;">Cena</td>
      </tr>
      ' . $rows . '
      <tr>
        <td colspan="2" style="padding:14px;text-align:right;font-size:11px;color:#7A7267;letter-spacing:1px;text-transform:uppercase;font-family:Arial,sans-serif;">Kopā:</td>
        <td style="padding:14px;text-align:right;font-family:Georgia,serif;font-size:26px;color:#B8975A;">&euro;' . number_format($total, 2) . '</td>
      </tr>
    </table>
    ' . emailBtn(SITE_URL . '/profils.php?tab=pasutijumi', 'Skatīt pasūtījumu');
  return luminaMail($epasts, $vards, 'Pasūtījums apstiprināts #' . $orderId . ' — LUMINA', $html);
}

function mailPasutijumsAdmin(string $vards, string $email, array $items, float $total, string $orderId): bool {
  $rows = '';
  foreach ($items as $item) {
    $rows .= '<tr>
      <td style="padding:9px 12px;border-bottom:1px solid #F3EEE6;font-size:13px;">' . htmlspecialchars($item['name']) . '</td>
      <td style="padding:9px 12px;border-bottom:1px solid #F3EEE6;text-align:center;color:#AAA49C;">&times;' . (int)$item['qty'] . '</td>
      <td style="padding:9px 12px;border-bottom:1px solid #F3EEE6;text-align:right;color:#B8975A;">&euro;' . number_format($item['cena'] * $item['qty'], 2) . '</td>
    </tr>';
  }
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:22px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Jauns pasūtījums #' . htmlspecialchars($orderId) . '</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:20px;">
      ' . emailRow('Klients', '<strong>' . htmlspecialchars($vards) . '</strong>', true) . emailDivider() .
      emailRow('E-pasts', '<a href="mailto:' . htmlspecialchars($email) . '" style="color:#B8975A;">' . htmlspecialchars($email) . '</a>') . emailDivider() .
      emailRow('Summa', '<span style="font-family:Georgia,serif;font-size:22px;color:#B8975A;">&euro;' . number_format($total, 2) . '</span>', true) . '
    </table>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:24px;">
      <tr style="background:#1C1C1C;">
        <td style="padding:9px 12px;font-size:10px;letter-spacing:2px;color:#B8975A;text-transform:uppercase;font-family:Arial,sans-serif;">Produkts</td>
        <td style="padding:9px 12px;text-align:center;font-size:10px;color:#B8975A;font-family:Arial,sans-serif;">Gb.</td>
        <td style="padding:9px 12px;text-align:right;font-size:10px;color:#B8975A;font-family:Arial,sans-serif;">Cena</td>
      </tr>' . $rows . '
    </table>
    ' . emailBtn(SITE_URL . '/admin/index.php', 'Admin panelis');
  return luminaMail(MAIL_ADMIN, 'Admin', 'Jauns pasūtījums #' . $orderId . ' — EUR ' . number_format($total, 2), $html);
}

function mailFotoPasutijumsKlients(string $epasts, string $vards, string $produkts, string $notes): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:28px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Foto pasūtījums saņemts ✓</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <p style="color:#7A7267;margin:0 0 24px;">Paldies, <strong>' . htmlspecialchars($vards) . '</strong>!</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:24px;">
      ' . emailRow('Produkts', htmlspecialchars($produkts), true) .
      ($notes ? emailDivider() . emailRow('Piezīmes', '<em>' . htmlspecialchars($notes) . '</em>') : '') . '
    </table>
    <p style="font-size:13px;color:#7A7267;background:#FAF8F4;padding:13px 16px;border-left:3px solid #B8975A;margin-bottom:24px;">Sazināsimies ar jums 24 stundu laikā, lai apstiprinātu detaļas.</p>
    ' . emailBtn(SITE_URL . '/profils.php?tab=pasutijumi', 'Mans profils');
  return luminaMail($epasts, $vards, 'Foto pasūtījums saņemts — LUMINA', $html);
}

function mailFotoPasutijumsAdmin(string $vards, string $email, string $produkts, string $notes, string $foto = ''): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:22px;font-weight:400;color:#1C1C1C;margin:0 0 6px;">Jauns foto pasūtījums</h2>
    <div style="width:40px;height:2px;background:#B8975A;margin:12px 0 20px;"></div>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E8E3DB;margin-bottom:24px;">
      ' . emailRow('Klients', '<strong>' . htmlspecialchars($vards) . '</strong>', true) . emailDivider() .
      emailRow('E-pasts', '<a href="mailto:' . htmlspecialchars($email) . '" style="color:#B8975A;">' . htmlspecialchars($email ?: '—') . '</a>') . emailDivider() .
      emailRow('Produkts', htmlspecialchars($produkts), true) .
      ($notes ? emailDivider() . emailRow('Piezīmes', '<em>' . htmlspecialchars($notes) . '</em>') : '') .
      ($foto ? emailDivider() . emailRow('Foto', '<span style="font-size:12px;color:#7A7267;">' . htmlspecialchars($foto) . '</span>', true) : '') . '
    </table>
    ' . emailBtn(SITE_URL . '/admin/pasutijumi.php', 'Skatīt pasūtījumu');
  return luminaMail(MAIL_ADMIN, 'Admin', 'Jauns foto pasutijums — ' . $vards, $html);
}

// ── Galerija — viesa piekļuves kods ──────────────────────────────
function mailGalerijaViesis(string $epasts, string $vards, string $galNosaukums, string $kods): bool {
  $html = emailHeader('Jūsu galerija ir gatava! 📸')
    . emailGreeting($vards)
    . '<p style="color:#555;line-height:1.7;margin:0 0 20px;">Jūsu fotosesija ir pabeigta un galerija ir gatava apskatei. Izmantojiet zemāk norādīto piekļuves kodu, lai skatītu savas fotogrāfijas.</p>'
    . '<div style="text-align:center;margin:28px 0;">'
    . '<div style="display:inline-block;background:#f8f6f1;border:2px solid #B8975A;padding:16px 32px;border-radius:4px;">'
    . '<div style="font-size:11px;color:#888;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;">Piekļuves kods</div>'
    . '<div style="font-size:32px;font-weight:700;color:#1a1a1a;letter-spacing:6px;font-family:monospace;">' . htmlspecialchars($kods) . '</div>'
    . '</div></div>'
    . '<p style="color:#555;line-height:1.7;margin:0 0 20px;text-align:center;">Galerija: <strong>' . htmlspecialchars($galNosaukums) . '</strong></p>'
    . emailBtn(SITE_URL . '/galerija-viesis.php', 'Skatīt galeriju →')
    . '<p style="color:#999;font-size:11px;text-align:center;margin-top:20px;">Ja nākotnē izveidosiet kontu ar šo e-pasta adresi, galerija tiks automātiski piesaistīta jūsu profilam.</p>';
  return luminaMail($epasts, $vards, 'Jūsu LUMINA galerija ir gatava — ' . $galNosaukums, $html);
}
