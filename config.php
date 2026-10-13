<?php
// Para usar MySQL, copie config.example.php para config.local.php e ajuste a conexão.
$config = [
    'driver' => 'files',
    'storage' => getenv('MATHFUND_STORAGE') ?: __DIR__ . '/storage',
    'timezone' => 'America/Sao_Paulo',
    'mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'database' => 'mathfund', 'user' => 'root', 'password' => ''],
];
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace_recursive($config, require __DIR__ . '/config.local.php');
}
return $config;
