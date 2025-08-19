<?php

namespace App\Models\Product;

use App\Models\cart;
use App\Models\Category\Category;
use App\Models\ProductImage\ProductImage;
use App\Models\SubCategory\SubCategory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
   
     protected $fillable = [
        'category_id',
        'subcategory_id',
        'name',
        'description',
        'clay_type',
        'firing_method',
        'glaze_type',
        'dimensions',
        'weight',
        'price',
        'discount_percent',
        'stock_quantity',
        'is_fragile',
        'is_handmade'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function carts()
    {
        return $this->hasMany(cart::class, 'product_id');
    }
}
