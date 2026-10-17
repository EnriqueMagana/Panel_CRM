<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrowserExtension extends Model
{
    protected $fillable = [
        'uploaded_by',
        'name',
        'slug',
        'version',
        'description',
        'features',
        'supported_browsers',
        'zip_path',
        'zip_size',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['features' => 'array', 'is_active' => 'boolean', 'zip_size' => 'integer'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function downloadUrl(): string
    {
        return route('browser-extensions.download', $this, false);
    }

    public function formattedSize(): string
    {
        if ($this->zip_size < 1024) {
            return $this->zip_size.' B';
        }

        if ($this->zip_size < 1_048_576) {
            return number_format($this->zip_size / 1024, 1).' KB';
        }

        return number_format($this->zip_size / 1_048_576, 1).' MB';
    }
}
