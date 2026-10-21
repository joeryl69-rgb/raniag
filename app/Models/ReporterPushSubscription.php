<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReporterPushSubscription extends Model
{
    protected $fillable = [
        'client_token',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];
}
