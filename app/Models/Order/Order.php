<?php

namespace App\Models\Order;

use App\Models\Payment\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'item_name',
        'products',
        'quantity',
        'address',
        'pin',
        'payment_id',
        'delivery_status',
        'payment_status',
        'order_date'
    ];

    public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
