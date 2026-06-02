<?php

header('Content-Type: text/plain; charset=UTF-8');

$basePath = dirname(__DIR__);

$checks = [
    'php_version' => PHP_VERSION,
    'php_version_ok' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'yes' : 'no',
    'ext_openssl' => extension_loaded('openssl') ? 'yes' : 'no',
    'ext_pdo' => extension_loaded('pdo') ? 'yes' : 'no',
    'ext_pdo_mysql' => extension_loaded('pdo_mysql') ? 'yes' : 'no',
    'ext_mbstring' => extension_loaded('mbstring') ? 'yes' : 'no',
    'ext_tokenizer' => extension_loaded('tokenizer') ? 'yes' : 'no',
    'ext_xml' => extension_loaded('xml') ? 'yes' : 'no',
    'ext_ctype' => extension_loaded('ctype') ? 'yes' : 'no',
    'vendor_autoload' => file_exists($basePath.'/vendor/autoload.php') ? 'yes' : 'no',
    'env_file' => file_exists($basePath.'/.env') ? 'yes' : 'no',
    'storage_writable' => is_writable($basePath.'/storage') ? 'yes' : 'no',
    'cache_writable' => is_writable($basePath.'/bootstrap/cache') ? 'yes' : 'no',
];

foreach ($checks as $name => $value) {
    echo $name.': '.$value.PHP_EOL;
}
