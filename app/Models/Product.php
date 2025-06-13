<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'category_id',
    ];

    // funzione di relazione con categoria
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
