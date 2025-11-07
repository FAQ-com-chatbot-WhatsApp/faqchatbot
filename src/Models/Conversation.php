<?php

namespace WhatsBot\Models;

/**
 * Conversation Model
 * 
 * Representa uma conversa entre o sistema e um contato.
 */
class Conversation
{
    public string $id;
    public string $contact_id;
    public string $flow_id;
    public string $status = 'active'; // active, completed, abandoned
    public int $message_count = 0;
    public ?string $current_step = null;
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
        $this->contact_id = $data['contact_id'] ?? '';
        $this->flow_id = $data['flow_id'] ?? '';
        $this->status = $data['status'] ?? 'active';
        $this->message_count = (int)($data['message_count'] ?? 0);
        $this->current_step = $data['current_step'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
        $this->updated_at = $data['updated_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'flow_id' => $this->flow_id,
            'status' => $this->status,
            'message_count' => $this->message_count,
            'current_step' => $this->current_step,
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

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
