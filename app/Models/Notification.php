<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notification model — PRD Section 13 & FR-11
 *
 * Stores expiration alerts for contracts.
 * alert_type: EXPIRING_SOON, OVERDUE
 *
 * Unique constraint: one notification per user + LOP + alert_type.
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lop_reference',
        'alert_type',
        'days_remaining',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'days_remaining' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: unread notifications only.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: read notifications only.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope: filter by alert type.
     */
    public function scopeOfType($query, string $alertType)
    {
        return $query->where('alert_type', $alertType);
    }

    /**
     * Scope: filter by LOP reference.
     */
    public function scopeForLop($query, string $lop)
    {
        return $query->where('lop_reference', $lop);
    }

    /**
     * Mark this notification as read.
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }
}
