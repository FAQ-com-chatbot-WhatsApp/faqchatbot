<?php

namespace WhatsBot\Models;

/**
 * Message Model
 * 
 * Representa uma mensagem em uma conversa.
 */
class Message
{
    public string $id;
    public string $conversation_id;
    public string $direction; // inbound, outbound
    public string $type; // text, image, document, location, etc
    public ?array $content = null;
    public string $status = 'pending'; // pending, sent, delivered, read, failed
    public ?string $external_id = null;
    public ?string $deleted_at = null;
    public string $created_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }

    public function hydrate(array $data): void
    {
        $this->id = $data['id'] ?? '';
        $this->conversation_id = $data['conversation_id'] ?? '';
        $this->direction = $data['direction'] ?? 'outbound';
        $this->type = $data['type'] ?? 'text';
        
        // Se content veio como JSON string, decodifica
        if (isset($data['content'])) {
            if (is_string($data['content'])) {
                $this->content = json_decode($data['content'], true);
            } else {
                $this->content = $data['content'];
            }
        }
        
        $this->status = $data['status'] ?? 'pending';
        $this->external_id = $data['external_id'] ?? null;
        $this->deleted_at = $data['deleted_at'] ?? null;
        $this->created_at = $data['created_at'] ?? '';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'direction' => $this->direction,
            'type' => $this->type,
            'content' => $this->content,
            'status' => $this->status,
            'external_id' => $this->external_id,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
        ];
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }
}
