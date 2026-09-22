<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrPoster extends Model
{
    protected $fillable = [
        'title',
        'barangay',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function reportUrl(): string
    {
        return route('public.report.create', ['barangay' => $this->barangay], absolute: true);
    }
}
