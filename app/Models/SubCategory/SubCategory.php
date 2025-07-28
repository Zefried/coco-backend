<?php

namespace App\Models\SubCategory;

use App\Models\Category\Category;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $fillable = ['category_id','name', 'slug', 'description', 'status'];

    public function Product() {
        return $this->hasMany(Product::class);
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
