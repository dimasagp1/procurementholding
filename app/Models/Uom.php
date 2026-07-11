<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uom extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'company_id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
