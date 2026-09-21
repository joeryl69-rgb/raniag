<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HazardZoneType extends Model
{
    protected $fillable = ['name', 'slug', 'color'];

    public function zones(): HasMany
    {
        return $this->hasMany(HazardZone::class);
    }
}
