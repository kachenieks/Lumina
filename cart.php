<?php
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
      $_SESSION['cart'][$id] = ['qty' => 0, 'name' => $row['nosaukums'], 'cena' => $row['cena'], 'img' => $row['attels_url']];
    }
    $_SESSION['cart'][$id]['qty']++;
  }
} elseif ($action === 'remove') {
  $id = (int)($_GET['id'] ?? 0);
  unset($_SESSION['cart'][$id]);
} elseif ($action === 'clear') {
  $_SESSION['cart'] = [];
}

$count = array_sum(array_column($_SESSION['cart'], 'qty'));
$total = array_sum(array_map(fn($item) => $item['qty'] * $item['cena'], $_SESSION['cart']));

echo json_encode([
  'count' => $count,
  'total' => $total,
  'cart' => $_SESSION['cart']
]);
?>
