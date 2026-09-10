<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SyncLog model — PRD Section 13 & FR-17
 *
 * Records Google API integration operations.
 * direction: SHEETS_TO_APP, APP_TO_SHEETS
 * status: success, failed, partial
 */
class SyncLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'sync_type',
        'direction',
        'status',
        'records_processed',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'records_processed' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: filter by direction.
     */
    public function scopeDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope: filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: only failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Get duration in seconds (null if not completed).
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at);
    }

    /**
     * Helper: create a sync log entry.
     * Centralizes sync log creation to ensure consistent format.
     */
    public static function record(
        string $syncType,
        string $direction = 'SHEETS_TO_APP',
        string $status = 'SUCCESS',
        int $recordsProcessed = 0,
        ?string $errorMessage = null,
        ?\DateTimeInterface $startedAt = null,
        ?\DateTimeInterface $completedAt = null,
        ?int $userId = null,
    ): static {
        return static::create([
            'user_id' => $userId,
            'sync_type' => $syncType,
            'direction' => $direction,
            'status' => $status,
            'records_processed' => $recordsProcessed,
            'error_message' => $errorMessage,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);
    }
}
