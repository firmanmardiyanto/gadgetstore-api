<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'image', 'status'
    ];

    public function hijabs(){
        return $this->belongsToMany('App\Models\Hijab', 'hijab_category', 'category_id', 'hijab_id');
    }
}
