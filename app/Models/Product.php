<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //Con le variabili di Eloquent
    //imposto come chiave primaria il codice
    protected $primaryKey = 'code';
    //non dev'essere incrementale ->
    public $incrementing = false;
    //perché è una stringa
    protected $keyType = 'string';

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
