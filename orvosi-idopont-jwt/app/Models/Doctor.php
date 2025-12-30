<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'specialization',
        'email',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

?>