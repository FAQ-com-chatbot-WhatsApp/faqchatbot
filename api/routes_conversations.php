<?php
// Arquivo obsoleto: as rotas foram integradas em api/index.php.
// Mantido vazio intencionalmente para evitar inclusões antigas.
if (!defined('__WHATS_BOT_ROUTES_DEPRECATED__')) {
    define('__WHATS_BOT_ROUTES_DEPRECATED__', true);
    // Sem conteúdo.
}
            jsonResponse(['error' => ['code' => 'INVALID_FLOW', 'message' => 'Flow ID inválido']], 422);
            return;
        }
        
        $pdo = pdo();
        
        // Verificar se flow existe
        $stmt = $pdo->prepare('SELECT id FROM flows WHERE id = :id AND active = 1 LIMIT 1');
        $stmt->execute([':id' => $flowId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'FLOW_NOT_FOUND', 'message' => 'Flow não encontrado']], 404);
            return;
        }
        
        // Buscar ou criar contato
        $stmt = $pdo->prepare('SELECT id FROM contacts WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $contactPhone]);
        $contact = $stmt->fetch();
        
        if ($contact) {
            $contactId = (int)$contact['id'];
            // Atualizar nome se fornecido
            $pdo->prepare('UPDATE contacts SET name = :name, updated_at = NOW() WHERE id = :id')
                ->execute([':name' => $contactName, ':id' => $contactId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO contacts (phone, name, created_at, updated_at) VALUES (:phone, :name, NOW(), NOW())');
            $stmt->execute([':phone' => $contactPhone, ':name' => $contactName]);
            $contactId = (int)$pdo->lastInsertId();
        }
        
        // Criar conversa
        $conversationId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $stmt = $pdo->prepare('INSERT INTO conversations (id, contact_id, flow_id, status, created_at, updated_at) VALUES (:id, :contact_id, :flow_id, :status, NOW(), NOW())');
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

    if ($method === 'POST' && $path === '/messages') {
        $auth = requireAuth();
        $body = readJsonBody();
        
        $conversationId = trim(strval($body['conversation_id'] ?? ''));
        $sender = trim(strval($body['sender'] ?? ''));
        $content = trim(strval($body['content'] ?? ''));
        
        if (!v::uuid()->validate($conversationId)) {
            jsonResponse(['error' => ['code' => 'INVALID_CONVERSATION', 'message' => 'ID de conversa inválido']], 422);
            return;
        }
        if (!in_array($sender, ['user', 'bot'], true)) {
            jsonResponse(['error' => ['code' => 'INVALID_SENDER', 'message' => 'Sender deve ser user ou bot']], 422);
            return;
        }
        if (!v::stringType()->length(1, 5000)->validate($content)) {
            jsonResponse(['error' => ['code' => 'INVALID_CONTENT', 'message' => 'Conteúdo inválido']], 422);
            return;
        }
        
        $pdo = pdo();
        
        // Verificar se conversa existe
        $stmt = $pdo->prepare('SELECT id FROM conversations WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $conversationId]);
        if (!$stmt->fetch()) {
            jsonResponse(['error' => ['code' => 'CONVERSATION_NOT_FOUND', 'message' => 'Conversa não encontrada']], 404);
            return;
        }
        
        $messageId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $stmt = $pdo->prepare('INSERT INTO messages (id, conversation_id, sender, content, created_at) VALUES (:id, :conversation_id, :sender, :content, NOW())');
        $stmt->execute([
            ':id' => $messageId,
            ':conversation_id' => $conversationId,
            ':sender' => $sender,
            ':content' => $content,
        ]);
        
        // Atualizar updated_at da conversa
        $pdo->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = :id')
            ->execute([':id' => $conversationId]);
        
        jsonResponse([
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'content' => $content,
        ], 201);
        return;
    }

    if ($method === 'GET' && preg_match('#^/conversations/([a-f0-9-]{36})$#', $path, $matches)) {
        $auth = requireAuth();
        $conversationId = $matches[1];
        
        $pdo = pdo();
        
        $stmt = $pdo->prepare('
            <?php
            // Arquivo obsoleto: as rotas foram integradas em api/index.php.
            // Mantido vazio intencionalmente para evitar inclusões antigas.
            if (!defined('__WHATS_BOT_ROUTES_DEPRECATED__')) {
                define('__WHATS_BOT_ROUTES_DEPRECATED__', true);
                // Sem conteúdo.
            }
