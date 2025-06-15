<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
    ];

    public static function recordValidator($record, $updating = false){
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public static function recordValidatorMessages(){
            
        return [
            'name.required' => 'Nome della categoria obbligatorio',
            'name.string' => 'Il nome della categoria deve essere una stringa',
            'name.max' => 'Il nome della categoria supera la lunghezza consentita di 255 caratteri',
        ];
    }

    public static function recordUpdatableFields(){
        return [
            'name',
        ];
    }
    
    //funzione di relazione con prodotti
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
