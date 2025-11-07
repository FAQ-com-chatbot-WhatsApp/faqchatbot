<?php

namespace WhatsBot\Models;

/**
 * Contact Model
 * 
 * Representa um contato no sistema.
 */
class Contact
{
    public string $id;
    public string $name;
    public string $phone;
    public ?string $external_id = null;
    public ?string $deleted_at = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    /**
     * Preenche o modelo com dados do array (geralmente resultado do DB)
     */
    public function hydrate(array $data): void
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->external_id = $data['external_id'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    /**
     * Converte o modelo para array (útil para JSON responses)
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'external_id' => $this->external_id,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Verifica se o contato foi soft-deleted
     */
    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}
