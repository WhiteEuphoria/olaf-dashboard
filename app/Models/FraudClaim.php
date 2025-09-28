<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FraudClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'details',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FraudClaimAttachment::class);
    }

    /**
     * @param array<int, UploadedFile|null> $files
     */
    public function addAttachments(array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('fraud-claims', 'public');

            $this->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    /**
     * @param iterable<int|string> $attachmentIds
     */
    public function removeAttachments(iterable $attachmentIds): void
    {
        $ids = collect($attachmentIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $attachments = $this->attachments()->whereIn('id', $ids)->get();

        foreach ($attachments as $attachment) {
            $disk = $attachment->disk ?: 'public';
            if ($attachment->path && Storage::disk($disk)->exists($attachment->path)) {
                Storage::disk($disk)->delete($attachment->path);
            }

            $attachment->delete();
        }
    }
}
