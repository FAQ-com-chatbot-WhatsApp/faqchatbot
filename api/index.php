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
        $textContent    = trim(strval($body['content'] ?? ''));

        if (!v::uuid()->validate($conversationId)) {
            jsonResponse(['error' => ['code' => 'INVALID_CONVERSATION', 'message' => 'ID de conversa inválido']], 422);
            return;
        }
        if (!in_array($sender, ['user','bot'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_SENDER', 'message' => 'Sender deve ser user ou bot']], 422);
            return;
        }
        if (!v::stringType()->length(1, 5000)->validate($textContent)) {
            jsonResponse(['error' => ['code' => 'INVALID_CONTENT', 'message' => 'Conteúdo inválido']], 422);
            return;
        }

        $pdo = pdo();
        // Verificar conversa
        $stmt = $pdo->prepare('SELECT id, message_count FROM conversations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        $conversation = $stmt->fetch();
        if (!$conversation) {
            jsonResponse(['error' => ['code' => 'CONVERSATION_NOT_FOUND', 'message' => 'Conversa não encontrada']], 404);
            return;
        }

        $direction = $sender === 'user' ? 'in' : 'out';
        $messageId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $contentJson = json_encode(['text' => $textContent], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $pdo->prepare('INSERT INTO messages (id, conversation_id, direction, type, content, status, created_at) VALUES (:id, :conversation_id, :direction, :type, :content, :status, NOW())');
        $stmt->execute([
            ':id' => $messageId,
            ':conversation_id' => $conversationId,
            ':direction' => $direction,
            ':type' => 'text',
            ':content' => $contentJson,
            ':status' => 'processed',
        ]);

        // Atualizar conversa (contagem + last_message_id + updated_at)
        $pdo->prepare('UPDATE conversations SET message_count = message_count + 1, last_message_id = :mid, updated_at = NOW() WHERE id = :id')
            ->execute([':mid' => $messageId, ':id' => $conversationId]);

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

        // Contagem total
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM conversations c LEFT JOIN contacts co ON c.contact_id = co.id $whereClause");
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

        if ($status !== '' && in_array($status, ['draft', 'active', 'archived'], true)) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($name !== '') {
            $where[] = 'name LIKE :name';
            $params[':name'] = '%' . $name . '%';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

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

    // Criar flow
    if ($method === 'POST' && $path === '/flows') {
        $body = readJsonBody();
        $auth = requireAuth();

        $name = trim(strval($body['name'] ?? ''));
        $description = trim(strval($body['description'] ?? ''));
        $definition = $body['definition'] ?? null;
        $status = trim(strval($body['status'] ?? 'draft'));

        // Validações
        if (empty($name) || strlen($name) < 1 || strlen($name) > 191) {
            jsonResponse(['error' => ['code' => 'INVALID_NAME', 'message' => 'Nome deve ter entre 1 e 191 caracteres']], 422);
            return;
        }
        if ($definition === null || !is_array($definition)) {
            jsonResponse(['error' => ['code' => 'INVALID_DEFINITION', 'message' => 'Definição deve ser um objeto JSON válido']], 422);
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
            'status' => $status,
            'description' => $description,
            'definition' => $definition,
            'created_by' => (int)$auth['sub'],
        ], 201);
        return;
    }

    // 404 padrão
    jsonResponse(['error' => ['code' => 'NOT_FOUND', 'message' => 'Rota não encontrada']], 404);
} catch (Throwable $e) {
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $msg = $isDebug ? $e->getMessage() : 'Erro interno';
    jsonResponse(['error' => ['code' => 'INTERNAL', 'message' => $msg]], 500);
}
