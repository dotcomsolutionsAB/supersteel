<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasFactory;
    protected $table = 't_orders';

    protected $fillable = [
        // 'client_id',
        'user_id',
        'order_id',
        'order_date',
        'amount',
        // 'log_date',
        // 'log_user',
        'created_by',
        'status',
        'type',
        'remarks',
        'is_edited',
    ];

    public function user()
    {
        // return $this->belongsTo(User::class, 'client_id'); 
        return $this->belongsTo(User::class, 'user_id'); 
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order_items()
    {
        // return $this->hasMany(OrderItemsModel::class,'orderID', 'order_id');
        return $this->hasMany(OrderItemsModel::class,'order_id', 'id');
    }
}
