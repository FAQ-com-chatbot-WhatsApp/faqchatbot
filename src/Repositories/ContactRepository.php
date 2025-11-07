<?php

namespace WhatsBot\Repositories;

use WhatsBot\Models\Contact;
use Ramsey\Uuid\Uuid;

/**
 * ContactRepository
 * 
 * Gerencia operações de banco para a entidade Contact
 */
class ContactRepository extends BaseRepository
{
    protected string $table = 'contacts';

    /**
     * Busca um contato por ID
     */
    public function findById(string $id): ?Contact
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $data = $this->fetchOne($sql, [':id' => $id]);
        
        return $data ? new Contact($data) : null;
    }

    /**
     * Busca um contato por telefone
     */
    public function findByPhone(string $phone): ?Contact
    {
        $sql = "SELECT * FROM {$this->table} WHERE phone = :phone LIMIT 1";
        $data = $this->fetchOne($sql, [':id' => $phone]);
        
        return $data ? new Contact($data) : null;
    }

    /**
     * Lista todos os contatos com paginação
     */
    public function list(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        $whereClause = '';
        if ($this->columnExists('deleted_at')) {
            $whereClause = 'WHERE deleted_at IS NULL';
        }
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($data) => new Contact($data), $results);
    }

    /**
     * Cria um novo contato
     */
    public function create(array $data): Contact
    {
        $id = Uuid::uuid4()->toString();
        $name = $data['name'] ?? '';
        $phone = $data['phone'] ?? '';
        $external_id = $data['external_id'] ?? null;
        
        $sql = "INSERT INTO {$this->table} (id, name, phone, external_id, created_at, updated_at) 
                VALUES (:id, :name, :phone, :external_id, NOW(), NOW())";
        
        $this->execute($sql, [
            ':id' => $id,
            ':name' => $name,
            ':phone' => $phone,
            ':external_id' => $external_id,
        ]);
        
        return $this->findById($id);
    }

    /**
     * Atualiza um contato existente
     */
    public function update(string $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];
        
        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        
        if (isset($data['phone'])) {
            $fields[] = 'phone = :phone';
            $params[':phone'] = $data['phone'];
        }
        
        if (isset($data['external_id'])) {
            $fields[] = 'external_id = :external_id';
            $params[':external_id'] = $data['external_id'];
        }
        
        $fields[] = 'updated_at = NOW()';
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        return $this->execute($sql, $params);
    }

    /**
     * Remove um contato (soft delete se disponível)
     */
    public function delete(string $id): bool
    {
        if ($this->columnExists('deleted_at')) {
            $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        } else {
            $sql = "DELETE FROM {$this->table} WHERE id = :id";
        }
        
        return $this->execute($sql, [':id' => $id]);
    }

    /**
     * Conta o total de contatos
     */
    public function count(): int
    {
        $whereClause = '';
        if ($this->columnExists('deleted_at')) {
            $whereClause = 'WHERE deleted_at IS NULL';
        }
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $result = $this->fetchOne($sql);
        return (int)($result['total'] ?? 0);
    }
}
