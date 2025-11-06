<?php

declare(strict_types=1);

// Bootstrap mínimo para carregar Dotenv e configurar ambiente

use Dotenv\Dotenv;

// Caminho base do projeto
$basePath = dirname(__DIR__);

// Carregar Composer autoload se existir
$autoload = $basePath . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Carregar variáveis de ambiente
if (file_exists($basePath . '/.env')) {
    $dotenv = Dotenv::createImmutable($basePath);
    $dotenv->safeLoad();
}

// Configurações padrão
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo');
ini_set('display_errors', ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? '1' : '0');

// Retorna basePath para reutilização
return [
    'base_path' => $basePath,
];
