<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresetMessagePreference extends Model
{
    public const LAYOUTS = ['list', 'grid'];

    public const DENSITIES = ['comfortable', 'compact'];

    public const ACCENTS = ['blue', 'violet', 'emerald', 'rose', 'amber', 'custom'];

    public const BACKGROUNDS = ['plain', 'dots', 'grid', 'soft', 'image'];

    public const CARD_STYLES = ['outlined', 'elevated', 'glass'];

    public const RADII = ['subtle', 'rounded', 'large'];

    public const MIN_FONT_SIZE = 12;

    public const MAX_FONT_SIZE = 24;

    public const THEMES = [
        'classic' => ['label' => 'Clásico', 'layout' => 'grid', 'density' => 'comfortable', 'accent' => 'blue', 'background' => 'plain', 'accent_color' => null, 'background_color' => null, 'card_style' => 'outlined', 'radius' => 'rounded', 'font_size' => 14],
        'ocean' => ['label' => 'Océano', 'layout' => 'grid', 'density' => 'comfortable', 'accent' => 'custom', 'background' => 'soft', 'accent_color' => '#0284c7', 'background_color' => '#f0f9ff', 'card_style' => 'elevated', 'radius' => 'large', 'font_size' => 14],
        'lavender' => ['label' => 'Lavanda', 'layout' => 'grid', 'density' => 'comfortable', 'accent' => 'custom', 'background' => 'dots', 'accent_color' => '#7c3aed', 'background_color' => '#faf5ff', 'card_style' => 'glass', 'radius' => 'large', 'font_size' => 14],
        'forest' => ['label' => 'Bosque', 'layout' => 'grid', 'density' => 'compact', 'accent' => 'custom', 'background' => 'grid', 'accent_color' => '#059669', 'background_color' => '#f0fdf4', 'card_style' => 'outlined', 'radius' => 'rounded', 'font_size' => 12],
        'sunset' => ['label' => 'Atardecer', 'layout' => 'grid', 'density' => 'comfortable', 'accent' => 'custom', 'background' => 'soft', 'accent_color' => '#e11d48', 'background_color' => '#fff7ed', 'card_style' => 'elevated', 'radius' => 'large', 'font_size' => 16],
        'minimal' => ['label' => 'Minimalista', 'layout' => 'list', 'density' => 'compact', 'accent' => 'custom', 'background' => 'plain', 'accent_color' => '#d97706', 'background_color' => '#fffbeb', 'card_style' => 'outlined', 'radius' => 'subtle', 'font_size' => 14],
    ];

    protected $fillable = [
        'user_id',
        'theme',
        'layout',
        'density',
        'accent',
        'accent_color',
        'background',
        'background_color',
        'background_image_path',
        'card_style',
        'radius',
        'font_size',
    ];

    protected function casts(): array
    {
        return ['font_size' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
