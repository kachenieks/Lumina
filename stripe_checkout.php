<?php
/**
 * LUMINA — Stripe Checkout (cURL, nav vajadzīga bibliotēka)
 */
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';

// ══════════════════════════════════════════════════
//  !! IELIEC SAVU STRIPE SECRET KEY ŠEIT !!
//  Atrodi: stripe.com → Developers → API keys
// ══════════════════════════════════════════════════
define('STRIPE_KEY', 'sk_test_51SDpRVHB157X3y90xO2i2qWUYT4OVYkSt5yDLABNTuruLY0QDWvcLKVgTivBADWGU7THD7f0k00B7xFmsWgiKEcb00pcGuiASA');
define('SITE_BASE',  'https://kristovskis.lv/4pt/blazkova/lumina/Lumina');
// ══════════════════════════════════════════════════

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

// ── Diagnostika — pārbaude vai Stripe strādā ─────
if ($action === 'test') {
  $errors = [];
  if (!function_exists('curl_init'))     $errors[] = 'cURL nav iespējots serverī';
  if (strpos(STRIPE_KEY, 'IEVIETO') !== false) $errors[] = 'Stripe atslēga nav ielikta';
  // Try Stripe connection
  if (empty($errors)) {
    $ch = curl_init('https://api.stripe.com/v1/balance');
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERPWD        => STRIPE_KEY . ':',
      CURLOPT_TIMEOUT        => 10,
      CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err)     $errors[] = 'cURL kļūda: ' . $err;
    elseif ($http === 401) $errors[] = 'Stripe atslēga ir nepareiza (401 Unauthorized)';
    elseif ($http !== 200) $errors[] = 'Stripe atbildēja ar HTTP ' . $http . ': ' . substr($body, 0, 200);
  }
  echo json_encode($errors ? ['status'=>'kļūda','kļūdas'=>$errors] : ['status'=>'OK','message'=>'Stripe darbojas!']);
  exit;
}

// ── Izveidot checkout sesiju ─────────────────────
if ($action === 'create_checkout') {
  if (!function_exists('curl_init')) {
    echo json_encode(['error'=>'cURL nav iespējots serverī. Sazinieties ar hostu.']); exit;
  }
  if (strpos(STRIPE_KEY, 'IEVIETO') !== false) {
    echo json_encode(['error'=>'Stripe atslēga nav konfigurēta stripe_checkout.php failā.']); exit;
  }
  if (empty($_SESSION['cart'])) {
    echo json_encode(['error'=>'Grozs ir tukšs']); exit;
  }

  $params = [];
  $i = 0;
  foreach ($_SESSION['cart'] as $item) {
    $params["line_items[$i][price_data][currency]"]              = 'eur';
    $params["line_items[$i][price_data][unit_amount]"]           = (int)round($item['cena'] * 100);
    $params["line_items[$i][price_data][product_data][name]"]    = $item['name'];
    $params["line_items[$i][quantity]"]                          = (int)$item['qty'];
    $i++;
  }

  $orderId = 'LUM' . date('ymd') . strtoupper(substr(uniqid(), -5));
  $_SESSION['pending_order_id'] = $orderId;

  $params['mode']                    = 'payment';
  $params['payment_method_types[0]'] = 'card';
  $params['success_url']             = SITE_BASE . '/stripe_checkout.php?action=success&order=' . $orderId . '&session_id={CHECKOUT_SESSION_ID}';
  $params['cancel_url']              = SITE_BASE . '/veikals.php?cancelled=1';
  $params['metadata[order_id]']      = $orderId;
  $params['metadata[klients_id]']    = (int)($_SESSION['klients_id'] ?? 0);
  // Pre-fill email for logged in users
  if (!empty($_SESSION['klients_epasts'])) {
    $params['customer_email'] = $_SESSION['klients_epasts'];
  }
  // Always collect billing details so we get customer email for guests
  $params['billing_address_collection'] = 'auto';

  $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($params),
    CURLOPT_USERPWD        => STRIPE_KEY . ':',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 20,
  ]);

  $body     = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curlErr  = curl_error($ch);
  curl_close($ch);

  if ($curlErr) {
    echo json_encode(['error' => 'Savienojuma kļūda: ' . $curlErr]); exit;
  }
  $resp = json_decode($body, true);
  if ($httpCode === 200 && isset($resp['url'])) {
    echo json_encode(['url' => $resp['url']]);
  } else {
    $msg = $resp['error']['message'] ?? ('HTTP ' . $httpCode);
    echo json_encode(['error' => 'Stripe: ' . $msg]);
  }
  exit;
}

// ── Veiksmīgs maksājums ──────────────────────────
if ($action === 'success') {
  header('Content-Type: text/html');
  $orderId   = $_GET['order'] ?? ($_SESSION['pending_order_id'] ?? '');
  $sessionId = $_GET['session_id'] ?? '';

  if (!empty($orderId) && !empty($_SESSION['cart'])) {
    $klientsId    = (int)($_SESSION['klients_id'] ?? 0);
    $klientaVards = $_SESSION['klients_vards'] ?? 'Klients';
    $klientaEmail = $_SESSION['klients_epasts'] ?? '';

    // Ja viesis — iegūst e-pastu no Stripe session
    if (empty($klientaEmail) && !empty($sessionId) && strpos(STRIPE_KEY, 'IEVIETO') === false) {
      $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => STRIPE_KEY . ':',
        CURLOPT_TIMEOUT        => 10,
      ]);
      $sBody = curl_exec($ch);
      curl_close($ch);
      $sData = json_decode($sBody, true);
      // Stripe returns customer_email or customer_details.email
      $klientaEmail = $sData['customer_email']
        ?? $sData['customer_details']['email']
        ?? '';
      if ($klientaEmail && $klientaVards === 'Klients') {
        $klientaVards = explode('@', $klientaEmail)[0];
      }
    }

    $items = []; $total = 0;
    foreach ($_SESSION['cart'] as $item) {
      $items[] = $item;
      $total += $item['cena'] * $item['qty'];
    }

    $oidEsc   = escape($savienojums, $orderId);
    $totalFmt = number_format($total, 2);

    // Save each cart item to pasutijumi table
    foreach ($_SESSION['cart'] as $cartItem) {
      $prod  = escape($savienojums, $cartItem['name'] ?? '');
      $foto  = escape($savienojums, $cartItem['foto_url'] ?? '');
      $notes = escape($savienojums, $cartItem['notes'] ?? ('Stripe #' . $orderId . ' €' . $totalFmt));
      $crop  = escape($savienojums, $cartItem['crop_data'] ?? '');
      @mysqli_query($savienojums,
        "INSERT INTO pasutijumi (klienta_id, produkts, foto_fails, crop_data, papildu_info, statuss, izveidots)
         VALUES ($klientsId, '$prod', '$foto', '$crop', '$notes', 'apmaksats', NOW())"
      );
    }

    if ($klientsId) {
      @mysqli_query($savienojums, "UPDATE klienti SET kopeja_summa = kopeja_summa + $total WHERE id=$klientsId");
    }

    // Send emails
    try {
      require_once __DIR__ . '/includes/mailer.php';
      if ($klientaEmail) mailPasutijumsKlients($klientaEmail, $klientaVards, $items, $total, $orderId);
      mailPasutijumsAdmin($klientaVards, $klientaEmail, $items, $total, $orderId);
    } catch (\Throwable $e) { error_log('Stripe mail error: ' . $e->getMessage()); }

    $_SESSION['cart'] = [];
    unset($_SESSION['pending_order_id']);
  }

  header('Location: ' . SITE_BASE . '/veikals.php?paid=1&order=' . urlencode($orderId));
  exit;
}

// ── Webhook ──────────────────────────────────────
if ($action === 'webhook') {
  $payload = @file_get_contents('php://input');
  $event = json_decode($payload, true);
  if ($event && $event['type'] === 'checkout.session.completed') {
    $oid = escape($savienojums, $event['data']['object']['metadata']['order_id'] ?? '');
    if ($oid) @mysqli_query($savienojums, "UPDATE pasutijumi SET statuss='apmaksats' WHERE produkts='$oid'");
  }
  http_response_code(200);
  echo json_encode(['ok' => true]);
  exit;
}

echo json_encode(['error' => 'Nezināma darbība']);
