<?php

namespace WhatsBot\Repositories;

use WhatsBot\Models\Message;
use Ramsey\Uuid\Uuid;

/**
 * MessageRepository
 * 
 * Gerencia operações de banco para a entidade Message
 */
class MessageRepository extends BaseRepository
{
    protected string $table = 'messages';

    public function findById(string $id): ?Message
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $data = $this->fetchOne($sql, [':id' => $id]);
        
        return $data ? new Message($data) : null;
    }

    public function findByConversationId(string $conversationId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [':conversation_id' => $conversationId];
        $where = ['conversation_id = :conversation_id'];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($data) => new Message($data), $results);
    }

    public function list(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($data) => new Message($data), $results);
    }

    public function create(array $data): Message
    {
        $id = Uuid::uuid4()->toString();
        $conversation_id = $data['conversation_id'] ?? '';
        $direction = $data['direction'] ?? 'outbound';
        $type = $data['type'] ?? 'text';
        $content = isset($data['content']) ? json_encode($data['content']) : null;
        $status = $data['status'] ?? 'pending';
        $external_id = $data['external_id'] ?? null;
        
        $sql = "INSERT INTO {$this->table} (id, conversation_id, direction, type, content, status, external_id, created_at) 
                VALUES (:id, :conversation_id, :direction, :type, :content, :status, :external_id, NOW())";
        
        $this->execute($sql, [
            ':id' => $id,
            ':conversation_id' => $conversation_id,
            ':direction' => $direction,
            ':type' => $type,
            ':content' => $content,
            ':status' => $status,
            ':external_id' => $external_id,
        ]);
        
        return $this->findById($id);
    }

    public function updateStatus(string $id, string $status): bool
    {
        $sql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        return $this->execute($sql, [':id' => $id, ':status' => $status]);
    }

    public function delete(string $id): bool
    {
        if ($this->columnExists('deleted_at')) {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        } else {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
        }
        
        return $this->execute($sql, [':id' => $id]);
    }

    public function count(?string $conversationId = null): int
    {
        $params = [];
        $where = [];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        if ($conversationId !== null) {
            $where[] = 'conversation_id = :conversation_id';
            $params[':conversation_id'] = $conversationId;
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $result = $this->fetchOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }
}
