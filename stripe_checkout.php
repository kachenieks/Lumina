<?php
/**
 * LUMINA — Stripe Checkout
 * Ievieto savus Stripe atslēgas zemāk!
 */
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

// ══════════════════════════════════════════
//  IELIEC ŠEIT SAVUS STRIPE ATSLĒGAS
// ══════════════════════════════════════════
define('STRIPE_SECRET_KEY', 'sk_test_51S7aYd1BTbbN3kVbxXXrLPBOtdA2iMfhj6m9uiUXdHAqa93jvHorghulXfFRoF0cC2aR0Cv8KRznsu6zSykQfnys00xavsSCOw');
define('SITE_BASE', 'https://kristovskis.lv/4pt/blazkova/lumina/Lumina');
// ══════════════════════════════════════════

$action = $_GET['action'] ?? '';

// ── Izveidot Stripe Checkout sesiju ────────────────────────
if ($action === 'create_checkout') {
  header('Content-Type: application/json');

  if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode(['error' => 'Grozs ir tukšs']); exit;
  }

  // Check curl is available
  if (!function_exists('curl_init')) {
    echo json_encode(['error' => 'cURL nav pieejams serverī. Sazinieties ar hostingu.']); exit;
  }

  // Check Stripe key is set
  if (strpos(STRIPE_SECRET_KEY, 'IEVIETO') !== false) {
    echo json_encode(['error' => 'Stripe atslēga nav konfigurēta. Ieliec savu sk_test_... stripe_checkout.php failā.']); exit;
  }

  $lineItems = [];
  $i = 0;
  foreach ($_SESSION['cart'] as $id => $item) {
    $lineItems["line_items[$i][price_data][currency]"] = 'eur';
    $lineItems["line_items[$i][price_data][unit_amount]"] = (int)round($item['cena'] * 100);
    $lineItems["line_items[$i][price_data][product_data][name]"] = $item['name'];
    $lineItems["line_items[$i][quantity]"] = (int)$item['qty'];
    $i++;
  }

  $orderId = 'LUM' . date('ymd') . strtoupper(substr(uniqid(), -4));
  $_SESSION['pending_order_id'] = $orderId;

  $params = array_merge($lineItems, [
    'mode'                => 'payment',
    'payment_method_types[0]' => 'card',
    'success_url'         => SITE_BASE . '/stripe_checkout.php?action=success&order=' . $orderId . '&session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'          => SITE_BASE . '/veikals.php?cancelled=1',
    'metadata[order_id]'  => $orderId,
    'metadata[klients_id]'=> (int)($_SESSION['klients_id'] ?? 0),
  ]);

  if (!empty($_SESSION['klients_epasts'])) {
    $params['customer_email'] = $_SESSION['klients_epasts'];
  }

  $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($params),
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
  ]);

  $body    = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlError = curl_error($ch);
  curl_close($ch);

  if ($curlError) {
    error_log('Stripe cURL error: ' . $curlError);
    echo json_encode(['error' => 'Savienojuma kļūda ar Stripe. ' . $curlError]); exit;
  }

  $resp = json_decode($body, true);
  if ($httpCode === 200 && isset($resp['url'])) {
    echo json_encode(['url' => $resp['url']]);
  } else {
    $msg = $resp['error']['message'] ?? ('HTTP ' . $httpCode);
    error_log('Stripe error: ' . $body);
    echo json_encode(['error' => 'Stripe kļūda: ' . $msg]);
  }
  exit;
}

// ── Veiksmīgs maksājums — atgriešanās ────────────────────
if ($action === 'success') {
  $orderId = $_GET['order'] ?? ($_SESSION['pending_order_id'] ?? '');
  $sessionId = $_GET['session_id'] ?? '';

  if (!empty($orderId) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $klientsId    = (int)($_SESSION['klients_id'] ?? 0);
    $klientaVards = $_SESSION['klients_vards'] ?? 'Klients';
    $klientaEmail = $_SESSION['klients_epasts'] ?? '';

    $items = [];
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
      $items[] = $item;
      $total += $item['cena'] * $item['qty'];
    }

    // Save to DB
    $oidEsc = escape($savienojums, $orderId);
    $totalEsc = number_format($total, 2);
    @mysqli_query($savienojums,
      "INSERT IGNORE INTO pasutijumi (klienta_id, produkts, foto_fails, papildu_info, statuss, izveidots)
       VALUES ($klientsId, '$oidEsc', '', 'Stripe €$totalEsc', 'apmaksats', NOW())"
    );

    if ($klientsId) {
      mysqli_query($savienojums, "UPDATE klienti SET kopeja_summa = kopeja_summa + $total WHERE id=$klientsId");
    }

    // Emails
    if ($klientaEmail) mailPasutijumsKlients($klientaEmail, $klientaVards, $items, $total, $orderId);
    mailPasutijumsAdmin($klientaVards, $klientaEmail, $items, $total, $orderId);

    $_SESSION['cart'] = [];
    unset($_SESSION['pending_order_id']);
  }

  header('Location: ' . SITE_BASE . '/veikals.php?paid=1&order=' . urlencode($orderId));
  exit;
}

// ── Stripe Webhook ─────────────────────────────────────────
if ($action === 'webhook') {
  header('Content-Type: application/json');
  $payload = @file_get_contents('php://input');
  $event = json_decode($payload, true);
  if ($event && $event['type'] === 'checkout.session.completed') {
    // Mark as paid
    $oid = escape($savienojums, $event['data']['object']['metadata']['order_id'] ?? '');
    if ($oid) @mysqli_query($savienojums, "UPDATE pasutijumi SET statuss='apmaksats' WHERE produkts='$oid'");
  }
  http_response_code(200);
  echo json_encode(['ok' => true]);
  exit;
}

echo json_encode(['error' => 'Nezināma darbība']);
?>
