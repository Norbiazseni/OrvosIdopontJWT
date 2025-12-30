<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'specialization',
        'room',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

?>