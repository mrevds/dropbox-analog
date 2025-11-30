<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //
    use HasApiTokens, Notifiable;
    protected $fillable = [
        'username',
        'password'
    ];
}
