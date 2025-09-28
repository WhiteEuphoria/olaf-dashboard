<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Document extends Model {
    use HasFactory;
    protected $fillable = ['user_id', 'path', 'original_name', 'document_type', 'status'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function storageDisk(): string
    {
        return config('filesystems.documents_disk', 'public');
    }
}
