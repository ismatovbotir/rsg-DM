<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['direction', 'entity', 'ip', 'http_status', 'rows', 'created', 'updated', 'errors_count', 'details', 'duration_ms'];

    protected $casts = ['details' => 'array'];
}
