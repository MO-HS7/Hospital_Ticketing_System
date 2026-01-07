<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'locale',
        'state',
        'messages',
        'expires_at',
    ];

    protected $casts = [
        'state' => 'array',
        'messages' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get a state value by key.
     */
    public function getState(string $key, $default = null)
    {
        return $this->state[$key] ?? $default;
    }

    /**
     * Set a state value.
     */
    public function setState(string $key, $value): self
    {
        $state = $this->state ?? [];
        $state[$key] = $value;
        $this->state = $state;
        return $this;
    }

    /**
     * Add a message to conversation history.
     */
    public function addMessage(string $role, string $content, array $meta = []): self
    {
        $messages = $this->messages ?? [];
        $messages[] = [
            'role' => $role,
            'content' => $content,
            'meta' => $meta,
            'timestamp' => now()->toIso8601String(),
        ];
        // Keep only last 20 messages for context
        $this->messages = array_slice($messages, -20);
        return $this;
    }

    /**
     * Get recent messages for AI context.
     */
    public function getRecentMessages(int $limit = 10): array
    {
        $messages = $this->messages ?? [];
        return array_slice($messages, -$limit);
    }
}
