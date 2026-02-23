<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$action = $_GET['action'] ?? '';

if ($action === 'add') {
  $id = (int)($_GET['id'] ?? 0);
  $prece = mysqli_query($savienojums, "SELECT * FROM preces WHERE id=$id AND aktivs=1");
  if ($row = mysqli_fetch_assoc($prece)) {
    if (!isset($_SESSION['cart'][$id])) {
      $_SESSION['cart'][$id] = ['id' => $id, 'qty' => 0, 'name' => $row['nosaukums'], 'cena' => (float)$row['cena']];
    }
    $_SESSION['cart'][$id]['qty']++;
  }

} elseif ($action === 'remove') {
  $id = (int)($_GET['id'] ?? 0);
  unset($_SESSION['cart'][$id]);

} elseif ($action === 'clear') {
  $_SESSION['cart'] = [];

} elseif ($action === 'get') {
  // Just return current cart — no changes

} elseif ($action === 'checkout_stripe') {
  // Redirect to Stripe checkout creator
  // The actual Stripe session creation happens in stripe_checkout.php
  if (empty($_SESSION['cart'])) {
    echo json_encode(['error' => 'Grozs ir tukšs']);
    exit;
  }
  echo json_encode(['redirect' => '/4pt/blazkova/lumina/Lumina/stripe_checkout.php?action=create_checkout']);
  exit;
}

$items = [];
foreach ($_SESSION['cart'] as $id => $item) {
  $items[] = ['id' => (int)$id, 'name' => $item['name'], 'cena' => (float)$item['cena'], 'qty' => (int)$item['qty']];
}

$count = array_sum(array_column($_SESSION['cart'], 'qty'));
$total = array_sum(array_map(fn($i) => $i['qty'] * $i['cena'], $_SESSION['cart']));

echo json_encode(['count' => $count, 'total' => $total, 'items' => $items]);
?>
