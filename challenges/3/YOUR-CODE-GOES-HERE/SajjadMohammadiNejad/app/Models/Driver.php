<?php

namespace App\Models;

use App\Enums\DriverStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

class Driver extends Model
{
    use HasFactory;

    public function scopeIsDriver($query, User $user)
    {
        return $query->where("id", $user->id);
    }

    public $incrementing = false;

    protected $casts = array(
        'status' => DriverStatus::class,
    );


    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'car_model',
        'car_plate',
        'latitude',
        'longitude',
        'status',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id');
    }
}
