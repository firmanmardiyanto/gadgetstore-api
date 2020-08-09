<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GadgetCategory extends Model
{
    protected $table = 'gadget_category';
    protected $fillable = [
        'gadget_id', 'category_id', 'invoice_number', 'status'
    ];
}
