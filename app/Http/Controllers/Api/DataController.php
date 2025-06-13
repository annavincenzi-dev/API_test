<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class DataController extends Controller
{
    //creo una mappatura delle tabelle attraverso i modelli
    protected $tabsMapping = [
        'prodotti' => Product::class,
        'categorie' => Category::class,
    ];


    //funzione che mi restituisce la tabella
    protected function getTable($tab){
        return $this->tabsMapping[$tab] ?? null;
    }

    // Validazione record prodotti
        function validateProduct($record, $index){
            
            $validator = Validator::make($record, [
            'code' => 'required|string|max:255|unique:products',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            ], [
            'code.required' => 'Codice obbligatorio',
            'code.string' => 'Il codice deve essere una stringa',
            'code.max' => 'Il codice supera la lunghezza consentita di 255 caratteri',
            'code.unique' => 'Il codice deve essere univoco',
            'name.required' => 'Nome obbligatorio',
            'name.string' => 'Il nome deve essere una stringa',
            'name.max' => 'Il nome supera la lunghezza consentita di 255 caratteri',
            'description.string' => 'La descrizione deve essere una stringa.',
            'description.max' => 'La descrizione supera la lunghezza massima di 255 caratteri.',
            'price.required' => 'Prezzo obbligatorio.',
            'price.numeric' => 'Il prezzo deve essere un numero.',
            'price.min' => 'Il prezzo deve essere maggiore o uguale a 0.',
            'category_id.exists' => 'La categoria specificata non esiste.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Errore di validazione nel record ' . ($index + 1),
                    'details' => $validator->errors()
                ], 422);
            }

            return null;
        }

    // validazione record categorie
        private function validateCategory($record, $index){
            $validator = Validator::make($record, [
            'name' => 'required|string|max:255|unique:categories',
            ], [
            'name.required' => 'Nome obbligatorio',
            'name.string' => 'Il nome deve essere una stringa',
            'name.max' => 'Il nome supera la lunghezza consentita di 255 caratteri',
            'name.unique' => 'Il nome deve essere univoco',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Errore di validazione nel record ' . ($index + 1),
                    'details' => $validator->errors()
                ], 422);
            }

            return null;
        }

    //metodo INSERT
    public function insert(Request $request){

        // validazione
        $request->validate([
            'tab' => 'required|string',
            'data' => 'required|array|min:1',
        ]);


        //trasformazione dei dati ricevuti
        //stringa in minuscolo
        $tab = strtolower($request->tab);

        //se l'utente ha inserito i numeri, li trasformo nelle stringhe corrispondenti
        //UX friendly 
        if($tab == 1){
            $tab = 'prodotti';
        } else if($tab == 2){
            $tab = 'categorie';
        } else if($tab != 'prodotti' && $tab != 'categorie'){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }

        //cerco la tabella corrispondente a tab
        $modelClass = $this->getTable($tab);
        //creo la variabile counter per restituire il numero di record inseriti
        $counter = 0;
        
        //ad ogni record nuovo inserito, associo un nuovo oggetto relativo alla tabella selezionata
        foreach($request->data as $index => $record){
            
            if ($tab === 'prodotti') {
                $errorResponse = $this->validateProduct($record, $index);
                if ($errorResponse) return $errorResponse;
            } else if ($tab === 'categorie') {
                $errorResponse = $this->validateCategory($record, $index);
                if ($errorResponse) return $errorResponse;
            }

             //creo il record nella tabella
            $modelClass::create($record);
            $counter++;
            }

        

        if($counter == 1){
            return response()->json([
                'message' => 'Hai inserito un nuovo record nella tabella ' . $tab . '!'
            ]);
        } else if($counter > 1) {
            return response()->json([
                'message' => "Hai inserito $counter nuovi record nella tabella $tab!"
            ]);
        } else {
            return response()->json([
                'message' => 'Nessun nuovo record inserito.'
            ], 200);
        }

    }
}
