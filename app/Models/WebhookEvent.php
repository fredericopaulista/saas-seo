<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $fillable = [
        'event_id',       // ID único do evento no Asaas (para idempotência)
        'source',         // 'asaas'
        'event_type',     // ex: PAYMENT_RECEIVED
        'payload',        // JSON bruto do evento
        'processed_at',
        'status',         // 'pending' | 'processed' | 'failed' | 'ignored'
        'error_message',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Check if an event was already processed (idempotency guard).
     */
    public static function alreadyProcessed(string $eventId): bool
    {
        return static::where('event_id', $eventId)
            ->whereIn('status', ['processed', 'ignored'])
            ->exists();
    }
}
