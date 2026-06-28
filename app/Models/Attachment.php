<?php

namespace App\Models;

use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'name',
        'mime_type',
        'size',
    ];

    protected $appends = ['url'];

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Public URL to the stored file.
     *
     * @return Attribute<string|null, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => $this->path ? Storage::disk($this->disk)->url($this->path) : null);
    }
}
