<?php

namespace WhatsBot\Repositories;

use WhatsBot\Models\Flow;
use Ramsey\Uuid\Uuid;

/**
 * FlowRepository
 * 
 * Gerencia operações de banco para a entidade Flow
 */
class FlowRepository extends BaseRepository
{
    protected string $table = 'flows';

    public function findById(string $id): ?Flow
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $data = $this->fetchOne($sql, [':id' => $id]);
        
        return $data ? new Flow($data) : null;
    }

    public function list(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];
        
        if ($this->columnExists('deleted_at')) {
            $where[] = 'deleted_at IS NULL';
        }
        
        if ($status !== null && in_array($status, ['draft', 'active', 'archived'])) {
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
        return array_map(fn($data) => new Flow($data), $results);
    }

    public function create(array $data): Flow
    {
        $id = Uuid::uuid4()->toString();
        $name = $data['name'] ?? '';
        $version = (int)($data['version'] ?? 1);
        $status = $data['status'] ?? 'draft';
        $description = $data['description'] ?? null;
        $definition = isset($data['definition']) ? json_encode($data['definition']) : null;
        $created_by = (int)($data['created_by'] ?? 0);
        
        $sql = "INSERT INTO {$this->table} (id, name, version, status, description, definition, created_by, created_at, updated_at) 
                VALUES (:id, :name, :version, :status, :description, :definition, :created_by, NOW(), NOW())";
        
        $this->execute($sql, [
            ':id' => $id,
            ':name' => $name,
            ':version' => $version,
            ':status' => $status,
            ':description' => $description,
            ':definition' => $definition,
            ':created_by' => $created_by,
        ]);
        
        return $this->findById($id);
    }

    public function update(string $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        
        if (isset($data['status'])) {
            $fields[] = 'status = :status';
            $params[':status'] = $data['status'];
        }
        
        if (isset($data['description'])) {
            $fields[] = 'description = :description';
            $params[':description'] = $data['description'];
        }
        
        if (isset($data['definition'])) {
            $fields[] = 'definition = :definition';
            $fields[] = 'version = version + 1';
            $params[':definition'] = json_encode($data['definition']);
        }
        
        $fields[] = 'updated_at = NOW()';
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->execute($sql, $params);
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
