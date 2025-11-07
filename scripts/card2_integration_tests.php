<?php
/**
 * Card 2 Integration Tests
 * 
 * Testes abrangentes para validação dos endpoints de Mensagens e Contatos.
 * 
 * Uso:
 *   php scripts/card2_integration_tests.php
 * 
 * Requer:
 *   - Servidor web rodando (http://localhost:8080 ou webcore container)
 *   - JWT token válido (gerar via login endpoint)
 *   - Database com schema aplicado
 */

// Detectar ambiente
$isContainer = file_exists('/.dockerenv') || getenv('HOSTNAME') === 'webcore';
$baseUrl = $isContainer ? 'http://webcore' : 'http://localhost:8080';
$apiUrl = $baseUrl . '/api';

// Cores para output
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m"); // No Color

$testsPassed = 0;
$testsFailed = 0;
$testsSkipped = 0;

// Mock JWT token (substituir por token válido em produção)
$jwtToken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODA4MCIsImF1ZCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDgwIiwiaWF0IjoxNzMwOTUwNDAwLCJleHAiOjE3MzEwMzY4MDAsInVzZXJfaWQiOiIxMjMiLCJ1c2VybmFtZSI6InRlc3RfdXNlciJ9.fake_signature";

/**
 * Faz requisição HTTP
 */
function httpRequest($method, $endpoint, $data = null, $token = null) {
    global $apiUrl;
    
    $ch = curl_init($apiUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

/**
 * Assert test
 */
function assertTest($name, $condition, $message = '') {
    global $testsPassed, $testsFailed;
    
    if ($condition) {
        $testsPassed++;
        echo GREEN . "✓ " . NC . $name . "\n";
        return true;
    } else {
        $testsFailed++;
        echo RED . "✗ " . NC . $name . "\n";
        if ($message) {
            echo RED . "  → " . $message . NC . "\n";
        }
        return false;
    }
}

/**
 * Skip test
 */
function skipTest($name, $reason) {
    global $testsSkipped;
    $testsSkipped++;
    echo YELLOW . "⊘ " . NC . $name . YELLOW . " (pulado: $reason)" . NC . "\n";
}

/**
 * Print section header
 */
function section($title) {
    echo "\n" . BLUE . "═══════════════════════════════════════════════" . NC . "\n";
    echo BLUE . " $title" . NC . "\n";
    echo BLUE . "═══════════════════════════════════════════════" . NC . "\n\n";
}

// =============================================================================
// PREPARAÇÃO
// =============================================================================

section("PREPARAÇÃO");

// Obter IDs de dados existentes para testes (sem depender de deleted_at)
try {
    $pdo = new PDO(
        'mysql:host=whatsbot-db;dbname=botdb;charset=utf8mb4',
        'bot_user',
        'pwd123',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    $existingContact = $pdo->query("SELECT id, name, phone FROM contacts ORDER BY created_at DESC LIMIT 1")->fetch();
    $existingConversation = $pdo->query("SELECT id, contact_id FROM conversations ORDER BY created_at DESC LIMIT 1")->fetch();
    $existingMessage = $pdo->query("SELECT id, conversation_id FROM messages ORDER BY created_at DESC LIMIT 1")->fetch();
} catch (PDOException $e) {
    echo RED . "Erro ao conectar ao banco: " . $e->getMessage() . NC . "\n";
    exit(1);
}

echo "Database entities found:\n";
echo "  Contact: " . ($existingContact ? $existingContact['id'] : 'NONE') . "\n";
echo "  Conversation: " . ($existingConversation ? $existingConversation['id'] : 'NONE') . "\n";
echo "  Message: " . ($existingMessage ? $existingMessage['id'] : 'NONE') . "\n";

// =============================================================================
// TESTES DE MENSAGENS
// =============================================================================

section("TESTES DE MENSAGENS");

// Test 1: POST /messages - Criar mensagem válida
if ($existingConversation) {
    $response = httpRequest('POST', '/messages', [
        'conversation_id' => $existingConversation['id'],
        'sender' => 'user',
        'content' => 'Test message from integration tests',
        'type' => 'text'
    ], $jwtToken);
    
    $success = assertTest(
        "POST /messages - Criar mensagem",
        $response['code'] === 201 && isset($response['body']['message_id']),
        "HTTP {$response['code']}, Body: " . json_encode($response['body'])
    );
    
    if ($success) {
        $createdMessageId = $response['body']['message_id'];
    }
} else {
    skipTest("POST /messages", "nenhuma conversa disponível");
}

// Test 2: POST /messages - Validação de sender inválido
if ($existingConversation) {
    $response = httpRequest('POST', '/messages', [
        'conversation_id' => $existingConversation['id'],
        'sender' => 'invalid_sender',
        'content' => 'Test'
    ], $jwtToken);
    
    assertTest(
        "POST /messages - Validar sender inválido",
        $response['code'] === 422,
        "Esperado 422, recebido {$response['code']}"
    );
} else {
    skipTest("POST /messages validação", "nenhuma conversa disponível");
}

// Test 3: POST /messages - Validação de conversation_id inválido
$response = httpRequest('POST', '/messages', [
    'conversation_id' => 'invalid-uuid',
    'sender' => 'user',
    'content' => 'Test'
], $jwtToken);

assertTest(
    "POST /messages - Validar UUID inválido",
    $response['code'] === 422,
    "Esperado 422, recebido {$response['code']}"
);

// Test 4: GET /messages - Listar todas as mensagens
$response = httpRequest('GET', '/messages?page=1&per_page=10', null, $jwtToken);

assertTest(
    "GET /messages - Listar com paginação",
    $response['code'] === 200 && isset($response['body']['messages']),
    "HTTP {$response['code']}"
);

// Test 5: GET /messages - Filtrar por conversation_id
if ($existingConversation) {
    $response = httpRequest('GET', '/messages?conversation_id=' . $existingConversation['id'], null, $jwtToken);
    
    assertTest(
        "GET /messages - Filtrar por conversation_id",
        $response['code'] === 200 && isset($response['body']['messages']),
        "HTTP {$response['code']}"
    );
} else {
    skipTest("GET /messages filtro", "nenhuma conversa disponível");
}

// Test 6: GET /messages - Filtrar por direction
$response = httpRequest('GET', '/messages?direction=in&page=1', null, $jwtToken);

assertTest(
    "GET /messages - Filtrar por direction",
    $response['code'] === 200,
    "HTTP {$response['code']}"
);

// Test 7: GET /messages/{id} - Buscar mensagem específica
if ($existingMessage) {
    $response = httpRequest('GET', '/messages/' . $existingMessage['id'], null, $jwtToken);
    
    assertTest(
        "GET /messages/{id} - Buscar por ID",
        $response['code'] === 200 && $response['body']['id'] === $existingMessage['id'],
        "HTTP {$response['code']}"
    );
} else {
    skipTest("GET /messages/{id}", "nenhuma mensagem disponível");
}

// Test 8: PUT /messages/{id} - Atualizar mensagem
if (isset($createdMessageId)) {
    $response = httpRequest('PUT', '/messages/' . $createdMessageId, [
        'content' => 'Updated message content',
        'status' => 'delivered'
    ], $jwtToken);
    
    assertTest(
        "PUT /messages/{id} - Atualizar",
        $response['code'] === 200 && $response['body']['updated'] === true,
        "HTTP {$response['code']}"
    );
} else {
    skipTest("PUT /messages/{id}", "nenhuma mensagem criada");
}

// Test 9: DELETE /messages/{id} - Deletar mensagem
if (isset($createdMessageId)) {
    $response = httpRequest('DELETE', '/messages/' . $createdMessageId, null, $jwtToken);
    
    assertTest(
        "DELETE /messages/{id} - Soft delete",
        $response['code'] === 200 && $response['body']['deleted'] === true,
        "HTTP {$response['code']}"
    );
    
    // Verificar que mensagem não aparece mais em listagens
    $response = httpRequest('GET', '/messages/' . $createdMessageId, null, $jwtToken);
    assertTest(
        "DELETE /messages/{id} - Verifica soft delete",
        $response['code'] === 404,
        "Esperado 404, recebido {$response['code']}"
    );
} else {
    skipTest("DELETE /messages/{id}", "nenhuma mensagem criada");
}

// =============================================================================
// TESTES DE CONTATOS
// =============================================================================

section("TESTES DE CONTATOS");

$testPhone = '+55119998877' . rand(10, 99);

// Test 10: POST /contacts - Criar contato novo
$response = httpRequest('POST', '/contacts', [
    'name' => 'Test User Integration',
    'phone' => $testPhone
], $jwtToken);

$success = assertTest(
    "POST /contacts - Criar novo contato",
    $response['code'] === 201 && isset($response['body']['contact_id']),
    "HTTP {$response['code']}, Body: " . json_encode($response['body'])
);

if ($success) {
    $createdContactId = $response['body']['contact_id'];
}

// Test 11: POST /contacts - Prevenção de duplicatas (mesmo telefone)
$response = httpRequest('POST', '/contacts', [
    'name' => 'Updated Name',
    'phone' => $testPhone
], $jwtToken);

assertTest(
    "POST /contacts - Prevenção de duplicata",
    $response['code'] === 200 && $response['body']['contact_id'] === $createdContactId,
    "HTTP {$response['code']}, deveria retornar contato existente"
);

// Test 12: POST /contacts - Validação de nome inválido
$response = httpRequest('POST', '/contacts', [
    'name' => '',
    'phone' => '+5511999887766'
], $jwtToken);

assertTest(
    "POST /contacts - Validar nome vazio",
    $response['code'] === 422,
    "Esperado 422, recebido {$response['code']}"
);

// Test 13: POST /contacts - Validação de telefone inválido
$response = httpRequest('POST', '/contacts', [
    'name' => 'Test',
    'phone' => '123' // muito curto
], $jwtToken);

assertTest(
    "POST /contacts - Validar telefone inválido",
    $response['code'] === 422,
    "Esperado 422, recebido {$response['code']}"
);

// Test 14: GET /contacts - Listar todos os contatos
$response = httpRequest('GET', '/contacts?page=1&per_page=20', null, $jwtToken);

assertTest(
    "GET /contacts - Listar com paginação",
    $response['code'] === 200 && isset($response['body']['contacts']) && isset($response['body']['pagination']),
    "HTTP {$response['code']}"
);

// Test 15: GET /contacts - Filtrar por nome
$response = httpRequest('GET', '/contacts?name=Test', null, $jwtToken);

assertTest(
    "GET /contacts - Filtrar por nome",
    $response['code'] === 200 && isset($response['body']['contacts']),
    "HTTP {$response['code']}"
);

// Test 16: GET /contacts - Filtrar por telefone
$response = httpRequest('GET', '/contacts?phone=' . urlencode($testPhone), null, $jwtToken);

assertTest(
    "GET /contacts - Filtrar por telefone",
    $response['code'] === 200 && count($response['body']['contacts']) > 0,
    "HTTP {$response['code']}"
);

// Test 17: GET /contacts/{id} - Buscar contato específico
if (isset($createdContactId)) {
    $response = httpRequest('GET', '/contacts/' . $createdContactId, null, $jwtToken);
    
    assertTest(
        "GET /contacts/{id} - Buscar por ID",
        $response['code'] === 200 && $response['body']['id'] === $createdContactId,
        "HTTP {$response['code']}"
    );
} else {
    skipTest("GET /contacts/{id}", "nenhum contato criado");
}

// Test 18: GET /contacts/phone/{phone} - Buscar por telefone
$response = httpRequest('GET', '/contacts/phone/' . urlencode($testPhone), null, $jwtToken);

assertTest(
    "GET /contacts/phone/{phone} - Buscar por telefone",
    $response['code'] === 200 && $response['body']['phone'] === $testPhone,
    "HTTP {$response['code']}"
);

// Test 19: PUT /contacts/{id} - Atualizar contato
if (isset($createdContactId)) {
    $response = httpRequest('PUT', '/contacts/' . $createdContactId, [
        'name' => 'Updated Integration Name',
        'phone' => $testPhone
    ], $jwtToken);
    
    assertTest(
        "PUT /contacts/{id} - Atualizar",
        $response['code'] === 200 && $response['body']['updated'] === true,
        "HTTP {$response['code']}"
    );
} else {
    skipTest("PUT /contacts/{id}", "nenhum contato criado");
}

// Test 20: PUT /contacts/{id} - Validação de nome inválido
if (isset($createdContactId)) {
    $response = httpRequest('PUT', '/contacts/' . $createdContactId, [
        'name' => '',
        'phone' => $testPhone
    ], $jwtToken);
    
    assertTest(
        "PUT /contacts/{id} - Validar nome vazio",
        $response['code'] === 422,
        "Esperado 422, recebido {$response['code']}"
    );
} else {
    skipTest("PUT /contacts/{id} validação", "nenhum contato criado");
}

// Test 21: DELETE /contacts/{id} - Deletar contato
if (isset($createdContactId)) {
    $response = httpRequest('DELETE', '/contacts/' . $createdContactId, null, $jwtToken);
    
    assertTest(
        "DELETE /contacts/{id} - Soft delete",
        $response['code'] === 200 && $response['body']['deleted'] === true,
        "HTTP {$response['code']}"
    );
    
    // Verificar que contato não aparece mais em listagens
    $response = httpRequest('GET', '/contacts/' . $createdContactId, null, $jwtToken);
    assertTest(
        "DELETE /contacts/{id} - Verifica soft delete",
        $response['code'] === 404,
        "Esperado 404, recebido {$response['code']}"
    );
} else {
    skipTest("DELETE /contacts/{id}", "nenhum contato criado");
}

// =============================================================================
// TESTES DE AUTENTICAÇÃO
// =============================================================================

section("TESTES DE AUTENTICAÇÃO");

// Test 22: Requisição sem token
$response = httpRequest('GET', '/contacts', null, null);

assertTest(
    "Autenticação - Requisição sem token",
    $response['code'] === 401,
    "Esperado 401, recebido {$response['code']}"
);

// Test 23: Requisição com token inválido
$response = httpRequest('GET', '/contacts', null, 'invalid_token_xyz');

assertTest(
    "Autenticação - Token inválido",
    $response['code'] === 401,
    "Esperado 401, recebido {$response['code']}"
);

// =============================================================================
// RELATÓRIO FINAL
// =============================================================================

section("RELATÓRIO FINAL");

$total = $testsPassed + $testsFailed + $testsSkipped;
$passRate = $total > 0 ? round(($testsPassed / ($testsPassed + $testsFailed)) * 100, 1) : 0;

echo "Total de testes: $total\n";
echo GREEN . "Passou: $testsPassed" . NC . "\n";
echo RED . "Falhou: $testsFailed" . NC . "\n";
echo YELLOW . "Pulados: $testsSkipped" . NC . "\n";
echo "\nTaxa de sucesso: " . ($passRate >= 90 ? GREEN : ($passRate >= 70 ? YELLOW : RED)) . "$passRate%" . NC . "\n";

if ($testsFailed > 0) {
    echo "\n" . RED . "❌ Alguns testes falharam!" . NC . "\n";
    exit(1);
} else {
    echo "\n" . GREEN . "✅ Todos os testes passaram!" . NC . "\n";
    exit(0);
}
