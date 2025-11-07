<?php
declare(strict_types=1);

// API mínima sobre Joomla seguindo KISS/DRY/SOLID

// Bootstrap app (.env + autoload)
$bootstrap = require_once dirname(__DIR__) . '/config/bootstrap.php';

use App\Api\Controllers\HealthController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Respect\Validation\Validator as v;

// ---------- Utilidades ----------
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function enableCors(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
}

function handlePreflight(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        enableCors();
        http_response_code(204);
        exit;
    }
}

function getDbConfig(): array {
    // Prioriza .env
    $env = [
        'host' => $_ENV['DB_HOST'] ?? null,
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        'name' => $_ENV['DB_DATABASE'] ?? null,
        'user' => $_ENV['DB_USERNAME'] ?? null,
        'pass' => $_ENV['DB_PASSWORD'] ?? null,
        'prefix' => $_ENV['DB_PREFIX'] ?? null,
    ];
    if ($env['host'] && $env['name'] && $env['user'] !== null) {
        // Tentativa de obter prefixo do configuration.php se existir
        $confFile = dirname(__DIR__) . '/configuration.php';
        if (is_file($confFile)) {
            require_once $confFile;
            if (class_exists('Configuration')) {
                $cfg = new Configuration();
                $env['prefix'] = $cfg->dbprefix ?? $env['prefix'];
            }
        }
        return $env;
    }

    // Fallback: tentar ler configuration.php do Joomla
    $confFile = dirname(__DIR__) . '/configuration.php';
    if (is_file($confFile)) {
        require_once $confFile;
        if (class_exists('Configuration')) {
            $cfg = new Configuration();
            return [
                'host' => $cfg->host ?? 'localhost',
                'port' => 3306,
                'name' => $cfg->db ?? '',
                'user' => $cfg->user ?? '',
                'pass' => $cfg->password ?? '',
                'prefix' => $cfg->dbprefix ?? '',
            ];
        }
    }

    return $env; // pode conter nulls se nada encontrado
}

function ensureStorageDir(): string {
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function rateLimitAuth(string $ip): bool {
    // Limite simples por IP para /auth/login
    $limit = (int)($_ENV['RATE_LIMIT_AUTH'] ?? 10); // padrão 10
    $window = 300; // 5 minutos
    $file = ensureStorageDir() . '/ratelimit_auth.json';
    $data = [];
    if (is_file($file)) {
        $raw = file_get_contents($file);
        if ($raw) {
            $json = json_decode($raw, true);
            if (is_array($json)) $data = $json;
        }
    }
    $now = time();
    $entry = $data[$ip] ?? ['count' => 0, 'start' => $now];
    if ($now - $entry['start'] > $window) {
        $entry = ['count' => 0, 'start' => $now];
    }
    $entry['count']++;
    $data[$ip] = $entry;
    // Persistir de forma best-effort
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
    return $entry['count'] <= $limit;
}

function fetchUserRoles(PDO $pdo, int $userId): array {
    $cfg = getDbConfig();
    $prefix = $cfg['prefix'] ?? '';
    if ($prefix && !str_ends_with($prefix, '_')) {
        $prefix .= '_';
    }
    $mapTable = sprintf('`%suser_usergroup_map`', $prefix);
    $groupsTable = sprintf('`%susergroups`', $prefix);
    $sql = "SELECT g.title FROM $mapTable m JOIN $groupsTable g ON g.id = m.group_id WHERE m.user_id = :uid";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        // Normalizar títulos em slugs simples para roles
        $roles = [];
        foreach ($rows as $title) {
            $r = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', trim($title)) ?? '');
            if ($r !== '') $roles[] = $r;
        }
        if (!$roles) $roles = ['USER'];
        return array_values(array_unique($roles));
    } catch (Throwable $e) {
        return ['USER'];
    }
}

function pdo(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;
    $cfg = getDbConfig();
    if (!$cfg['host'] || !$cfg['name'] || $cfg['user'] === null) {
        throw new RuntimeException('Configuração de banco ausente. Verifique .env ou configuration.php');
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['name']);
    $pdo = new PDO($dsn, strval($cfg['user']), strval($cfg['pass']), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}

function usersTable(): string {
    $prefix = getDbConfig()['prefix'] ?? '';
    if ($prefix && !str_ends_with($prefix, '_')) {
        $prefix .= '_';
    }
    // Joomla usa #__users como alias, mas no banco real é <prefix>users
    return sprintf('`%susers`', $prefix);
}

function makeJwt(array $claims): string {
    $secret = $_ENV['JWT_SECRET'] ?? '';
    if ($secret === '') {
        throw new RuntimeException('JWT_SECRET não definido');
    }
    $ttl = (int)($_ENV['JWT_TTL'] ?? 900);
    $now = time();
    $payload = array_merge([
        'iss' => $_ENV['JWT_ISSUER'] ?? 'faqchatbot-api',
        'iat' => $now,
        'nbf' => $now,
        'exp' => $now + $ttl,
    ], $claims);
    return JWT::encode($payload, $secret, 'HS256');
}

function parsePath(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    // remover prefixo /api
    if (str_starts_with($path, '/api')) {
        $path = substr($path, 4) ?: '/';
    }
    return $path;
}

function requireAuth(): array {
    // Middleware JWT: valida token e retorna claims
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($authHeader, 'Bearer ')) {
        jsonResponse(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Token ausente']], 401);
        exit;
    }
    $token = substr($authHeader, 7);
    $secret = $_ENV['JWT_SECRET'] ?? '';
    if ($secret === '') {
        jsonResponse(['error' => ['code' => 'CONFIG_ERROR', 'message' => 'Configuração JWT ausente']], 500);
        exit;
    }
    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        return (array)$decoded;
    } catch (Throwable $e) {
        jsonResponse(['error' => ['code' => 'INVALID_TOKEN', 'message' => 'Token inválido ou expirado']], 401);
        exit;
    }
}

// ---------- CORS e preflight ----------
enableCors();
handlePreflight();

// ---------- Roteamento mínimo ----------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parsePath();

try {
    if ($method === 'GET' && $path === '/health/ping') {
        $ctrl = new HealthController();
        jsonResponse($ctrl->ping());
        return;
    }

    // (Removido bloco duplicado de listagem de flows - ver seção consolidada mais abaixo)

    if ($method === 'POST' && $path === '/auth/login') {
        $body = readJsonBody();
        $username = trim(strval($body['username'] ?? ''));
        $password = strval($body['password'] ?? '');

        // Rate limiting por IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!rateLimitAuth($ip)) {
            jsonResponse(['error' => ['code' => 'RATE_LIMIT', 'message' => 'Muitas tentativas de login. Aguarde.']], 429);
            return;
        }

        // validação simples (KISS)
        if (!v::alnum('-_.@')->noWhitespace()->length(3, 150)->validate($username)) {
            jsonResponse(['error' => ['code' => 'INVALID_USERNAME', 'message' => 'Usuário inválido']], 422);
            return;
        }
        if (!v::stringType()->length(1, null)->validate($password)) {
            jsonResponse(['error' => ['code' => 'INVALID_PASSWORD', 'message' => 'Senha inválida']], 422);
            return;
        }

        $pdo = pdo();
        $sql = 'SELECT id, username, name, email, password, block FROM ' . usersTable() . ' WHERE username = :u LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();
        if (!$user || (int)$user['block'] === 1) {
            jsonResponse(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Credenciais inválidas']], 401);
            return;
        }
        if (!password_verify($password, strval($user['password']))) {
            jsonResponse(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Credenciais inválidas']], 401);
            return;
        }

        $roles = fetchUserRoles($pdo, (int)$user['id']);
        $token = makeJwt([
            'sub' => (string)$user['id'],
            'username' => (string)$user['username'],
            'roles' => $roles,
        ]);

        jsonResponse([
            'access_token' => $token,
            'expires_in' => (int)($_ENV['JWT_TTL'] ?? 900),
            'token_type' => 'Bearer',
        ]);
        return;
    }

    // ---------------- Conversações ----------------
    // Criar conversa
    if ($method === 'POST' && $path === '/conversations') {
        $auth = requireAuth();
        $body = readJsonBody();

        $contactPhone = trim(strval($body['contact_phone'] ?? ''));
        $contactName  = trim(strval($body['contact_name'] ?? ''));
        $flowId       = trim(strval($body['flow_id'] ?? ''));

        // Validações
        // Telefone simples: aceitar +, dígitos, espaços e hífen (evitar rejeitar números válidos internacionais)
        if (!preg_match('/^\+?[0-9][0-9\-\s]{5,31}$/', $contactPhone)) {
            jsonResponse(['error' => ['code' => 'INVALID_PHONE', 'message' => 'Telefone inválido']], 422);
            return;
        }
        if (!v::stringType()->length(1, 191)->validate($contactName)) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome inválido']], 422);
            return;
        }
        if (!v::uuid()->validate($flowId)) {
            jsonResponse(['error' => ['code' => 'INVALID_FLOW', 'message' => 'Flow ID inválido (UUID esperado)']], 422);
            return;
        }

        $pdo = pdo();
        // Verificar flow ativo
        $stmt = $pdo->prepare('SELECT id FROM flows WHERE id = :id AND status = "active" LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'FLOW_NOT_FOUND', 'message' => 'Flow não encontrado ou inativo']], 404);
            return;
        }

        // Buscar contato existente
        $stmt = $pdo->prepare('SELECT id FROM contacts WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $contactPhone]);
        $contact = $stmt->fetch();

        if ($contact) {
            $contactId = $contact['id'];
            // Atualizar nome se mudou
            $pdo->prepare('UPDATE contacts SET name = :name, updated_at = NOW() WHERE id = :id')
                ->execute([':name' => $contactName, ':id' => $contactId]);
        } else {
            $contactId = \Ramsey\Uuid\Uuid::uuid4()->toString();
            $stmt = $pdo->prepare('INSERT INTO contacts (id, name, phone, created_at, updated_at) VALUES (:id, :name, :phone, NOW(), NOW())');
            $stmt->execute([':id' => $contactId, ':name' => $contactName, ':phone' => $contactPhone]);
        }

        // Criar conversa
        $conversationId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $stmt = $pdo->prepare('INSERT INTO conversations (id, contact_id, flow_id, status, message_count, created_at, updated_at) VALUES (:id, :contact_id, :flow_id, :status, 0, NOW(), NOW())');
        $stmt->execute([
            ':id' => $conversationId,
            ':contact_id' => $contactId,
            ':flow_id' => $flowId,
            ':status' => 'active',
        ]);

        jsonResponse([
            'conversation_id' => $conversationId,
            'contact_id' => $contactId,
            'flow_id' => $flowId,
            'status' => 'active',
        ], 201);
        return;
    }

    // Inserir mensagem
    if ($method === 'POST' && $path === '/messages') {
        $auth = requireAuth();
        $body = readJsonBody();

        $conversationId = trim(strval($body['conversation_id'] ?? ''));
        $sender         = trim(strval($body['sender'] ?? ''));
        // Novo formato: content pode ser string ou objeto: { "body": "..." }
        $contentRaw     = $body['content'] ?? null;
        $type           = trim(strval($body['type'] ?? 'text'));
        $metadata       = $body['metadata'] ?? null;

        if (!v::uuid()->validate($conversationId)) {
            jsonResponse(['error' => ['code' => 'INVALID_CONVERSATION', 'message' => 'ID de conversa inválido']], 422);
            return;
        }
        if (!in_array($sender, ['user','bot'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_SENDER', 'message' => 'Sender deve ser user ou bot']], 422);
            return;
        }

        // Normalizar conteúdo
        $contentArr = null;
        if (is_string($contentRaw)) {
            $textContent = trim($contentRaw);
            if (!v::stringType()->length(1, 5000)->validate($textContent)) {
                jsonResponse(['error' => ['code' => 'INVALID_CONTENT', 'message' => 'Conteúdo inválido']], 422);
                return;
            }
            $contentArr = ['body' => $textContent];
        } elseif (is_array($contentRaw)) {
            // Esperamos ao menos content.body quando type=text
            if ($type === 'text') {
                $bodyText = isset($contentRaw['body']) ? trim(strval($contentRaw['body'])) : null;
                if (!v::stringType()->length(1, 5000)->validate($bodyText)) {
                    jsonResponse(['error' => ['code' => 'INVALID_CONTENT', 'message' => 'Content.body inválido']], 422);
                    return;
                }
            }
            $contentArr = $contentRaw;
        } else {
            jsonResponse(['error' => ['code' => 'INVALID_CONTENT', 'message' => 'Content obrigatório']], 422);
            return;
        }

        // Mesclar metadata se fornecido (no modelo solicitado metadata vem separada)
        if (is_array($metadata) && $metadata !== []) {
            $contentArr['metadata'] = $metadata;
        }

        $pdo = pdo();
        // Verificar conversa
    $stmt = $pdo->prepare('SELECT id, message_count, contact_id FROM conversations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        $conversation = $stmt->fetch();
        if (!$conversation) {
            jsonResponse(['error' => ['code' => 'CONVERSATION_NOT_FOUND', 'message' => 'Conversa não encontrada']], 404);
            return;
        }

        $direction = $sender === 'user' ? 'in' : 'out';
        $messageId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $contentJson = json_encode($contentArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $pdo->prepare('INSERT INTO messages (id, conversation_id, direction, type, content, status, created_at) VALUES (:id, :conversation_id, :direction, :type, :content, :status, NOW())');
        $stmt->execute([
            ':id' => $messageId,
            ':conversation_id' => $conversationId,
            ':direction' => $direction,
            ':type' => $type,
            ':content' => $contentJson,
            ':status' => 'processed',
        ]);

        // Atualizar conversa (contagem + last_message_id + updated_at)
        $pdo->prepare('UPDATE conversations SET message_count = message_count + 1, last_message_id = :mid, updated_at = NOW() WHERE id = :id')
            ->execute([':mid' => $messageId, ':id' => $conversationId]);

        // Atualizar last interaction do contato (updated_at) se possível
        if (!empty($conversation['contact_id'])) {
            try {
                $pdo->prepare('UPDATE contacts SET updated_at = NOW() WHERE id = :id')
                    ->execute([':id' => $conversation['contact_id']]);
            } catch (Throwable $e) {
                // não bloquear a inserção se falhar ao atualizar contato
            }
        }

        jsonResponse([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'direction' => $direction,
            'type' => 'text',
            'content' => ['text' => $textContent],
            'status' => 'processed'
        ], 201);
        return;
    }

    // ---------------- Messages (CRUD) ----------------
    // Listar messages (com filtros opcionais)
    if ($method === 'GET' && $path === '/messages') {
        $auth = requireAuth();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $conversationId = trim(strval($_GET['conversation_id'] ?? ''));
    $direction = trim(strval($_GET['direction'] ?? ''));
    $typeFilter = trim(strval($_GET['type'] ?? ''));
    $statusFilter = trim(strval($_GET['status'] ?? ''));

        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 200) $perPage = 20;

        $pdo = pdo();
        $where = [];
        $params = [];
        if ($conversationId !== '' && v::uuid()->validate($conversationId)) {
            $where[] = 'conversation_id = :conversation_id';
            $params[':conversation_id'] = $conversationId;
        }
        if ($direction !== '' && in_array($direction, ['in','out'], true)) {
            $where[] = 'direction = :direction';
            $params[':direction'] = $direction;
        }
        if ($typeFilter !== '') {
            $where[] = 'type = :type';
            $params[':type'] = $typeFilter;
        }
        if ($statusFilter !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $statusFilter;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        // Se existir coluna deleted_at, filtrar registros soft-deleted
        try {
            $col = $pdo->query("SHOW COLUMNS FROM messages LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $col = false;
        }
        if ($col) {
            $whereClause = $whereClause === '' ? 'WHERE deleted_at IS NULL' : ($whereClause . ' AND deleted_at IS NULL');
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages $whereClause");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['total'];

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT id, conversation_id, direction, type, content, status, created_at FROM messages $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $messages = $stmt->fetchAll();
        foreach ($messages as &$m) {
            $decoded = json_decode($m['content'], true);
            if (json_last_error() === JSON_ERROR_NONE) $m['content'] = $decoded;
        }
        unset($m);

        jsonResponse(['messages' => $messages, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => ceil($total / $perPage)]]);
        return;
    }

    // Obter message por id
    if ($method === 'GET' && preg_match('#^/messages/([a-f0-9\\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $id = $m[1];
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id, conversation_id, direction, type, content, status, created_at FROM messages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $msg = $stmt->fetch();
        if (!$msg) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Message não encontrado']], 404);
            return;
        }
        $decoded = json_decode($msg['content'], true);
        if (json_last_error() === JSON_ERROR_NONE) $msg['content'] = $decoded;
        jsonResponse($msg);
        return;
    }

    // Substituir/atualizar message (PUT)
    if ($method === 'PUT' && preg_match('#^/messages/([a-f0-9\\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $id = $m[1];
        $body = readJsonBody();
        $content = $body['content'] ?? null;
        $type = trim(strval($body['type'] ?? 'text'));
        $status = trim(strval($body['status'] ?? ''));

        if ($content === null) {
            jsonResponse(['error' => ['code' => 'INVALID_CONTENT', 'message' => 'Content obrigatório']], 422);
            return;
        }

        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id FROM messages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Message não encontrado']], 404);
            return;
        }

        $contentJson = is_string($content) ? json_encode(['text' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $pdo->prepare('UPDATE messages SET content = :content, type = :type, status = :status WHERE id = :id');
        $stmt->execute([':content' => $contentJson, ':type' => $type, ':status' => $status, ':id' => $id]);
        jsonResponse(['updated' => true, 'message_id' => $id]);
        return;
    }

    // Remover message
    if ($method === 'DELETE' && preg_match('#^/messages/([a-f0-9\\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $id = $m[1];
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id FROM messages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Message não encontrado']], 404);
            return;
        }
        // Prefer soft-delete if coluna deleted_at existe
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM messages LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $colCheck = false;
        }
        if ($colCheck) {
            $stmt = $pdo->prepare('UPDATE messages SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM messages WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }
        jsonResponse(['deleted' => true, 'message_id' => $id]);
        return;
    }

    // Buscar conversa + mensagens
    if ($method === 'GET' && preg_match('#^/conversations/([a-f0-9\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $conversationId = $m[1];

        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT c.id, c.status, c.created_at, c.updated_at, c.message_count, c.last_message_id, co.id AS contact_id, co.phone, co.name AS contact_name, f.id AS flow_id, f.name AS flow_name FROM conversations c LEFT JOIN contacts co ON c.contact_id = co.id LEFT JOIN flows f ON c.flow_id = f.id WHERE c.id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        $conversation = $stmt->fetch();
        if (!$conversation) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Conversa não encontrada']], 404);
            return;
        }

        $stmt = $pdo->prepare('SELECT id, direction, type, content, status, created_at FROM messages WHERE conversation_id = :id ORDER BY created_at ASC');
        $stmt->execute([':id' => $conversationId]);
        $messages = $stmt->fetchAll();
        foreach ($messages as &$msg) {
            $decoded = json_decode($msg['content'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $msg['content'] = $decoded;
            }
        }
        unset($msg);

        jsonResponse([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
        return;
    }

    // Listar conversas com paginação e filtros
    if ($method === 'GET' && $path === '/conversations') {
        $auth = requireAuth();

        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 10);
        $status = trim(strval($_GET['status'] ?? ''));
        $contactPhone = trim(strval($_GET['contact_phone'] ?? ''));

        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 100) $perPage = 10;

        $pdo = pdo();
        $where = [];
        $params = [];

        if ($status !== '' && in_array($status, ['active', 'completed', 'abandoned'], true)) {
            $where[] = 'c.status = :status';
            $params[':status'] = $status;
        }
        if ($contactPhone !== '') {
            $where[] = 'co.phone LIKE :phone';
            $params[':phone'] = '%' . $contactPhone . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Se existir coluna deleted_at em contacts ou flows, filtrar registros soft-deleted
        try {
            $colCo = $pdo->query("SHOW COLUMNS FROM contacts LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $colCo = false;
        }
        try {
            $colF = $pdo->query("SHOW COLUMNS FROM flows LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $colF = false;
        }
        if ($colCo) {
            $whereClause = $whereClause === '' ? 'WHERE co.deleted_at IS NULL' : ($whereClause . ' AND co.deleted_at IS NULL');
        }
        if ($colF) {
            $whereClause = $whereClause === '' ? 'WHERE f.deleted_at IS NULL' : ($whereClause . ' AND f.deleted_at IS NULL');
        }

        // Contagem total
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM conversations c LEFT JOIN contacts co ON c.contact_id = co.id LEFT JOIN flows f ON c.flow_id = f.id $whereClause");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['total'];

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT c.id, c.status, c.created_at, c.updated_at, c.message_count, c.last_message_id, co.id AS contact_id, co.phone, co.name AS contact_name, f.id AS flow_id, f.name AS flow_name FROM conversations c LEFT JOIN contacts co ON c.contact_id = co.id LEFT JOIN flows f ON c.flow_id = f.id $whereClause ORDER BY c.updated_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $conversations = $stmt->fetchAll();

        jsonResponse([
            'conversations' => $conversations,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ]);
        return;
    }

    // ---------------- Contacts ----------------
    // Listar contacts
    if ($method === 'GET' && $path === '/contacts') {
        $auth = requireAuth();
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $phone = trim(strval($_GET['phone'] ?? ''));
        $name = trim(strval($_GET['name'] ?? ''));

        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 200) $perPage = 20;

        $pdo = pdo();
        $where = [];
        $params = [];
        if ($phone !== '') {
            $where[] = 'phone LIKE :phone';
            $params[':phone'] = '%' . $phone . '%';
        }
        if ($name !== '') {
            $where[] = 'name LIKE :name';
            $params[':name'] = '%' . $name . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        // Se existir coluna deleted_at, filtrar registros soft-deleted
        try {
            $col = $pdo->query("SHOW COLUMNS FROM contacts LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $col = false;
        }
        if ($col) {
            $whereClause = $whereClause === '' ? 'WHERE deleted_at IS NULL' : ($whereClause . ' AND deleted_at IS NULL');
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contacts $whereClause");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['total'];

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT id, name, phone, created_at, updated_at FROM contacts $whereClause ORDER BY updated_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $contacts = $stmt->fetchAll();

        jsonResponse(['contacts' => $contacts, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => ceil($total / $perPage)]]);
        return;
    }

    // Criar contact
    if ($method === 'POST' && $path === '/contacts') {
        $auth = requireAuth();
        $body = readJsonBody();
        $name = trim(strval($body['name'] ?? ''));
        $phone = trim(strval($body['phone'] ?? ''));

        if (!v::stringType()->length(1,191)->validate($name)) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome inválido']], 422);
            return;
        }
        if (!preg_match('/^\+?[0-9][0-9\-\s]{5,31}$/', $phone)) {
            jsonResponse(['error' => ['code' => 'INVALID_PHONE', 'message' => 'Telefone inválido']], 422);
            return;
        }

        $pdo = pdo();
        // Evitar duplicatas por telefone: se existir, retornar existente (200) e atualizar nome se diferente
        $stmt = $pdo->prepare('SELECT id, name, phone FROM contacts WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);
        $existing = $stmt->fetch();
        if ($existing) {
            // Atualizar nome se necessário
            if ($existing['name'] !== $name) {
                $pdo->prepare('UPDATE contacts SET name = :name, updated_at = NOW() WHERE id = :id')
                    ->execute([':name' => $name, ':id' => $existing['id']]);
            }
            jsonResponse(['contact_id' => $existing['id'], 'name' => $name, 'phone' => $phone], 200);
            return;
        }

        $id = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $stmt = $pdo->prepare('INSERT INTO contacts (id, name, phone, created_at, updated_at) VALUES (:id, :name, :phone, NOW(), NOW())');
        $stmt->execute([':id' => $id, ':name' => $name, ':phone' => $phone]);
        jsonResponse(['contact_id' => $id, 'name' => $name, 'phone' => $phone], 201);
        return;
    }

    // Obter contact por id
    if ($method === 'GET' && preg_match('#^/contacts/([a-f0-9\\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $id = $m[1];
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id, name, phone, created_at, updated_at FROM contacts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $contact = $stmt->fetch();
        if (!$contact) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Contact não encontrado']], 404);
            return;
        }
        jsonResponse($contact);
        return;
    }

    // Remover contact (soft-delete if available)
    if ($method === 'DELETE' && preg_match('#^/contacts/([a-f0-9\\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $id = $m[1];
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id FROM contacts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Contact não encontrado']], 404);
            return;
        }
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM contacts LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $colCheck = false;
        }
        if ($colCheck) {
            $stmt = $pdo->prepare('UPDATE contacts SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM contacts WHERE id = :id');
            $stmt->execute([':id' => $id]);
        }
        jsonResponse(['deleted' => true, 'contact_id' => $id]);
        return;
    }

    // Atualizar contact
    if ($method === 'PUT' && preg_match('#^/contacts/([a-f0-9\\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $id = $m[1];
        $body = readJsonBody();
        $name = trim(strval($body['name'] ?? ''));
        $phone = trim(strval($body['phone'] ?? ''));

        if (!v::stringType()->length(1,191)->validate($name)) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome inválido']], 422);
            return;
        }
        if (!preg_match('/^\+?[0-9][0-9\-\s]{5,31}$/', $phone)) {
            jsonResponse(['error' => ['code' => 'INVALID_PHONE', 'message' => 'Telefone inválido']], 422);
            return;
        }

        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id FROM contacts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Contact não encontrado']], 404);
            return;
        }
        $stmt = $pdo->prepare('UPDATE contacts SET name = :name, phone = :phone, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':name' => $name, ':phone' => $phone, ':id' => $id]);
        jsonResponse(['updated' => true, 'contact_id' => $id]);
        return;
    }

    // Buscar contact por telefone (URL-encoded)
    if ($method === 'GET' && preg_match('#^/contacts/phone/(.+)$#', $path, $m)) {
        $auth = requireAuth();
        $phoneRaw = rawurldecode($m[1]);
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id, name, phone, created_at, updated_at FROM contacts WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phoneRaw]);
        $contact = $stmt->fetch();
        if (!$contact) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Contact não encontrado']], 404);
            return;
        }
        jsonResponse($contact);
        return;
    }

    // Identificar conversa ativa por telefone
    if ($method === 'GET' && preg_match('#^/conversations/active/phone/(.+)$#', $path, $m)) {
        $auth = requireAuth();
        $phone = rawurldecode($m[1]);
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT c.id, c.status, c.current_step, c.message_count, c.updated_at, c.created_at, co.id as contact_id, co.name as contact_name, co.phone, f.id as flow_id, f.name as flow_name FROM conversations c JOIN contacts co ON co.id = c.contact_id LEFT JOIN flows f ON f.id = c.flow_id WHERE co.phone = :phone AND c.status = "active" ORDER BY c.updated_at DESC LIMIT 1');
        $stmt->execute([':phone' => $phone]);
        $row = $stmt->fetch();
        if (!$row) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Conversa ativa não encontrada para este telefone']], 404);
            return;
        }
        jsonResponse(['conversation' => $row]);
        return;
    }

    // ---------------- Conversations: next step ----------------
    if ($method === 'POST' && preg_match('#^/conversations/([a-f0-9\\-]{36})/next$#', $path, $m)) {
        $auth = requireAuth();
        $conversationId = $m[1];
        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id, flow_id, message_count, current_step, updated_at, status FROM conversations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        $conv = $stmt->fetch();
        if (!$conv) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Conversa não encontrada']], 404);
            return;
        }
        $stmt = $pdo->prepare('SELECT definition FROM flows WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conv['flow_id']]);
        $flow = $stmt->fetch();
        if (!$flow) {
            jsonResponse(['error' => ['code' => 'FLOW_NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }
        $def = json_decode($flow['definition'] ?? 'null', true);
        $steps = is_array($def) && isset($def['steps']) && is_array($def['steps']) ? $def['steps'] : [];

        // Se as steps usam campo 'order', ordenar por ele (compatibilidade com modelo solicitado)
        $hasOrder = false;
        foreach ($steps as $s) {
            if (is_array($s) && array_key_exists('order', $s)) { $hasOrder = true; break; }
        }
        if ($hasOrder) {
            usort($steps, function($a, $b) {
                $oa = isset($a['order']) ? (int)$a['order'] : 0;
                $ob = isset($b['order']) ? (int)$b['order'] : 0;
                return $oa <=> $ob;
            });
        }

        // Próximo índice baseado no estado persistido
        $nextIndex = max(0, (int)($conv['current_step'] ?? 0));
        $nextStep = $steps[$nextIndex] ?? null;
        $completed = $nextStep === null;

        // Timeout de conversa: se não houve atualização em 24h, marcar como abandoned e retornar
        $updatedAtRaw = $conv['updated_at'] ?? null;
        $updatedAt = $updatedAtRaw ? strtotime($updatedAtRaw) : null;
        if ($updatedAt !== null && (time() - $updatedAt) > 86400) {
            try {
                $pdo->prepare('UPDATE conversations SET status = "abandoned", updated_at = NOW() WHERE id = :id')
                    ->execute([':id' => $conversationId]);
            } catch (Throwable $e) {
                // ignore
            }
            jsonResponse(['conversation_id' => $conversationId, 'next_step' => null, 'completed' => true, 'status' => 'abandoned']);
            return;
        }

        // Se a step referencia uma message via message_id, expandir o conteúdo da mensagem
        if ($nextStep !== null && is_array($nextStep) && !empty($nextStep['message_id'])) {
            try {
                $stmt = $pdo->prepare('SELECT id, type, content, status, created_at FROM messages WHERE id = :id LIMIT 1');
                $stmt->execute([':id' => $nextStep['message_id']]);
                $msgRow = $stmt->fetch();
                if ($msgRow) {
                    $decoded = json_decode($msgRow['content'], true);
                    if (json_last_error() === JSON_ERROR_NONE) $msgRow['content'] = $decoded;
                    $nextStep['message'] = $msgRow;
                }
            } catch (Throwable $e) {
                // não bloquear se falhar ao buscar a message; retornar step sem expansão
            }
        }
        // Se completou (não há next step), finalizar a conversa e tentar logar transição
        if ($completed) {
            try {
                $pdo->prepare('UPDATE conversations SET status = "completed", updated_at = NOW() WHERE id = :id')
                    ->execute([':id' => $conversationId]);
            } catch (Throwable $e) {
                // ignore
            }
            // Logar transição em conversation_transitions se tabela existir (best-effort)
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'conversation_transitions'")->fetch();
                if ($tableCheck) {
                    $ins = $pdo->prepare('INSERT INTO conversation_transitions (conversation_id, from_step, to_step, step_id, action, payload) VALUES (:cid, :from, :to, :step_id, :action, :payload)');
                    $ins->execute([':cid' => $conversationId, ':from' => $nextIndex, ':to' => null, ':step_id' => null, ':action' => 'complete', ':payload' => json_encode($nextStep)]);
                }
            } catch (Throwable $e) {
                // não bloquear
            }
        } else {
            // Avançar estado: current_step + 1, message_count + 1 e logar transição
            try {
                $pdo->prepare('UPDATE conversations SET current_step = current_step + 1, message_count = message_count + 1, updated_at = NOW() WHERE id = :id')
                    ->execute([':id' => $conversationId]);
            } catch (Throwable $e) {
                // ignorar falha de atualização, mas tentar prosseguir
            }
            // Logar transição "next"
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'conversation_transitions'")->fetch();
                if ($tableCheck) {
                    $stepId = is_array($nextStep) && isset($nextStep['id']) ? strval($nextStep['id']) : null;
                    $ins = $pdo->prepare('INSERT INTO conversation_transitions (conversation_id, from_step, to_step, step_id, action, payload) VALUES (:cid, :from, :to, :step_id, :action, :payload)');
                    $ins->execute([':cid' => $conversationId, ':from' => $nextIndex, ':to' => $nextIndex + 1, ':step_id' => $stepId, ':action' => 'next', ':payload' => json_encode($nextStep)]);
                }
            } catch (Throwable $e) {
                // não bloquear
            }
        }

        jsonResponse(['conversation_id' => $conversationId, 'next_step' => $nextStep, 'completed' => $completed, 'status' => $completed ? 'completed' : ($conv['status'] ?? 'active')]);
        return;
    }

    // Atualizar status da conversa
    if ($method === 'PATCH' && preg_match('#^/conversations/([a-f0-9\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $conversationId = $m[1];

        $body = readJsonBody();
        $newStatus = trim(strval($body['status'] ?? ''));

        if (!in_array($newStatus, ['active', 'completed', 'abandoned'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_STATUS', 'message' => 'Status deve ser active, completed ou abandoned']], 422);
            return;
        }

        $pdo = pdo();
        $stmt = $pdo->prepare('UPDATE conversations SET status = :status, updated_at = NOW() WHERE id = :id');
        $affected = $stmt->execute([':status' => $newStatus, ':id' => $conversationId]);

        if ($affected === 0) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Conversa não encontrada']], 404);
            return;
        }

        jsonResponse(['status' => $newStatus, 'updated' => true]);
        return;
    }

    // ---------------- Flows ----------------
    // Listar flows com paginação e filtros
    if ($method === 'GET' && $path === '/flows') {
        $auth = requireAuth();

        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 10);
        $status = trim(strval($_GET['status'] ?? ''));
        $name = trim(strval($_GET['name'] ?? ''));

        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 100) $perPage = 10;

        $pdo = pdo();
        $where = [];
        $params = [];

        // aceitar 'inactive' como alias de 'archived'
        if ($status === 'inactive') { $status = 'archived'; }
        if ($status !== '' && in_array($status, ['draft', 'active', 'archived'], true)) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($name !== '') {
            $where[] = 'name LIKE :name';
            $params[':name'] = '%' . $name . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        // Se existir coluna deleted_at, filtrar registros soft-deleted
        try {
            $col = $pdo->query("SHOW COLUMNS FROM flows LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $col = false;
        }
        if ($col) {
            $whereClause = $whereClause === '' ? 'WHERE deleted_at IS NULL' : ($whereClause . ' AND deleted_at IS NULL');
        }

        // Contagem total
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM flows $whereClause");
        $stmt->execute($params);
        $total = (int)$stmt->fetch()['total'];

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT id, name, version, status, description, created_by, created_at, updated_at FROM flows $whereClause ORDER BY updated_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $flows = $stmt->fetchAll();
        // mapear status 'archived' -> 'inactive' para resposta
        foreach ($flows as &$f) {
            if (($f['status'] ?? '') === 'archived') {
                $f['status'] = 'inactive';
            }
        }
        unset($f);

        jsonResponse([
            'flows' => $flows,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ]);
        return;
    }

    // Obter flow específico
    if ($method === 'GET' && preg_match('#^/flows/([a-f0-9\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $flowId = $m[1];

        $pdo = pdo();
        $stmt = $pdo->prepare('SELECT id, name, version, status, description, definition, created_by, created_at, updated_at FROM flows WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        $flow = $stmt->fetch();

        if (!$flow) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }

        // Decodificar definition se existir
        if (!empty($flow['definition'])) {
            $decoded = json_decode($flow['definition'], true);
            // Se json_decode falhar, devolver definição bruta (evita erro 500 por dados inválidos)
            $flow['definition'] = $decoded === null ? $flow['definition'] : $decoded;
        } else {
            $flow['definition'] = null;
        }

        // mapear status para 'inactive' se vier 'archived'
        if (($flow['status'] ?? '') === 'archived') {
            $flow['status'] = 'inactive';
        }
        jsonResponse($flow);
        return;
    }

    // Criar flow
    if ($method === 'POST' && $path === '/flows') {
        $body = readJsonBody();
        $auth = requireAuth();

        $name = trim(strval($body['name'] ?? ''));
        $description = trim(strval($body['description'] ?? ''));
        // Aceitar tanto 'definition' quanto 'steps' no payload (compatibilidade com novo modelo)
        $definition = $body['definition'] ?? null;
        if ($definition === null && isset($body['steps']) && is_array($body['steps'])) {
            $definition = ['steps' => $body['steps']];
        }
    $status = trim(strval($body['status'] ?? 'draft'));
    if ($status === 'inactive') { $status = 'archived'; }

        // Validações
        if (empty($name) || strlen($name) < 1 || strlen($name) > 191) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome deve ter entre 1 e 191 caracteres']], 422);
            return;
        }
        if ($definition === null || !is_array($definition)) {
            jsonResponse(['error' => ['code' => 'INVALID_DEFINITION', 'message' => 'Definição deve ser um objeto JSON válido']], 422);
            return;
        }
        if (!array_key_exists('steps', $definition) || !is_array($definition['steps'])) {
            jsonResponse(['error' => ['code' => 'INVALID_STEPS', 'message' => 'Definição deve conter steps (array)']], 422);
            return;
        }
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_STATUS', 'message' => 'Status deve ser draft, active ou archived']], 422);
            return;
        }
        if ($description !== '' && strlen($description) > 65535) {
            jsonResponse(['error' => ['code' => 'INVALID_DESCRIPTION', 'message' => 'Descrição muito longa']], 422);
            return;
        }

        $pdo = pdo();
        // validar referências message_id nas steps (se houver)
        $steps = isset($definition['steps']) && is_array($definition['steps']) ? $definition['steps'] : [];
        foreach ($steps as $s) {
            if (is_array($s) && !empty($s['message_id'])) {
                $mid = trim(strval($s['message_id']));
                if ($mid !== '') {
                    $check = $pdo->prepare('SELECT id FROM messages WHERE id = :id LIMIT 1');
                    $check->execute([':id' => $mid]);
                    if (!$check->fetch()) {
                        jsonResponse(['error' => ['code' => 'INVALID_REFERENCE', 'message' => 'message_id referenciado não encontrado: ' . $mid]], 422);
                        return;
                    }
                }
            }
        }
        $flowId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $definitionJson = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmt = $pdo->prepare('INSERT INTO flows (id, name, version, status, description, definition, created_by, created_at, updated_at) VALUES (:id, :name, :version, :status, :description, :definition, :created_by, NOW(), NOW())');
        $stmt->execute([
            ':id' => $flowId,
            ':name' => $name,
            ':version' => 1,
            ':status' => $status,
            ':description' => $description ?: null,
            ':definition' => $definitionJson,
            ':created_by' => (int)$auth['sub'],
        ]);

        jsonResponse([
            'flow_id' => $flowId,
            'name' => $name,
            'version' => 1,
            'status' => $status === 'archived' ? 'inactive' : $status,
            'description' => $description,
            'definition' => $definition,
            'created_by' => (int)$auth['sub'],
        ], 201);
        return;
    }

    // Substituir flow (PUT)
    if ($method === 'PUT' && preg_match('#^/flows/([a-f0-9\-]{36})$#', $path, $m)) {
        $body = readJsonBody();
        $auth = requireAuth();
        $flowId = $m[1];

        $name = trim(strval($body['name'] ?? ''));
        $description = trim(strval($body['description'] ?? ''));
        $definition = $body['definition'] ?? null;
        // compatibility: accept top-level steps
        if ($definition === null && isset($body['steps']) && is_array($body['steps'])) {
            $definition = ['steps' => $body['steps']];
        }
    $status = trim(strval($body['status'] ?? 'draft'));
    if ($status === 'inactive') { $status = 'archived'; }

        if (empty($name) || strlen($name) < 1 || strlen($name) > 191) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome deve ter entre 1 e 191 caracteres']], 422);
            return;
        }
        if ($definition === null || !is_array($definition)) {
            jsonResponse(['error' => ['code' => 'INVALID_DEFINITION', 'message' => 'Definição deve ser um objeto JSON válido']], 422);
            return;
        }
        if (!array_key_exists('steps', $definition) || !is_array($definition['steps'])) {
            jsonResponse(['error' => ['code' => 'INVALID_STEPS', 'message' => 'Definição deve conter steps (array)']], 422);
            return;
        }
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_STATUS', 'message' => 'Status deve ser draft, active ou archived']], 422);
            return;
        }

        $pdo = pdo();
        // Verificar se existe
        $stmt = $pdo->prepare('SELECT id FROM flows WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }

        // Validar referências message_id nas steps
        $steps = isset($definition['steps']) && is_array($definition['steps']) ? $definition['steps'] : [];
        foreach ($steps as $s) {
            if (is_array($s) && !empty($s['message_id'])) {
                $mid = trim(strval($s['message_id']));
                if ($mid !== '') {
                    $check = $pdo->prepare('SELECT id FROM messages WHERE id = :id LIMIT 1');
                    $check->execute([':id' => $mid]);
                    if (!$check->fetch()) {
                        jsonResponse(['error' => ['code' => 'INVALID_REFERENCE', 'message' => 'message_id referenciado não encontrado: ' . $mid]], 422);
                        return;
                    }
                }
            }
        }

        $definitionJson = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Incrementar versão ao substituir
        $stmt = $pdo->prepare('UPDATE flows SET name = :name, description = :description, definition = :definition, status = :status, version = version + 1, updated_at = NOW() WHERE id = :id');
        $affected = $stmt->execute([':name' => $name, ':description' => $description ?: null, ':definition' => $definitionJson, ':status' => $status, ':id' => $flowId]);

        if ($affected === 0) {
            jsonResponse(['error' => ['code' => 'UPDATE_FAILED', 'message' => 'Falha ao substituir flow']], 500);
            return;
        }

        jsonResponse(['updated' => true, 'flow_id' => $flowId]);
        return;
    }

    // Atualizar flow
    if ($method === 'PATCH' && preg_match('#^/flows/([a-f0-9\-]{36})$#', $path, $m)) {
        $body = readJsonBody();
        $auth = requireAuth();
        $flowId = $m[1];

        $name = isset($body['name']) ? trim(strval($body['name'])) : null;
        $description = isset($body['description']) ? trim(strval($body['description'])) : null;
        $definition = $body['definition'] ?? null;
    $status = isset($body['status']) ? trim(strval($body['status'])) : null;
    if ($status === 'inactive') { $status = 'archived'; }

        // Validações
        if ($name !== null && (empty($name) || strlen($name) < 1 || strlen($name) > 191)) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome deve ter entre 1 e 191 caracteres']], 422);
            return;
        }
        if ($definition !== null && !is_array($definition)) {
            jsonResponse(['error' => ['code' => 'INVALID_DEFINITION', 'message' => 'Definição deve ser um objeto JSON válido']], 422);
            return;
        }
        if ($status !== null && !in_array($status, ['draft', 'active', 'archived'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_STATUS', 'message' => 'Status deve ser draft, active ou archived']], 422);
            return;
        }
        if ($description !== null && strlen($description) > 65535) {
            jsonResponse(['error' => ['code' => 'INVALID_DESCRIPTION', 'message' => 'Descrição muito longa']], 422);
            return;
        }

        $pdo = pdo();

        // Verificar se flow existe
        $stmt = $pdo->prepare('SELECT id FROM flows WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }

        // Se definition presente, validar referências message_id e preparar incremento de versão se mudou
        $shouldIncrementVersion = false;
        if ($definition !== null) {
            if (!is_array($definition)) {
                jsonResponse(['error' => ['code' => 'INVALID_DEFINITION', 'message' => 'Definição deve ser um objeto JSON válido']], 422);
                return;
            }
            if (!array_key_exists('steps', $definition) || !is_array($definition['steps'])) {
                jsonResponse(['error' => ['code' => 'INVALID_STEPS', 'message' => 'Definição deve conter steps (array)']], 422);
                return;
            }
            $stmt = $pdo->prepare('SELECT definition, version FROM flows WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $flowId]);
            $existing = $stmt->fetch();
            $existingDef = json_decode($existing['definition'] ?? 'null', true);
            if (json_encode($existingDef, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !== json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) {
                $shouldIncrementVersion = true;
            }

            $steps = isset($definition['steps']) && is_array($definition['steps']) ? $definition['steps'] : [];
            foreach ($steps as $s) {
                if (is_array($s) && !empty($s['message_id'])) {
                    $mid = trim(strval($s['message_id']));
                    if ($mid !== '') {
                        $check = $pdo->prepare('SELECT id FROM messages WHERE id = :id LIMIT 1');
                        $check->execute([':id' => $mid]);
                        if (!$check->fetch()) {
                            jsonResponse(['error' => ['code' => 'INVALID_REFERENCE', 'message' => 'message_id referenciado não encontrado: ' . $mid]], 422);
                            return;
                        }
                    }
                }
            }
        }

        // Construir query de atualização dinâmica
        $updates = [];
        $params = [':id' => $flowId];

        if ($name !== null) {
            $updates[] = 'name = :name';
            $params[':name'] = $name;
        }
        if ($description !== null) {
            $updates[] = 'description = :description';
            $params[':description'] = $description;
        }
        if ($definition !== null) {
            $updates[] = 'definition = :definition';
            $params[':definition'] = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($status !== null) {
            $updates[] = 'status = :status';
            $params[':status'] = $status;
        }

        if (empty($updates)) {
            jsonResponse(['error' => ['code' => 'NO_CHANGES', 'message' => 'Nenhum campo para atualizar']], 400);
            return;
        }

        // Se a definição mudou e detectamos que deve incrementar versão, incrementar
        if (!empty($shouldIncrementVersion)) {
            $updates[] = 'version = version + 1';
        }
        $updates[] = 'updated_at = NOW()';
        $sql = 'UPDATE flows SET ' . implode(', ', $updates) . ' WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $affected = $stmt->execute($params);

        if ($affected === 0) {
            jsonResponse(['error' => ['code' => 'UPDATE_FAILED', 'message' => 'Falha ao atualizar flow']], 500);
            return;
        }

        jsonResponse(['updated' => true, 'flow_id' => $flowId]);
        return;
    }

    // Remover flow
    if ($method === 'DELETE' && preg_match('#^/flows/([a-f0-9\-]{36})$#', $path, $m)) {
        $auth = requireAuth();
        $flowId = $m[1];

        $pdo = pdo();

        // Verificar se flow existe
        $stmt = $pdo->prepare('SELECT id FROM flows WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }

        // Verificar se flow está sendo usado em conversas ativas
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM conversations WHERE flow_id = :flow_id AND status = "active"');
        $stmt->execute([':flow_id' => $flowId]);
        $activeConversations = (int)$stmt->fetch()['count'];

        if ($activeConversations > 0) {
            jsonResponse(['error' => ['code' => 'FLOW_IN_USE', 'message' => 'Flow não pode ser removido pois está sendo usado em conversas ativas']], 409);
            return;
        }

        // Prefer soft-delete if coluna deleted_at existe
        try {
            $colCheck = $pdo->query("SHOW COLUMNS FROM flows LIKE 'deleted_at'")->fetch();
        } catch (Throwable $e) {
            $colCheck = false;
        }
        if ($colCheck) {
            $stmt = $pdo->prepare('UPDATE flows SET deleted_at = NOW() WHERE id = :id');
            $affected = $stmt->execute([':id' => $flowId]);
        } else {
            // Remover flow
            $stmt = $pdo->prepare('DELETE FROM flows WHERE id = :id');
            $affected = $stmt->execute([':id' => $flowId]);
        }

        if ($affected === 0) {
            jsonResponse(['error' => ['code' => 'DELETE_FAILED', 'message' => 'Falha ao remover flow']], 500);
            return;
        }

        jsonResponse(['deleted' => true, 'flow_id' => $flowId]);
        return;
    }

    // Ativar flow
    if ($method === 'POST' && preg_match('#^/flows/([a-f0-9\-]{36})/activate$#', $path, $m)) {
        $auth = requireAuth();
        $flowId = $m[1];
        $pdo = pdo();

        $stmt = $pdo->prepare('UPDATE flows SET status = "active", updated_at = NOW() WHERE id = :id');
        $affected = $stmt->execute([':id' => $flowId]);
        if (!$affected) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }
        jsonResponse(['flow_id' => $flowId, 'status' => 'active']);
        return;
    }

    // Desativar flow (status interno 'archived', exposto como 'inactive')
    if ($method === 'POST' && preg_match('#^/flows/([a-f0-9\-]{36})/deactivate$#', $path, $m)) {
        $auth = requireAuth();
        $flowId = $m[1];
        $pdo = pdo();

        $stmt = $pdo->prepare('UPDATE flows SET status = "archived", updated_at = NOW() WHERE id = :id');
        $affected = $stmt->execute([':id' => $flowId]);
        if (!$affected) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }
        jsonResponse(['flow_id' => $flowId, 'status' => 'inactive']);
        return;
    }

    // Duplicar flow
    if ($method === 'POST' && preg_match('#^/flows/([a-f0-9\-]{36})/duplicate$#', $path, $m)) {
        $auth = requireAuth();
        $flowId = $m[1];
        $pdo = pdo();

        $stmt = $pdo->prepare('SELECT name, description, definition FROM flows WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        $orig = $stmt->fetch();
        if (!$orig) {
            jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }
        $newId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $newName = rtrim(strval($orig['name'])) . ' (copy)';
        $stmt = $pdo->prepare('INSERT INTO flows (id, name, version, status, description, definition, created_by, created_at, updated_at) VALUES (:id, :name, :version, :status, :description, :definition, :created_by, NOW(), NOW())');
        $stmt->execute([
            ':id' => $newId,
            ':name' => $newName,
            ':version' => 1,
            ':status' => 'draft',
            ':description' => $orig['description'] ?? null,
            ':definition' => $orig['definition'] ?? null,
            ':created_by' => (int)$auth['sub'],
        ]);
        jsonResponse(['flow_id' => $newId, 'name' => $newName, 'version' => 1, 'status' => 'draft']);
        return;
    }

    // 404 padrão
    jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Rota não encontrada']], 404);
} catch (Throwable $e) {
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $msg = $isDebug ? $e->getMessage() : 'Erro interno';
    jsonResponse(['error' => ['code' => 'INTERNAL', 'message' => $msg]], 500);
}
