#!/usr/bin/env php
<?php
/**
 * Migration Runner
 * 
 * Aplica migrations SQL em ordem no banco de dados
 * 
 * Uso: php scripts/apply_migrations.php [--reset]
 */

// Carrega configuração do banco
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

$dbHost = $_ENV['DB_HOST'] ?? 'mariadb';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'botdb';
$dbUser = $_ENV['DB_USERNAME'] ?? 'bot_user';
$dbPass = $_ENV['DB_PASSWORD'] ?? 'pwd123';

echo "🔌 Conectando ao banco de dados...\n";
echo "   Host: {$dbHost}:{$dbPort}\n";
echo "   Database: {$dbName}\n\n";

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Conexão estabelecida\n\n";
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}

// Verifica se deve resetar o banco
$reset = in_array('--reset', $argv);
if ($reset) {
    echo "⚠️  MODO RESET ATIVADO - Recriando estrutura do banco...\n\n";
    
    $tables = ['conversation_transitions', 'messages', 'conversations', 'flows', 'contacts'];
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS {$table}");
            echo "   🗑️  Tabela {$table} removida\n";
        } catch (PDOException $e) {
            echo "   ⚠️  Erro ao remover {$table}: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

// Lista de migrations em ordem
$migrations = [
    'database/schema.sql' => 'Schema base (contacts, flows, conversations, messages)',
    'migrations/001_add_soft_delete_and_current_step.sql' => 'Adicionar soft delete e current_step',
    'migrations/001b_apply_changes.sql' => 'Aplicar mudanças adicionais',
    'migrations/003_add_indices.sql' => 'Adicionar índices de performance',
    'migrations/seed_sample_data.sql' => 'Seed de dados de exemplo',
];

echo "📦 Aplicando migrations...\n\n";

foreach ($migrations as $file => $description) {
    $fullPath = __DIR__ . '/../' . $file;
    
    if (!file_exists($fullPath)) {
        echo "⚠️  Arquivo não encontrado: {$file}\n";
        continue;
    }
    
    echo "▶️  Aplicando: {$description}\n";
    echo "   Arquivo: {$file}\n";
    
    try {
        $sql = file_get_contents($fullPath);
        
        // Executa as queries (split por ponto-e-vírgula não é 100% confiável, mas funciona para scripts simples)
        $pdo->exec($sql);
        
        echo "   ✅ Aplicado com sucesso\n\n";
    } catch (PDOException $e) {
        echo "   ❌ Erro: " . $e->getMessage() . "\n\n";
        // Continua com próximas migrations mesmo se houver erro (migrations são idempotentes)
    }
}

echo "✨ Processo concluído!\n\n";

// Mostra estatísticas do banco
echo "📊 Estatísticas do banco:\n";

$tables = ['contacts', 'flows', 'conversations', 'messages'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM {$table}");
        $result = $stmt->fetch();
        $count = $result['total'] ?? 0;
        echo "   {$table}: {$count} registro(s)\n";
    } catch (PDOException $e) {
        echo "   {$table}: (erro ao contar)\n";
    }
}

echo "\n✅ Banco de dados pronto para uso!\n";
