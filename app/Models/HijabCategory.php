<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HijabCategory extends Model
{
    protected $table = 'hijab_category';
    protected $fillable = [
        'hijab_id', 'category_id', 'invoice_number', 'status'
    ];
}
