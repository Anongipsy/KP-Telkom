<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContractDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'lop',
        'original_name',
        'stored_name',
        'mime_type',
        'size',
        'disk',
        'path',
        'uploaded_by',
    ];

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope query to find document by contract LOP.
     */
    public function scopeForLop(Builder $query, string $lop): Builder
    {
        return $query->where('lop', $lop);
    }

    /**
     * Check if document is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf'
            || str_ends_with(strtolower($this->original_name), '.pdf');
    }

    /**
     * Check if document is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if document can be previewed directly in browser.
     */
    public function isPreviewable(): bool
    {
        return $this->isPdf() || $this->isImage();
    }

    /**
     * Human-readable formatted file size (KB / MB).
     */
    public function sizeFormatted(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Get full absolute path on the disk.
     */
    public function getFullPath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /**
     * Format metadata for API / JSON preview consumption.
     */
    public function toMetadataArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->original_name,
            'stored_name' => $this->stored_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_formatted' => $this->sizeFormatted(),
            'is_pdf' => $this->isPdf(),
            'is_image' => $this->isImage(),
            'is_previewable' => $this->isPreviewable(),
            'uploaded_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'uploaded_by_name' => $this->uploader?->name,
        ];
    }
}
