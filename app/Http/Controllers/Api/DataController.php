<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DataController extends Controller
{
    //creo una mappatura delle tabelle attraverso i modelli
    protected $tabsMapping = [
        'prodotti' => Product::class,
        'categorie' => Category::class,
    ];


    protected function getTable($tab){
        return $this->tabsMapping[$tab] ?? null;
    }

    public function insert(){
        $request->validate([
            'tab' => 'required|string',
            'data' => 'required|array|min:1',
        ]);


        $tab = strtolower($request->tab);

        if($tab == 1){
            $tab = 'prodotti';
        } else if($tab == 2){
            $tab = 'categorie';
        } else if($tab != 'prodotti' && $tab != 'categorie'){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }
    }
}
