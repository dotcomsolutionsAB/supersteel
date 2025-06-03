<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryModel extends Model
{
    use HasFactory;

    protected $table = 't_category';

    protected $fillable = [
        'cat_1',
        'cat_2',
        'cat_3',
        'name',
        'category_image',
        'preview'
    ];

    // Define the relationship: A category has many products, joined by the 'name' column
    public function get_products()
    {
        return $this->hasMany(ProductModel::class, 'c1', 'code');
    }

    // Method to determine the category_id dynamically
    public function getCategoryIdAttribute()
    {
        if (!empty($this->cat_3)) {
            return $this->cat_3;
        } elseif (!empty($this->cat_2)) {
            return $this->cat_2;
        }
        return $this->cat_1;
    }
}
