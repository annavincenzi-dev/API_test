<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;

class TabsMappingService {

    //creo la mappa delle tabelle.
    protected $tabs = [
        'prodotti' => Product::class,
        'categorie' => Category::class,
    ];

    //trasformo i dati inseriti dall'utente per il riconoscimento del modello.
    public function resolve($reqTab){
        $reqTab = strtolower($reqTab);

        //l'utente può indicare le tabelle con i numeri. 
        switch($reqTab){
            case "1":
                $reqTab = 'prodotti';
                break;
            case "2":
                $reqTab = 'categorie';
                break;
            case 'prodotti': 
            case 'categorie':
                break;
            default:
                return null;
        }

        //verifico se la tabella esiste
        return $this->tabs[$reqTab] ?? null;
    }
}