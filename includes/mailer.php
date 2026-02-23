<?php
/**
 * LUMINA — Centralizēts e-pasta sūtītājs (PHPMailer)
 * 
 * Izmantošana:
 *   require_once __DIR__ . '/mailer.php';
 *   luminaMail('adrese@epasts.lv', 'Vārds', 'Tēma', '<p>Saturs</p>');
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer ceļš — jābūt augšupielādētam serverī
$pmBase = __DIR__ . '/../PHPMailer/src/';
require_once $pmBase . 'Exception.php';
require_once $pmBase . 'PHPMailer.php';
require_once $pmBase . 'SMTP.php';

// ── Konfigurācija ──────────────────────────────────────────
define('MAIL_FROM',     'katrinablazkova06@gmail.com');
define('MAIL_FROM_NAME','LUMINA Fotogrāfija');
define('MAIL_ADMIN',    'katrinablazkova06@gmail.com');
define('MAIL_SMTP_HOST','smtp.gmail.com');
define('MAIL_SMTP_USER','katrinablazkova06@gmail.com');
define('MAIL_SMTP_PASS','mknh tdre wnbs wovh');       // Gmail App Password
define('MAIL_SMTP_PORT', 465);
define('SITE_URL',       'https://kristovskis.lv/4pt/blazkova/lumina/Lumina');
// ───────────────────────────────────────────────────────────

/**
 * Galvenā sūtīšanas funkcija
 */
function luminaMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
  try {
    $mail = new PHPMailer(true);
    $mail->CharSet   = 'UTF-8';
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host       = MAIL_SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_SMTP_USER;
    $mail->Password   = MAIL_SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = MAIL_SMTP_PORT;

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);
    $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = emailLayout($subject, $htmlBody);
    $mail->AltBody = strip_tags($htmlBody);

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log('LUMINA mailer error: ' . $e->getMessage());
    return false;
  }
}

/**
 * HTML e-pasta izkārtojums ar LUMINA dizainu
 */
function emailLayout(string $title, string $content): string {
  return '<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin:0;padding:0;background:#FAF8F4;font-family:Georgia,serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:#1C1C1C;padding:32px 40px;text-align:center;">
      <div style="font-family:Georgia,serif;font-size:26px;letter-spacing:10px;color:#FAF8F4;">
        LUMIN<span style="color:#B8975A">A</span>
      </div>
      <div style="font-size:10px;letter-spacing:4px;color:rgba(255,255,255,.4);margin-top:6px;text-transform:uppercase;font-family:Arial,sans-serif;">
        Profesionālā Fotogrāfija
      </div>
    </td>
  </tr>

  <!-- Thin gold line -->
  <tr><td style="height:3px;background:linear-gradient(90deg,#B8975A,#d4af72,#B8975A);"></td></tr>

  <!-- Content -->
  <tr>
    <td style="background:#ffffff;padding:44px 48px;color:#1C1C1C;font-family:Arial,sans-serif;font-size:15px;line-height:1.7;">
      ' . $content . '
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#1C1C1C;padding:28px 40px;text-align:center;">
      <div style="font-size:11px;color:rgba(255,255,255,.4);font-family:Arial,sans-serif;line-height:1.8;">
        LUMINA Fotogrāfija · <a href="' . SITE_URL . '" style="color:#B8975A;text-decoration:none;">lumina.lv</a><br>
        E-pasts: <a href="mailto:' . MAIL_FROM . '" style="color:#B8975A;text-decoration:none;">' . MAIL_FROM . '</a><br>
        <span style="color:rgba(255,255,255,.25);font-size:10px;">Šis e-pasts tika nosūtīts automātiski. Lūdzu neatbildiet uz to.</span>
      </div>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>';
}

// ══════════════════════════════════════════════════════
// GATAVI E-PASTA ŠABLONI
// ══════════════════════════════════════════════════════

/**
 * 1. Reģistrācijas apstiprināšana
 */
function mailRegistracija(string $epasts, string $vards): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1C1C1C;margin:0 0 20px;">
      Laipni lūgti, ' . htmlspecialchars($vards) . '! ✦
    </h2>
    <p style="color:#666;">Jūsu konts LUMINA ir veiksmīgi izveidots. Tagad varat:</p>
    <table width="100%" cellpadding="12" style="margin:20px 0;">
      <tr><td style="background:#FAF8F4;border-left:3px solid #B8975A;padding:12px 16px;margin-bottom:8px;font-size:14px;">
        📅 Rezervēt fotosesijas
      </td></tr>
      <tr><td style="height:6px;"></td></tr>
      <tr><td style="background:#FAF8F4;border-left:3px solid #B8975A;padding:12px 16px;font-size:14px;">
        🖼️ Skatīt savas privātās galerijas pēc sesijām
      </td></tr>
      <tr><td style="height:6px;"></td></tr>
      <tr><td style="background:#FAF8F4;border-left:3px solid #B8975A;padding:12px 16px;font-size:14px;">
        🛍️ Pasūtīt foto druku un canvas mākslas darbus
      </td></tr>
    </table>
    <div style="text-align:center;margin:32px 0;">
      <a href="' . SITE_URL . '/profils.php" style="background:#B8975A;color:#fff;padding:14px 36px;text-decoration:none;font-size:13px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;">
        Mans profils →
      </a>
    </div>
    <p style="color:#999;font-size:13px;border-top:1px solid #eee;padding-top:20px;margin-top:20px;">
      Ja neesat reģistrējušies, ignorējiet šo e-pastu.
    </p>';
  return luminaMail($epasts, $vards, 'Laipni lūgti LUMINA ✦', $html);
}

/**
 * 2. Rezervācijas apstiprinājums — klientam
 */
function mailRezervacijaKlients(string $epasts, string $vards, array $rez): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1C1C1C;margin:0 0 8px;">
      Rezervācija saņemta ✓
    </h2>
    <p style="color:#666;margin-bottom:28px;">Paldies, ' . htmlspecialchars($vards) . '! Esam saņēmuši jūsu pieteikumu un sazināsimies 24 stundu laikā.</p>

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;border:1px solid #e8e3db;margin-bottom:28px;">
      <tr><td style="padding:20px 24px;border-bottom:1px solid #e8e3db;">
        <div style="font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#B8975A;font-family:Arial,sans-serif;">Pakalpojums</div>
        <div style="font-size:18px;color:#1C1C1C;margin-top:4px;">' . htmlspecialchars($rez['pakalpojums']) . '</div>
      </td></tr>
      <tr><td style="padding:20px 24px;border-bottom:1px solid #e8e3db;">
        <div style="font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#B8975A;font-family:Arial,sans-serif;">Datums & Laiks</div>
        <div style="font-size:18px;color:#1C1C1C;margin-top:4px;">' . date('d.m.Y', strtotime($rez['datums'])) . ' · ' . substr($rez['laiks'], 0, 5) . '</div>
      </td></tr>
      ' . ($rez['vieta'] ? '<tr><td style="padding:20px 24px;border-bottom:1px solid #e8e3db;">
        <div style="font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#B8975A;font-family:Arial,sans-serif;">Vieta</div>
        <div style="font-size:18px;color:#1C1C1C;margin-top:4px;">' . htmlspecialchars($rez['vieta']) . '</div>
      </td></tr>' : '') . '
      ' . ($rez['cena'] ? '<tr><td style="padding:20px 24px;">
        <div style="font-size:10px;letter-spacing:3px;text-transform:uppercase;color:#B8975A;font-family:Arial,sans-serif;">Orientējošā cena</div>
        <div style="font-family:Georgia,serif;font-size:28px;color:#B8975A;margin-top:4px;">no €' . number_format($rez['cena'], 0) . '</div>
      </td></tr>' : '') . '
    </table>

    <div style="text-align:center;">
      <a href="' . SITE_URL . '/profils.php?tab=rezervacijas" style="background:#B8975A;color:#fff;padding:14px 36px;text-decoration:none;font-size:13px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;">
        Skatīt rezervāciju →
      </a>
    </div>';
  return luminaMail($epasts, $vards, 'Rezervācija saņemta — LUMINA', $html);
}

/**
 * 3. Rezervācijas paziņojums — adminam
 */
function mailRezervacijaAdmin(array $rez, string $klientaVards, string $klientaEmail, string $talrunis = ''): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:22px;font-weight:400;color:#1C1C1C;margin:0 0 20px;">
      🔔 Jauna rezervācija
    </h2>
    <table width="100%" style="border-collapse:collapse;font-size:14px;font-family:Arial,sans-serif;">
      <tr style="background:#FAF8F4;">
        <td style="padding:10px 14px;color:#888;width:140px;">Klients</td>
        <td style="padding:10px 14px;font-weight:bold;">' . htmlspecialchars($klientaVards) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 14px;color:#888;">E-pasts</td>
        <td style="padding:10px 14px;"><a href="mailto:' . htmlspecialchars($klientaEmail) . '" style="color:#B8975A;">' . htmlspecialchars($klientaEmail) . '</a></td>
      </tr>
      ' . ($talrunis ? '<tr style="background:#FAF8F4;"><td style="padding:10px 14px;color:#888;">Tālrunis</td><td style="padding:10px 14px;">' . htmlspecialchars($talrunis) . '</td></tr>' : '') . '
      <tr style="background:#FAF8F4;">
        <td style="padding:10px 14px;color:#888;">Pakalpojums</td>
        <td style="padding:10px 14px;">' . htmlspecialchars($rez['pakalpojums']) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 14px;color:#888;">Datums</td>
        <td style="padding:10px 14px;">' . date('d.m.Y', strtotime($rez['datums'])) . ' plkst. ' . substr($rez['laiks'], 0, 5) . '</td>
      </tr>
      <tr style="background:#FAF8F4;">
        <td style="padding:10px 14px;color:#888;">Vieta</td>
        <td style="padding:10px 14px;">' . htmlspecialchars($rez['vieta'] ?: '—') . '</td>
      </tr>
      ' . ($rez['papildu_info'] ? '<tr><td style="padding:10px 14px;color:#888;">Piezīmes</td><td style="padding:10px 14px;font-style:italic;">' . htmlspecialchars($rez['papildu_info']) . '</td></tr>' : '') . '
    </table>
    <div style="text-align:center;margin-top:28px;">
      <a href="' . SITE_URL . '/admin/rezervacijas.php" style="background:#1C1C1C;color:#B8975A;padding:12px 32px;text-decoration:none;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;">
        Apstiprināt admin panelī →
      </a>
    </div>';
  return luminaMail(MAIL_ADMIN, 'Admin', '🔔 Jauna rezervācija — ' . $klientaVards, $html);
}

/**
 * 4. Rezervācijas statusa maiņa — klientam
 */
function mailRezervacijaStatuss(string $epasts, string $vards, array $rez, string $jaunaisStatuss): bool {
  $statusInfo = [
    'apstiprinats' => ['✓ Apstiprināta', '#27ae60', 'Jūsu rezervācija ir apstiprināta. Lūdzu saglabājiet šo datumu savā kalendārā!'],
    'pabeigts'     => ['✔ Pabeigta', '#2980b9', 'Paldies, ka izvēlējāties LUMINA! Jūsu sesija ir pabeigta. Fotogrāfijas nosūtīsim drīzumā.'],
    'atcelts'      => ['✕ Atcelta', '#c0392b', 'Diemžēl jūsu rezervācija ir atcelta. Sazinieties ar mums, lai pārplānotu.'],
  ];
  $si = $statusInfo[$jaunaisStatuss] ?? ['Atjaunināta', '#888', 'Jūsu rezervācijas statuss ir mainīts.'];
  $html = '
    <div style="text-align:center;margin-bottom:28px;">
      <div style="display:inline-block;background:' . $si[1] . '18;border:1px solid ' . $si[1] . ';padding:10px 24px;border-radius:4px;font-size:16px;color:' . $si[1] . ';font-family:Arial,sans-serif;">
        ' . $si[0] . '
      </div>
    </div>
    <p style="color:#555;">' . htmlspecialchars($vards) . ', ' . $si[2] . '</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;border:1px solid #e8e3db;margin:20px 0;">
      <tr><td style="padding:16px 20px;border-bottom:1px solid #e8e3db;">
        <span style="font-size:11px;color:#B8975A;text-transform:uppercase;letter-spacing:2px;">Pakalpojums</span><br>
        <strong>' . htmlspecialchars($rez['pakalpojums']) . '</strong>
      </td></tr>
      <tr><td style="padding:16px 20px;">
        <span style="font-size:11px;color:#B8975A;text-transform:uppercase;letter-spacing:2px;">Datums</span><br>
        <strong>' . date('d.m.Y', strtotime($rez['datums'])) . ' · ' . substr($rez['laiks'], 0, 5) . '</strong>
      </td></tr>
    </table>
    <div style="text-align:center;">
      <a href="' . SITE_URL . '/profils.php?tab=rezervacijas" style="background:#B8975A;color:#fff;padding:12px 30px;text-decoration:none;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;">
        Mans profils →
      </a>
    </div>';
  return luminaMail($epasts, $vards, 'Rezervācija ' . $si[0] . ' — LUMINA', $html);
}

/**
 * 5. Veikala pasūtījums (Stripe) — klientam
 */
function mailPasutijumsKlients(string $epasts, string $vards, array $items, float $total, string $orderId): bool {
  $rows = '';
  foreach ($items as $item) {
    $rows .= '<tr>
      <td style="padding:12px 16px;border-bottom:1px solid #f0ebe3;font-size:14px;">' . htmlspecialchars($item['name']) . '</td>
      <td style="padding:12px 16px;border-bottom:1px solid #f0ebe3;text-align:center;font-size:14px;color:#888;">× ' . (int)$item['qty'] . '</td>
      <td style="padding:12px 16px;border-bottom:1px solid #f0ebe3;text-align:right;font-size:14px;color:#B8975A;font-weight:bold;">€' . number_format($item['cena'] * $item['qty'], 2) . '</td>
    </tr>';
  }
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1C1C1C;margin:0 0 8px;">Pasūtījums apstiprināts ✓</h2>
    <p style="color:#666;margin-bottom:28px;">Paldies, ' . htmlspecialchars($vards) . '! Maksājums saņemts. Pasūtījuma Nr: <strong>#' . htmlspecialchars($orderId) . '</strong></p>

    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e8e3db;margin-bottom:20px;">
      <thead>
        <tr style="background:#1C1C1C;">
          <th style="padding:12px 16px;text-align:left;font-size:11px;letter-spacing:2px;color:#B8975A;font-weight:400;text-transform:uppercase;">Produkts</th>
          <th style="padding:12px 16px;text-align:center;font-size:11px;letter-spacing:2px;color:#B8975A;font-weight:400;text-transform:uppercase;">Daudzums</th>
          <th style="padding:12px 16px;text-align:right;font-size:11px;letter-spacing:2px;color:#B8975A;font-weight:400;text-transform:uppercase;">Cena</th>
        </tr>
      </thead>
      <tbody>' . $rows . '</tbody>
      <tfoot>
        <tr>
          <td colspan="2" style="padding:16px;text-align:right;font-size:13px;color:#888;">Kopā:</td>
          <td style="padding:16px;text-align:right;font-family:Georgia,serif;font-size:24px;color:#B8975A;font-weight:bold;">€' . number_format($total, 2) . '</td>
        </tr>
      </tfoot>
    </table>

    <p style="font-size:13px;color:#888;background:#FAF8F4;padding:14px;border-left:3px solid #B8975A;">
      📦 Pasūtījumu sagatavosim un informēsim par nosūtīšanas laiku.
    </p>';
  return luminaMail($epasts, $vards, 'Pasūtījums apstiprināts #' . $orderId . ' — LUMINA', $html);
}

/**
 * 6. Veikala pasūtījums — adminam
 */
function mailPasutijumsAdmin(string $klientaVards, string $klientaEmail, array $items, float $total, string $orderId): bool {
  $rows = '';
  foreach ($items as $item) {
    $rows .= '<tr><td style="padding:8px 12px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['name']) . '</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee;text-align:center;">× ' . (int)$item['qty'] . '</td>
      <td style="padding:8px 12px;border-bottom:1px solid #eee;text-align:right;color:#B8975A;">€' . number_format($item['cena'] * $item['qty'], 2) . '</td></tr>';
  }
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:20px;font-weight:400;color:#1C1C1C;margin:0 0 16px;">🛒 Jauns veikala pasūtījums — #' . htmlspecialchars($orderId) . '</h2>
    <p><strong>Klients:</strong> ' . htmlspecialchars($klientaVards) . ' · <a href="mailto:' . htmlspecialchars($klientaEmail) . '" style="color:#B8975A;">' . htmlspecialchars($klientaEmail) . '</a></p>
    <table width="100%" style="border-collapse:collapse;margin:16px 0;font-size:13px;font-family:Arial,sans-serif;">
      <thead><tr style="background:#1C1C1C;">
        <th style="padding:10px 12px;text-align:left;color:#B8975A;font-weight:400;">Produkts</th>
        <th style="padding:10px 12px;text-align:center;color:#B8975A;font-weight:400;">Gb.</th>
        <th style="padding:10px 12px;text-align:right;color:#B8975A;font-weight:400;">Cena</th>
      </tr></thead>
      <tbody>' . $rows . '</tbody>
      <tfoot><tr>
        <td colspan="2" style="padding:10px 12px;text-align:right;font-weight:bold;">Kopā:</td>
        <td style="padding:10px 12px;text-align:right;color:#B8975A;font-weight:bold;font-size:16px;">€' . number_format($total, 2) . '</td>
      </tr></tfoot>
    </table>
    <a href="' . SITE_URL . '/admin/index.php" style="background:#1C1C1C;color:#B8975A;padding:10px 24px;text-decoration:none;font-size:11px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;">
      Admin panelis →
    </a>';
  return luminaMail(MAIL_ADMIN, 'Admin', '🛒 Jauns pasūtījums #' . $orderId . ' — €' . number_format($total, 2), $html);
}

/**
 * 7. Foto pasūtījums (editors) — klientam
 */
function mailFotoPasutijumsKlients(string $epasts, string $vards, string $produkts, string $notes): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:26px;font-weight:400;color:#1C1C1C;margin:0 0 8px;">Foto pasūtījums saņemts ✓</h2>
    <p style="color:#666;margin-bottom:24px;">Paldies, ' . htmlspecialchars($vards) . '! Esam saņēmuši jūsu pasūtījumu.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#FAF8F4;border:1px solid #e8e3db;">
      <tr><td style="padding:18px 22px;border-bottom:1px solid #e8e3db;">
        <div style="font-size:10px;color:#B8975A;letter-spacing:3px;text-transform:uppercase;">Produkts</div>
        <div style="font-size:17px;color:#1C1C1C;margin-top:4px;">' . htmlspecialchars($produkts) . '</div>
      </td></tr>
      ' . ($notes ? '<tr><td style="padding:18px 22px;">
        <div style="font-size:10px;color:#B8975A;letter-spacing:3px;text-transform:uppercase;">Piezīmes</div>
        <div style="font-size:15px;color:#555;margin-top:4px;font-style:italic;">' . htmlspecialchars($notes) . '</div>
      </td></tr>' : '') . '
    </table>
    <p style="font-size:13px;color:#888;margin-top:20px;background:#FAF8F4;padding:12px 16px;border-left:3px solid #B8975A;">
      Sazināsimies ar jums 24 stundu laikā, lai apstiprinātu detaļas un galīgo cenu.
    </p>';
  return luminaMail($epasts, $vards, 'Foto pasūtījums saņemts — LUMINA', $html);
}
?>

/**
 * 8. Foto pasūtījums — adminam (ar foto info)
 */
function mailFotoPasutijumsAdmin(string $klientaVards, string $klientaEmail, string $produkts, string $notes, string $fotoInfo = ''): bool {
  $html = '
    <h2 style="font-family:Georgia,serif;font-size:20px;font-weight:400;color:#1C1C1C;margin:0 0 16px;">📸 Jauns foto pasūtījums</h2>
    <table width="100%" style="border-collapse:collapse;font-size:14px;font-family:Arial,sans-serif;">
      <tr style="background:#FAF8F4;">
        <td style="padding:10px 14px;color:#888;width:120px;">Klients</td>
        <td style="padding:10px 14px;font-weight:bold;">' . htmlspecialchars($klientaVards) . '</td>
      </tr>
      <tr>
        <td style="padding:10px 14px;color:#888;">E-pasts</td>
        <td style="padding:10px 14px;"><a href="mailto:' . htmlspecialchars($klientaEmail) . '" style="color:#B8975A;">' . htmlspecialchars($klientaEmail ?: '—') . '</a></td>
      </tr>
      <tr style="background:#FAF8F4;">
        <td style="padding:10px 14px;color:#888;">Produkts</td>
        <td style="padding:10px 14px;">' . htmlspecialchars($produkts) . '</td>
      </tr>
      ' . ($notes ? '<tr><td style="padding:10px 14px;color:#888;">Piezīmes</td><td style="padding:10px 14px;font-style:italic;">' . htmlspecialchars($notes) . '</td></tr>' : '') . '
      ' . ($fotoInfo ? '<tr style="background:#FAF8F4;"><td style="padding:10px 14px;color:#888;">Foto fails</td><td style="padding:10px 14px;font-size:12px;color:#555;">' . htmlspecialchars($fotoInfo) . '</td></tr>' : '') . '
    </table>
    <div style="text-align:center;margin-top:24px;">
      <a href="' . SITE_URL . '/admin/index.php" style="background:#1C1C1C;color:#B8975A;padding:10px 24px;text-decoration:none;font-size:11px;letter-spacing:2px;text-transform:uppercase;font-family:Arial,sans-serif;">
        Admin panelis →
      </a>
    </div>';
  return luminaMail(MAIL_ADMIN, 'Admin', '📸 Jauns foto pasūtījums — ' . $klientaVards, $html);
}
