<?php

namespace WhatsBot\Models;

/**
 * Flow Model
 * 
 * Representa um fluxo conversacional.
 */
class Flow
{
    public string $id;
    public string $name;
    public int $version = 1;
    public string $status = 'draft'; // draft, active, archived
    public ?string $description = null;
    public ?array $definition = null;
    public int $created_by;
    public ?string $deleted_at = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data): void
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->version = (int)($data['version'] ?? 1);
        $this->status = $data['status'] ?? 'draft';
        $this->description = $data['description'] ?? null;
        
        // Se definition veio como JSON string, decodifica
        if (isset($data['definition'])) {
            if (is_string($data['definition'])) {
                $this->definition = json_decode($data['definition'], true);
            } else {
                $this->definition = $data['definition'];
            }
        }
        
        $this->created_by = (int)($data['created_by'] ?? 0);
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'version' => $this->version,
            'status' => $this->status,
            'description' => $this->description,
            'definition' => $this->definition,
            'created_by' => $this->created_by,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isDeleted();
    }
}
