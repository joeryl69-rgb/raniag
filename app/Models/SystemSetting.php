<?php

namespace App\Models;

use App\Support\ThemePresets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'theme_key',
        'dark_mode',
        'follow_system',
        'font_key',
        'font_size',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
            'follow_system' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Singleton accessor — the whole app shares one settings row (id=1).
     * Cached for the request so layouts/app.blade.php can call this on
     * every page without an extra query per view.
     */
    public static function current(): self
    {
        return once(function () {
            return static::firstOrCreate(['id' => 1], [
                'theme_key' => ThemePresets::DEFAULT_KEY,
                'dark_mode' => false,
                'follow_system' => false,
                'font_key' => ThemePresets::DEFAULT_FONT_KEY,
                'font_size' => ThemePresets::DEFAULT_FONT_SIZE,
            ]);
        });
    }
}
