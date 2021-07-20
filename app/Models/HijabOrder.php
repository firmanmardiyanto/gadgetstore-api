<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HijabOrder extends Model
{
    protected $table = 'hijab_order';
    protected $fillable = [
        'hijab_id', 'order_id', 'quantity'
    ];
}
