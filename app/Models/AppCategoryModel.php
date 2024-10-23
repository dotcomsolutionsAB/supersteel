<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppCategoryModel extends Model
{
    use HasFactory;

    protected $table = 't_app_category';

    protected $fillable = [
        'name',
        'category_image',
    ];

    // Define the relationship: A category has many products, joined by the 'name' column
    public function get_products()
    {
        return $this->hasMany(ProductModel::class, 'c1', 'code');
    }

    // A category has many subcategories
    public function subcategories()
    {
        return $this->hasMany(AppSubCategoryModel::class, 'category_id', 'id');
    }
}
