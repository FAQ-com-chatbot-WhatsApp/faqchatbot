#!/usr/bin/env php
<?php
/**
 * Smoke Tests - Testes básicos dos endpoints da API
 * 
 * Uso: php scripts/smoke_tests.php
 */

// Detecta se está rodando dentro do container ou no host
$isInsideContainer = file_exists('/.dockerenv');
$baseUrl = $isInsideContainer ? 'http://webcore' : 'http://localhost:8080';
$apiUrl = $baseUrl . '/api';

echo "🧪 Iniciando Smoke Tests\n";
echo "Base URL: {$apiUrl}\n\n";

$passed = 0;
$failed = 0;

/**
 * Faz uma requisição HTTP e valida o resultado
 */
function testEndpoint(string $method, string $url, ?array $data = null, int $expectedStatus = 200, ?string $description = null): bool
{
    global $passed, $failed;
    
    $desc = $description ?? "{$method} {$url}";
    echo "▶️  {$desc}... ";
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === $expectedStatus) {
        echo "✅ PASS (HTTP {$httpCode})\n";
        $passed++;
        return true;
    } else {
        echo "❌ FAIL (esperado HTTP {$expectedStatus}, recebido HTTP {$httpCode})\n";
        if ($response) {
            $decoded = json_decode($response, true);
            if ($decoded && isset($decoded['error'])) {
                echo "   Erro: {$decoded['error']['message']}\n";
            }
        }
        $failed++;
        return false;
    }
}

echo "📋 Testando endpoints básicos...\n\n";

// 1. Health check
testEndpoint('GET', "{$apiUrl}/health/ping", null, 200, 'Health Check');

// 2. Criar contato
$contactData = [
    'name' => 'Teste Smoke Test',
    'phone' => '+5511999998888'
];
$contactCreated = testEndpoint('POST', "{$apiUrl}/contacts", $contactData, 201, 'Criar contato');

// 3. Listar contatos (sem autenticação, deve falhar com 401)
testEndpoint('GET', "{$apiUrl}/contacts", null, 401, 'Listar contatos sem auth');

// 4. Criar flow (sem autenticação, deve falhar com 401)
$flowData = [
    'name' => 'Flow Teste',
    'description' => 'Flow criado via smoke test',
    'status' => 'draft',
    'steps' => []
];
testEndpoint('POST', "{$apiUrl}/flows", $flowData, 401, 'Criar flow sem auth');

// 5. Listar flows (sem autenticação, deve falhar com 401)
testEndpoint('GET', "{$apiUrl}/flows", null, 401, 'Listar flows sem auth');

echo "\n📊 Resultados:\n";
echo "   ✅ Passou: {$passed}\n";
echo "   ❌ Falhou: {$failed}\n";
echo "   📈 Total: " . ($passed + $failed) . "\n\n";

if ($failed === 0) {
    echo "🎉 Todos os testes passaram!\n";
    exit(0);
} else {
    echo "⚠️  Alguns testes falharam. Verifique os logs acima.\n";
    exit(1);
}
