<?php

use GestContratos\Core\Application;

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '';
if (preg_match('#^/https?://#i', $requestPath)) {
    header('Location: ' . ltrim($requestPath, '/'));
    exit;
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = new Application(dirname(__DIR__));
$app->run();
