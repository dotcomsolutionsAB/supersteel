<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = "t_products";
    protected $fillable = [
        'product_code',
        'product_name',
        'print_name',
        'brand',
        'category',
        'type',
        'machine_part_no',
        'price_a',
        'price_b',
        'price_c',
        'price_d',
        'price_i',
        'ppc',
        'stock',
        'in_transit',
        'pending',
        'product_image',
        'extra_images',
        'new_arrival',
        'special_price',
        'video_link',
    ];

    public function transactions()
    {
        // return $this->hasMany(CartModel::class, 'products_id', 'product_code');
        return $this->hasMany(CartModel::class, 'product_code', 'product_code');
    }

    // Define the relationship: A product belongs to a category using the 'category' field
    public function category()
    {
        return $this->belongsTo(CategoryModel::class, 'category', 'name');
    }
}
