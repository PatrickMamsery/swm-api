<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DawasaOffice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
    ];

    public function personnels()
    {
        return $this->hasMany(DawasaPersonnel::class);
    }
}
