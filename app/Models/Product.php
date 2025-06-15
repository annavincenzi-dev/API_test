<?php

namespace App\Models;

use Illuminate\Validation\Rule;
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

    public static function recordValidator($record, $updating = false){
        
        $rules = [
            'code' => 'required|string|max:4',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];

        // se non sto aggiornando il record, controllo se il codice è univoco
        /* l'unicità del campo code non è richiesta in fase di update per non creare errori di validazione. In ogni caso, non può essere modificato dopo la fase di creazione del record.*/
        if (!$updating) {
        $rules['code'][] = Rule::unique('products', 'code');
        }

        return $rules;
    }

    public static function recordValidatorMessages(){
            
        return [
            'code.required' => "Codice prodotto obbligatorio",
            'code.string' => "Il codice del prodotto deve essere una stringa",
            'code.max' => "Il codice del prodotto supera la lunghezza consentita di 4 caratteri",
            'code.unique' => 'Il codice del prodotto deve essere univoco',
            'name.required' => 'Nome del prodotto obbligatorio',
            'name.string' => 'Il nome del prodotto deve essere una stringa',
            'name.max' => 'Il nome del prodotto supera la lunghezza consentita di 255 caratteri',
            'description.string' => 'La descrizione del prodotto deve essere una stringa.',
            'description.max' => 'La descrizione del prodotto supera la lunghezza massima di 255 caratteri.',
            'price.required' => 'Prezzo del prodotto obbligatorio.',
            'price.numeric' => 'Il prezzo del prodotto deve essere un numero.',
            'price.min' => 'Il prezzo del prodottodeve essere maggiore o uguale a 0.',
            'category_id.exists' => 'La categoria di prodotti specificata non esiste.',
        ];
    }

    public static function recordUpdatableFields(){
        return ['name', 'description', 'price', 'category_id'];
    }

    // funzione di relazione con categoria
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
