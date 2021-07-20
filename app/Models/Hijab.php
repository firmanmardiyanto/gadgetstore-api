<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hijab extends Model
{

    protected $fillable =[
        'merk', 'slug', 'description', 'seller', 'cover', 'price', 'weight', 'stock', 'status'
    ];

    public function categories() {
        return $this->belongsToMany('App\Models\Category', 'hijab_category', 'category_id', 'hijab_id');
    }
}
