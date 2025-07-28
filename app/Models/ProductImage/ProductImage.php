<?php

namespace App\Models\ProductImage;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image',
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
