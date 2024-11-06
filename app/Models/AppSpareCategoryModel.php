<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSpareCategoryModel extends Model
{
    use HasFactory;

    protected $table = 't_app_spare_category';

    protected $fillable = [
        'sub_category_id',
        'name',
        'category_image',
    ];

    // Define the relationship: A category has many products, joined by the 'name' column
    public function products()
    {
        return $this->hasMany(ProductModel::class, 'spare_category', 'name');
    }
}
