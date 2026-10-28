<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    public const MODES = [
        'auto' => 'Avto (ekran va sensorga qarab)',
        'on' => 'Klaviatura yoqilgan (sensorli ekran)',
        'off' => 'Klaviatura o‘chiq (tugma bilan chaqiriladi)',
    ];

    protected $fillable = ['uuid', 'token_hash', 'name', 'keyboard_mode', 'last_seen_at', 'last_user_id', 'user_agent', 'printer_id'];

    protected $casts = ['last_seen_at' => 'datetime'];

    /** The PDA making this request (set by the DeviceProfile middleware), or null. */
    public static function current(): ?self
    {
        return app()->bound('device.current') ? app('device.current') : null;
    }

    public function printer()
    {
        return $this->belongsTo(Printer::class);
    }

    public function lastUser()
    {
        return $this->belongsTo(User::class, 'last_user_id');
    }
}
