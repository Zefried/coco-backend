<?php

namespace App\Models\Category;

use App\Models\Product\Product;
use App\Models\SubCategory\SubCategory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status'];


    public function product() {
        return $this->hasMany(Product::class);
    }

    public function subCategories() {
        return $this->hasMany(SubCategory::class, 'category_id');
    }



}
