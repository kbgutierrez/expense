<?php

defined('BASEPATH') or exit('No direct script access allowed');

$active_group = 'default';
$query_builder = true;

if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    Dotenv\Dotenv::createImmutable(__DIR__ . '/../../')->safeLoad();
}

$dbHost = $_ENV['DB_HOST'] ?? '';
$dbName = $_ENV['DB_NAME'] ?? 'BigEExpense';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASS'] ?? '';

$db['default'] = array(
    'dsn' => '',
    'hostname' => $dbHost,
    'username' => $dbUser,
    'password' => $dbPass,
    'database' => $dbName,
    'dbdriver' => 'sqlsrv',
    'dbprefix' => '',
    'pconnect' => false,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => true,
    // 'cachedir' => 'application/cache',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => false,
    'compress' => false,
    'stricton' => false,
    'failover' => array(),
    'save_queries' => false,
);


$db['dbknet'] = array(
    'dsn' => '',
    'hostname' => $dbHost,
    'username' => $dbUser,
    'password' => $dbPass,
    'database' => $dbName,
    'dbdriver' => 'sqlsrv',
    'dbprefix' => '',
    'pconnect' => false,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => true,
    // 'cachedir' => 'application/cache',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => false,
    'compress' => false,
    'stricton' => false,
    'failover' => array(),
    'save_queries' => false,
);

