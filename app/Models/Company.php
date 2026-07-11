<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'connect_odoo',
        'connect_finance',
        'odoo_url',
        'odoo_db',
        'odoo_username',
        'odoo_password',
        'odoo_company_id',
        'finance_api_url',
        'finance_api_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'connect_odoo' => 'boolean',
        'connect_finance' => 'boolean',
        'odoo_company_id' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class);
    }
}
