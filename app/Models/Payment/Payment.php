<?php

namespace App\Models\Payment;

use App\Models\Order\Order;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'total_amount',
        'payment_method',
        'transaction_id',
        'discount',
        'unit_price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
