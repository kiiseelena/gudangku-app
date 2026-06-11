<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionLog extends Model
{
    protected $table = 'session_logs';

    protected $fillable = [
        'session_id',
        'role',
        'timestamp'
    ];
}
