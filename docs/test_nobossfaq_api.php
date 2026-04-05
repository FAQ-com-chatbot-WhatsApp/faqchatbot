<?php

/**
 * Testes da API No Boss FAQ via cURL (somente linha de comando).
 *
 * Como executar via terminal:
 * 1) Ajuste o bloco $CONFIG (FIXME) abaixo.
 *   php plugins/webservices/nobossfaq/test_nobossfaq_api.php
 *
 * Observações:
 * - Este arquivo é um exemplo para clientes.
 * - O bloco FIXME centraliza todos os parâmetros necessários para os testes.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be executed via CLI.\n";
    exit(1);
}

// FIXME: centralize aqui todos os parâmetros necessários para os testes.
$CONFIG = [
    'baseUrl' => 'https://localhost/nb/extensions/api',
    'token' => '',

    // Se o servidor local não tiver rewrite para API, mantenha true para usar /api/index.php.
    'useIndexPhp' => true,

    // Em produção com certificado válido, altere para true.
    'verifySSL' => false,

    // Filtros padrão para as consultas.
    'state' => 1,
    'language' => 'pt-BR',
    'keywords' => 'pagamento',
    'answer_type' => 'editor',

    // IDs de apoio para os testes. Se deixar 0, o script tenta descobrir automaticamente.
    'group_id' => 0,
    'category_id' => 0,
    'question_id' => 0,

    // Se true, remove no final a pergunta criada no teste POST.
    'cleanupCreatedQuestion' => true,
];

$apiBase = buildApiBase($CONFIG);

echo "Iniciando testes da API No Boss FAQ...\n";
echo "Base URL usada: {$apiBase}\n";

// EXEMPLOS DE CHAMADAS (fluxo principal)

// 1) GET /groups
$groups = endpointGetGroups($apiBase, $CONFIG);
printResult('1) GET /v1/nobossfaq/groups', $groups);

$groupId = resolveGroupId($CONFIG, $groups);

// 2) GET /categories
$categories = endpointGetCategories($apiBase, $CONFIG, $groupId);
printResult('2) GET /v1/nobossfaq/categories', $categories);

$categoryId = resolveCategoryId($CONFIG, $categories);

// 3) GET /questions
$questions = endpointGetQuestions($apiBase, $CONFIG, $groupId, $categoryId);
printResult('3) GET /v1/nobossfaq/questions', $questions);

$questionId = resolveQuestionId($CONFIG, $questions);

// 4) GET /questions/:id
if ($questionId > 0) {
    $oneQuestion = endpointGetQuestionById($apiBase, $CONFIG, $questionId);
    printResult('4) GET /v1/nobossfaq/questions/:id', $oneQuestion);
} else {
    echo "\n4) GET /v1/nobossfaq/questions/:id ignorado (nenhuma pergunta encontrada para usar como exemplo).\n";
}

// 5) POST /questions
$created = endpointCreateQuestion($apiBase, $CONFIG, $groupId, $categoryId);
printResult('5) POST /v1/nobossfaq/questions', $created);

$createdId = null;
if (($created['ok'] ?? false) && !empty($created['body']['data']['id'])) {
    $createdId = (int) $created['body']['data']['id'];
}

// 6) PATCH /questions/:id
if ($createdId !== null) {
    $updated = endpointUpdateQuestion($apiBase, $CONFIG, $createdId);
    printResult('6) PATCH /v1/nobossfaq/questions/:id', $updated);

    // 7) DELETE /questions/:id (opcional)
    if (!empty($CONFIG['cleanupCreatedQuestion'])) {
        $deleted = endpointDeleteQuestion($apiBase, $CONFIG, $createdId);
        printResult('7) DELETE /v1/nobossfaq/questions/:id', $deleted);
    } else {
        echo "\n7) DELETE /v1/nobossfaq/questions/:id ignorado (cleanupCreatedQuestion=false).\n";
    }
} else {
    echo "\n6/7) PATCH e DELETE ignorados (não foi possível criar pergunta na etapa 5).\n";
}

echo "\nTeste finalizado.\n";

/* ========================================================================== */
/* FUNÇÕES DE APOIO E FUNÇÕES DOS ENDPOINTS (mantidas no final, por pedido) */
/* ========================================================================== */

function buildApiBase(array $config): string
{
    $apiBase = rtrim((string) $config['baseUrl'], '/');
    $hasIndexPhp = preg_match('#/index\.php$#i', $apiBase) === 1;

    if (!empty($config['useIndexPhp']) && !$hasIndexPhp) {
        $apiBase .= '/index.php';
    }

    return $apiBase;
}

function resolveGroupId(array $config, array $groupsResult): int
{
    if (!empty($config['group_id'])) {
        return (int) $config['group_id'];
    }

    return (int) ($groupsResult['body']['data']['items'][0]['id'] ?? 0);
}

function resolveCategoryId(array $config, array $categoriesResult): int
{
    if (!empty($config['category_id'])) {
        return (int) $config['category_id'];
    }

    return (int) ($categoriesResult['body']['data']['items'][0]['id'] ?? 0);
}

function resolveQuestionId(array $config, array $questionsResult): int
{
    if (!empty($config['question_id'])) {
        return (int) $config['question_id'];
    }

    return (int) ($questionsResult['body']['data']['items'][0]['id'] ?? 0);
}

function endpointGetGroups(string $apiBase, array $config): array
{
    $query = http_build_query([
        'state' => (int) ($config['state'] ?? 1),
        'language' => (string) ($config['language'] ?? 'pt-BR'),
    ]);

    return apiRequest('GET', $apiBase . '/v1/nobossfaq/groups?' . $query, $config);
}

function endpointGetCategories(string $apiBase, array $config, int $groupId): array
{
    $params = [
        'language' => (string) ($config['language'] ?? 'pt-BR'),
    ];

    if ($groupId > 0) {
        $params['group_id'] = $groupId;
    }

    return apiRequest('GET', $apiBase . '/v1/nobossfaq/categories?' . http_build_query($params), $config);
}

function endpointGetQuestions(string $apiBase, array $config, int $groupId, int $categoryId): array
{
    $params = [
        'keywords' => (string) ($config['keywords'] ?? ''),
        'answer_type' => (string) ($config['answer_type'] ?? ''),
        'language' => (string) ($config['language'] ?? 'pt-BR'),
    ];

    if ($groupId > 0) {
        $params['group_id'] = $groupId;
    }

    if ($categoryId > 0) {
        $params['category_id'] = $categoryId;
    }

    $params = array_filter($params, static fn($value) => $value !== '');

    return apiRequest('GET', $apiBase . '/v1/nobossfaq/questions?' . http_build_query($params), $config);
}

function endpointGetQuestionById(string $apiBase, array $config, int $questionId): array
{
    return apiRequest('GET', $apiBase . '/v1/nobossfaq/questions/' . $questionId, $config);
}

function endpointCreateQuestion(string $apiBase, array $config, int $groupId, int $categoryId): array
{
    $payload = [
        'question' => 'Teste API - ' . date('Y-m-d H:i:s'),
        'id_faqs_group' => $groupId,
        'id_category' => $categoryId,
        'answer' => 'Resposta criada automaticamente via script de teste.',
        'state' => (int) ($config['state'] ?? 1),
        'language' => (string) ($config['language'] ?? 'pt-BR'),
    ];

    return apiRequest('POST', $apiBase . '/v1/nobossfaq/questions', $config, $payload);
}

function endpointUpdateQuestion(string $apiBase, array $config, int $questionId): array
{
    $payload = [
        'question' => 'Teste API atualizado - ' . date('Y-m-d H:i:s'),
        'answer' => 'Resposta atualizada automaticamente via script de teste.',
        'language' => (string) ($config['language'] ?? 'pt-BR'),
    ];

    return apiRequest('PATCH', $apiBase . '/v1/nobossfaq/questions/' . $questionId, $config, $payload);
}

function endpointDeleteQuestion(string $apiBase, array $config, int $questionId): array
{
    return apiRequest('DELETE', $apiBase . '/v1/nobossfaq/questions/' . $questionId, $config);
}

function apiRequest(string $method, string $url, array $config, ?array $body = null): array
{
    $ch = curl_init($url);

    $headers = [
        'Accept: application/json, application/vnd.api+json',
        'Authorization: Bearer ' . (string) ($config['token'] ?? ''),
    ];

    if ($body !== null) {
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $verifySSL = !empty($config['verifySSL']);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => $verifySSL,
        CURLOPT_SSL_VERIFYHOST => $verifySSL ? 2 : 0,
    ]);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($errno) {
        return [
            'ok' => false,
            'status' => 0,
            'error' => "Erro cURL ({$errno}): {$error}",
            'body' => null,
            'raw' => null,
        ];
    }

    $decoded = json_decode((string) $response, true);

    return [
        'ok' => $httpCode >= 200 && $httpCode < 300,
        'status' => $httpCode,
        'error' => null,
        'body' => $decoded,
        'raw' => $response,
    ];
}

function printResult(string $title, array $result): void
{
    echo "\n============================================================\n";
    echo $title . "\n";
    echo "Status HTTP: " . ($result['status'] ?? 0) . "\n";

    if (!empty($result['error'])) {
        echo "Erro: " . $result['error'] . "\n";
        return;
    }

    if (is_array($result['body'] ?? null)) {
        echo json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        return;
    }

    echo (string) ($result['raw'] ?? '') . "\n";
}
