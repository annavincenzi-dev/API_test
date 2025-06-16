<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;

class TabsMappingService {

    //creo una variabile per la stringa corrispondente al nome della tabella
    public $tabName;
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

        //se la tabella richiesta esiste, ne restituisco la classe e il nome
        if(array_key_exists($reqTab, $this->tabs)){
            $this->tabName = $reqTab;
            return $this->tabs[$reqTab];
        }

        //altrimenti il service non va a buon fine
        return null;
    }

    public function findRecordbyCode($tabName, $code){

        $record = $this->tabs[$tabName]::where($tabName == 'prodotti' ? 'code' : 'id', $code)->first();

        return $record;
        
        

    }
}