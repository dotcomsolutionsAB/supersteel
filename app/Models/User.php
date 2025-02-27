<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'otp',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'pincode',
        'gstin',
        'state',
        'country',
        'manager_id',
        'alias',
        'billing_style',
        'transport',
        'price_type',
        'notifications'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function orders()
    {
        return $this->hasMany(OrderModel::class);
    }

    public function user_cart()
    {
        return $this->hasMany(CartModel::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
    
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }
}
