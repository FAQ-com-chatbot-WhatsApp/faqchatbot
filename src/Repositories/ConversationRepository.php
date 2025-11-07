<?php

namespace WhatsBot\Repositories;

use WhatsBot\Models\Conversation;
use Ramsey\Uuid\Uuid;

/**
 * ConversationRepository
 * 
 * Gerencia operações de banco para a entidade Conversation
 */
class ConversationRepository extends BaseRepository
{
    protected string $table = 'conversations';

    public function findById(string $id): ?Conversation
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $data = $this->fetchOne($sql, [':id' => $id]);
        
        return $data ? new Conversation($data) : null;
    }

    public function findByContactId(string $contactId, ?string $status = null): array
    {
        $params = [':contact_id' => $contactId];
        $where = ['contact_id = :contact_id'];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        if ($status !== null) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC";
        $results = $this->fetchAll($sql, $params);
        
        return array_map(fn($data) => new Conversation($data), $results);
    }

    public function list(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        if ($status !== null) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($data) => new Conversation($data), $results);
    }

    public function create(array $data): Conversation
    {
        $id = Uuid::uuid4()->toString();
        $contact_id = $data['contact_id'] ?? '';
        $flow_id = $data['flow_id'] ?? '';
        $status = $data['status'] ?? 'active';
        $message_count = (int)($data['message_count'] ?? 0);
        $current_step = $data['current_step'] ?? null;
        
        $sql = "INSERT INTO {$this->table} (id, contact_id, flow_id, status, message_count, current_step, created_at, updated_at) 
                VALUES (:id, :contact_id, :flow_id, :status, :message_count, :current_step, NOW(), NOW())";
        
        $this->execute($sql, [
            ':id' => $id,
            ':contact_id' => $contact_id,
            ':flow_id' => $flow_id,
            ':status' => $status,
            ':message_count' => $message_count,
            ':current_step' => $current_step,
        ]);
        
        return $this->findById($id);
    }

    public function update(string $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['status'])) {
            $fields[] = 'status = :status';
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['message_count'])) {
            $fields[] = 'message_count = :message_count';
            $params[':message_count'] = (int)$data['message_count'];
        }
        
        if (isset($data['current_step'])) {
            $fields[] = 'current_step = :current_step';
            $params[':current_step'] = $data['current_step'];
        }
        
        $fields[] = 'updated_at = NOW()';
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->execute($sql, $params);
    }

    public function incrementMessageCount(string $id): bool
    {
        $sql = "UPDATE {$this->table} SET message_count = message_count + 1, updated_at = NOW() WHERE id = :id";
        return $this->execute($sql, [':id' => $id]);
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

    public function count(?string $status = null): int
    {
        $params = [];
        $where = [];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        if ($status !== null) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $result = $this->fetchOne($sql, $params);
        return (int)($result['total'] ?? 0);
    }
}
