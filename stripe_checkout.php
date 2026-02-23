<?php
/**
 * LUMINA — Stripe maksājumu apstrāde
 * 
 * Strādā ar Stripe PHP SDK vai bez tā (via API calls)
 * Sandbox režīms — ieliec savus STRIPE_KEY vērtības
 */
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

header('Content-Type: application/json');

// ── STRIPE KONFIGURĀCIJA ─────────────────────────────────
// !! Nomainiet uz saviem Stripe atslēgām !!
define('STRIPE_SECRET_KEY',  'sk_test_51S7aYd1BTbbN3kVbxXXrLPBOtdA2iMfhj6m9uiUXdHAqa93jvHorghulXfFRoF0cC2aR0Cv8KRznsu6zSykQfnys00xavsSCOw');
define('STRIPE_PUBLIC_KEY',  'pk_test_51S7aYd1BTbbN3kVbe1XtxXYIlM84mgkD54G67HDafrqrDu7blp2g99ikCw9Yi6ze9tsKsMSkdqS2anC5uBWgZapP00mkgSvzJ2');
define('SITE_BASE', 'https://kristovskis.lv/4pt/blazkova/lumina/Lumina');
// ─────────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── 1. Izveidot Stripe Checkout sesiju ───────────────────
if ($action === 'create_checkout') {
  if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode(['error' => 'Grozs ir tukšs']);
    exit;
  }

  $lineItems = [];
  foreach ($_SESSION['cart'] as $id => $item) {
    $lineItems[] = [
      'price_data' => [
        'currency'     => 'eur',
        'unit_amount'  => (int)round($item['cena'] * 100), // centi
        'product_data' => [
          'name' => $item['name'],
        ],
      ],
      'quantity' => (int)$item['qty'],
    ];
  }

  $orderId = 'LUM-' . strtoupper(substr(uniqid(), -6));
  $_SESSION['pending_order_id'] = $orderId;

  $payload = [
    'payment_method_types' => ['card'],
    'line_items'           => $lineItems,
    'mode'                 => 'payment',
    'success_url'          => SITE_BASE . '/stripe_checkout.php?action=success&order=' . $orderId,
    'cancel_url'           => SITE_BASE . '/veikals.php?cancelled=1',
    'metadata'             => [
      'order_id'    => $orderId,
      'klients_id'  => $_SESSION['klients_id'] ?? 0,
    ],
  ];

  // Add customer email if logged in
  if (isset($_SESSION['klients_epasts'])) {
    $payload['customer_email'] = $_SESSION['klients_epasts'];
  }

  $response = stripeRequest('POST', '/checkout/sessions', $payload);

  if (isset($response['url'])) {
    echo json_encode(['url' => $response['url']]);
  } else {
    echo json_encode(['error' => $response['error']['message'] ?? 'Stripe kļūda']);
  }
  exit;
}

// ── 2. Veiksmīgs maksājums — atgriešanās no Stripe ──────
if ($action === 'success') {
  $orderId  = $_GET['order'] ?? ($_SESSION['pending_order_id'] ?? '');
  $sessionId = $_GET['session_id'] ?? '';

  // Verify payment with Stripe
  $verified = false;
  if ($sessionId) {
    $stripeSession = stripeRequest('GET', '/checkout/sessions/' . $sessionId, []);
    $verified = isset($stripeSession['payment_status']) && $stripeSession['payment_status'] === 'paid';
  } else {
    // Fallback if session_id not passed
    $verified = !empty($orderId);
  }

  if ($verified && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $klientsId    = (int)($_SESSION['klients_id'] ?? 0);
    $klientaVards = $_SESSION['klients_vards'] ?? 'Viesis';
    $klientaEmail = $_SESSION['klients_epasts'] ?? '';

    // Calculate total
    $items = [];
    $total = 0;
    foreach ($_SESSION['cart'] as $id => $item) {
      $items[] = $item;
      $total += $item['cena'] * $item['qty'];
    }

    // Save order to DB
    $orderIdEsc = escape($savienojums, $orderId);
    $sql = "INSERT IGNORE INTO pasutijumi (klienta_id, produkts, foto_fails, papildu_info, statuss, izveidots)
            VALUES ($klientsId, '$orderIdEsc', '', 'Stripe maksājums — €" . number_format($total, 2) . "', 'apmaksats', NOW())";
    @mysqli_query($savienojums, $sql);

    // Update client total spent
    if ($klientsId) {
      mysqli_query($savienojums, "UPDATE klienti SET kopeja_summa = kopeja_summa + $total WHERE id=$klientsId");
    }

    // Send emails
    if ($klientaEmail) {
      mailPasutijumsKlients($klientaEmail, $klientaVards, $items, $total, $orderId);
    }
    mailPasutijumsAdmin($klientaVards, $klientaEmail, $items, $total, $orderId);

    // Clear cart
    $_SESSION['cart'] = [];
    unset($_SESSION['pending_order_id']);
  }

  // Redirect to success page
  header('Location: ' . SITE_BASE . '/veikals.php?paid=1&order=' . urlencode($orderId));
  exit;
}

// ── 3. Webhook no Stripe (papildus drošībai) ────────────
if ($action === 'webhook') {
  $payload = file_get_contents('php://input');
  $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
  $webhookSecret = 'whsec_IEVIETO_SAVU_WEBHOOK_SECRET'; // no Stripe dashboard

  // Verify webhook signature
  if (!verifyStripeWebhook($payload, $sigHeader, $webhookSecret)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
  }

  $event = json_decode($payload, true);

  if ($event['type'] === 'checkout.session.completed') {
    $session = $event['data']['object'];
    $orderId = $session['metadata']['order_id'] ?? '';
    $klientsId = (int)($session['metadata']['klients_id'] ?? 0);
    $email = $session['customer_email'] ?? '';

    if ($orderId && $email) {
      $vards = 'Klients';
      if ($klientsId) {
        $kl = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT vards FROM klienti WHERE id=$klientsId"));
        $vards = $kl['vards'] ?? 'Klients';
      }
      // Mark as paid in DB
      $oid = escape($savienojums, $orderId);
      @mysqli_query($savienojums, "UPDATE pasutijumi SET statuss='apmaksats' WHERE produkts='$oid'");
    }
  }

  http_response_code(200);
  echo json_encode(['received' => true]);
  exit;
}

// ── Stripe API helper ────────────────────────────────────
function stripeRequest(string $method, string $endpoint, array $data): array {
  $url = 'https://api.stripe.com/v1' . $endpoint;
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
  ]);

  if ($method === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(flattenForStripe($data)));
  }

  $result = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return json_decode($result, true) ?? ['error' => ['message' => 'cURL kļūda']];
}

// Stripe needs nested arrays as brackets: line_items[0][price_data][currency]=eur
function flattenForStripe(array $data, string $prefix = ''): array {
  $result = [];
  foreach ($data as $key => $value) {
    $fullKey = $prefix ? "{$prefix}[{$key}]" : $key;
    if (is_array($value)) {
      $result = array_merge($result, flattenForStripe($value, $fullKey));
    } else {
      $result[$fullKey] = $value;
    }
  }
  return $result;
}

function verifyStripeWebhook(string $payload, string $sigHeader, string $secret): bool {
  $parts = explode(',', $sigHeader);
  $timestamp = '';
  $signatures = [];
  foreach ($parts as $part) {
    [$k, $v] = explode('=', $part, 2);
    if ($k === 't') $timestamp = $v;
    if ($k === 'v1') $signatures[] = $v;
  }
  if (!$timestamp) return false;
  $expectedSig = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
  foreach ($signatures as $sig) {
    if (hash_equals($expectedSig, $sig)) return true;
  }
  return false;
}

echo json_encode(['error' => 'Nezināma darbība']);
?>
