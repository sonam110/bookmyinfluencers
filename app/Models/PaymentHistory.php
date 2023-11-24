<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'bal_type',
        'plan_id',
        'customer_id',
        'order_id',
        'receipt',
        'payment_id',
        'entity',
        'currency',
        'old_amount',
        'amount',
        'new_amount',
        'currency',
        'invoice_id',
        'method',
        'description',
        'refund_status',
        'amount_refunded',
        'captured',
        'email',
        'contact',
        'fee',
        'tax',
        'tax_amount',
        'error_code',
        'error_description',
        'error_reason',
        'card_id',
        'card_info',
        'bank',
        'wallet',
        'vpa',
        'acquirer_data',
        'status',
        'payemnt_status',
    ];

}
