<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog model — PRD Section 13 & FR-16
 *
 * Records user actions for audit trail.
 * Actions: login, failed_login, contract_update, contract_create,
 *          google_sheets_error, google_drive_error, notification_generated, etc.
 *
 * IMPORTANT: metadata MUST NOT contain passwords, private keys, tokens, or secrets.
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: filter by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: filter by target type.
     */
    public function scopeForTarget($query, string $targetType, ?string $targetReference = null)
    {
        $query->where('target_type', $targetType);

        if ($targetReference !== null) {
            $query->where('target_reference', $targetReference);
        }

        return $query;
    }

    /**
     * Helper: create an audit log entry.
     * Centralizes audit log creation to ensure consistent format and auto-sanitization.
     */
    public static function record(
        string $action,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetReference = null,
        ?array $metadata = null,
    ): static {
        $sanitizedMetadata = $metadata !== null ? \App\Helpers\LogSanitizer::sanitize($metadata) : null;

        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_reference' => $targetReference,
            'metadata' => $sanitizedMetadata,
        ]);
    }
}
