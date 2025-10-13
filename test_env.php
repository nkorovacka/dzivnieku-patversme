<?php
require_once __DIR__ . '/vendor/autoload.php'; // это обязательно!

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "✅ Datubāze: " . ($_ENV['DB_NAME'] ?? '❌ .env nav nolasīts');
