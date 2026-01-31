<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LeaseDocument extends Model
{
    protected $fillable = [
        'lease_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'compressed_size',
        'file_hash',
        'is_compressed',
        'uploaded_by',
        'document_date',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'compressed_size' => 'integer',
        'is_compressed' => 'boolean',
        'document_date' => 'date',
    ];

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeForHumansAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getCompressionRatioAttribute(): ?float
    {
        if (!$this->is_compressed || !$this->compressed_size) {
            return null;
        }
        return round((1 - ($this->compressed_size / $this->file_size)) * 100, 1);
    }

    public function getDownloadUrl(): string
    {
        return Storage::disk('local')->url($this->file_path);
    }
}
