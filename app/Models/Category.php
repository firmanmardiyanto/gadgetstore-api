<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'image', 'status'
    ];

    public function gadgets(){
        return $this->belongsToMany('App\Models\Gadget', 'gadget_category', 'category_id', 'gadget_id');
    }
}
