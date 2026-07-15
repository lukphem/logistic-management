<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiAccessDenial extends Model
{
    protected $fillable = ['api_client_id', 'attempted_ip', 'reason', 'endpoint'];
}
