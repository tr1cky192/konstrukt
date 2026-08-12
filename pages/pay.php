<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<h1>Помилка</h1><p>Недопустимий метод запиту.</p>");
}

$siteId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['site_id'] ?? 'default');
$productName = trim($_POST['product_name'] ?? 'Замовлення');
$amount = trim($_POST['amount'] ?? '0');
$currency = trim($_POST['currency'] ?? 'UAH');

if ($currency === '₴') {
    $currency = 'UAH';
} elseif ($currency === '$') {
    $currency = 'USD';
} elseif ($currency === '€') {
    $currency = 'EUR';
}

$sitesDir = __DIR__ . '/../sites';
$filePath = $sitesDir . '/' . $siteId . '.json';

if (!file_exists($filePath)) {
    die("<h1>Помилка</h1><p>Сайт не знайдено.</p>");
}

$config = json_decode(file_get_contents($filePath), true);
$settings = $config['settings'] ?? [];

$merchantKey = trim($settings['payment_key'] ?? '');
$merchantSecret = trim($settings['payment_secret'] ?? '');


$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? "https" : "http";

$baseUri = $protocol . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);
$baseUri = rtrim($baseUri, '/\\');
$successUrl = $baseUri . "/" . urlencode($siteId) . "?status=success";


if (empty($merchantKey) || empty($merchantSecret)) {
    header("Location: " . $successUrl);
    exit;
}

$key = $merchantKey;         
$pass = $merchantSecret;     
$payment = 'CC';           
$req_token = 'Y';         
$url = $successUrl;

$formattedAmount = number_format((float) $amount, 2, '.', '');
$data = base64_encode(json_encode([
    'amount' => $formattedAmount,
    'description' => $productName,
    'currency' => $currency
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$sign = md5(strtoupper(
    strrev($key).
    strrev($payment).
    strrev($data).
    strrev($url).
    strrev($pass)
));
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <title>Payment</title>
</head>
<body onload="document.forms[0].submit()">
  <form action="https://secure.platononline.com/payment/auth" method="post">
    <input type="hidden" name="payment" value="<?php echo htmlspecialchars($payment); ?>" />
    <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>" />
    <input type="hidden" name="url" value="<?php echo htmlspecialchars($url); ?>" />
    <input type="hidden" name="data" value="<?php echo htmlspecialchars($data); ?>" />
    <input type="hidden" name="req_token" value="<?php echo htmlspecialchars($req_token); ?>" />
    <input type="hidden" name="sign" value="<?php echo htmlspecialchars($sign); ?>" />
  </form>
</body>
</html>