<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresetMessage extends Model
{
    use HasFactory;

    /** @var array<string, array{label: string, example: string}> */
    public const AUTOMATIC_VARIABLES = [
        'fecha' => ['label' => 'Fecha', 'example' => '15/10/2026'],
        'fecha_larga' => ['label' => 'Fecha larga', 'example' => 'jueves, 15 de octubre de 2026'],
        'dia' => ['label' => 'Día', 'example' => 'jueves'],
        'hora' => ['label' => 'Hora', 'example' => '16:30'],
        'saludo' => ['label' => 'Saludo', 'example' => 'Buenas tardes'],
        'mi_nombre' => ['label' => 'Mi nombre', 'example' => 'Usuario actual'],
    ];

    /** @var array<string, array{label: string, example: string, input_type: string, calculation: string}> */
    public const CALCULATED_VARIABLES = [
        'dias_para_90' => [
            'label' => '90 días',
            'example' => '39 días',
            'input_type' => 'date',
            'calculation' => 'days-to-90',
        ],
    ];

    protected $fillable = ['created_by', 'title', 'body', 'variables', 'is_active'];

    protected function casts(): array
    {
        return ['variables' => 'array', 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function userStates(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'preset_message_user')
            ->withPivot(['pinned_at', 'pin_order', 'hidden_at'])
            ->withTimestamps();
    }

    public function usageEvents(): HasMany
    {
        return $this->hasMany(PresetMessageUsageEvent::class);
    }

    /** @return list<string> */
    public static function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([A-Za-z][A-Za-z0-9_]*)\s*\}\}/u', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /** @return array<string, array{label: string, example: string}> */
    public static function automaticVariables(): array
    {
        return self::AUTOMATIC_VARIABLES;
    }

    /** @return array<string, array{label: string, example: string, input_type: string, calculation: string}> */
    public static function calculatedVariables(): array
    {
        return self::CALCULATED_VARIABLES;
    }

    /** @return array{label: string, example: string, input_type: string, calculation: string}|null */
    public static function calculatedVariable(string $name): ?array
    {
        return self::CALCULATED_VARIABLES[$name] ?? null;
    }

    /** @return list<string> */
    public function manualVariables(): array
    {
        return array_values(array_diff($this->variables ?? [], array_keys(self::AUTOMATIC_VARIABLES)));
    }
}
