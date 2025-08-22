<?php

namespace App\Models\Order;

use App\Models\Payment\Payment;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'address',
        'payment_id',
        'delivery_status',
        'payment_status',
        'order_date'
    ];

    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }
}
