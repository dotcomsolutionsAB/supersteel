<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSubCategoryModel extends Model
{
    use HasFactory;

    protected $table = 't_app_sub_category';

    protected $fillable = [
        'category_id',
        'name',
        'category_image',
    ];

    // Define the relationship: A category has many products, joined by the 'name' column
    public function get_products()
    {
        return $this->hasMany(ProductModel::class, 'c1', 'code');
    }
}
