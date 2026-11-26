<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An admin account (table `admins`). AdminController::login checks this table
 * first; the .env ADMIN_EMAIL / ADMIN_PASSWORD stays as the fallback login.
 * The password column always holds a bcrypt hash.
 */
class Admin extends Model
{
    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password'];

    protected $casts = ['is_active' => 'boolean'];
}
