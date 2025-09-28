<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FraudClaimAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fraud_claim_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function fraudClaim(): BelongsTo
    {
        return $this->belongsTo(FraudClaim::class);
    }

    public function storageDisk(): string
    {
        return $this->disk ?: 'public';
    }

    public function existsInStorage(): bool
    {
        if (! $this->path) {
            return false;
        }

        return Storage::disk($this->storageDisk())->exists($this->path);
    }

    public function isImage(): bool
    {
        if ($this->mime_type) {
            return Str::startsWith(strtolower($this->mime_type), 'image/');
        }

        if (! $this->path) {
            return false;
        }

        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }

    public function previewUrl(FraudClaim $claim): ?string
    {
        if (! $this->isImage() || ! $this->existsInStorage()) {
            return null;
        }

        return route('admin.dashboard.fraud-claims.attachments.preview', [$claim, $this]);
    }

    public function downloadUrl(FraudClaim $claim): ?string
    {
        if (! $this->existsInStorage()) {
            return null;
        }

        return route('admin.dashboard.fraud-claims.attachments.download', [$claim, $this]);
    }

    public function extensionLabel(): string
    {
        return strtoupper(pathinfo($this->original_name ?: $this->path, PATHINFO_EXTENSION) ?: 'FILE');
    }
}
