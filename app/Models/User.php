<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'department_id',
        'phone',
        'position',
        'signature_path'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('superadmin');
    }

    public function isOperationalManager()
    {
        return $this->hasRole('operational_manager');
    }

    public function isGeneralManager()
    {
        return $this->hasRole('general_manager');
    }

    public function isProcurement()
    {
        return $this->hasRole('procurement');
    }

    public function isRegularUser()
    {
        return $this->hasRole('user');
    }
}