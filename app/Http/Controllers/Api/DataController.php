<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class DataController extends Controller
{

    // Validazione record prodotti
    private function validateProduct($record, $updating = false){
            



        

        // validazione effettiva
        $validator = Validator::make($record, $rules, $messages);

        //se la validazione fallisce, la risposta è un messaggio di errore
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Errore di validazione nel record',
                'details' => $validator->errors()
            ], 422);
        }

            //se la validazione va a buon fine, il codice prosegue
            return null;
        }

    // validazione record categorie
    private function validateCategory($record, $updating = false){

        //regole di validazione
        $rules = [
            'name' => 'required|string|max:255',
        ];

        //messaggi di validazione
        $messages = [
            'name.required' => 'Nome obbligatorio',
            'name.string' => 'Il nome deve essere una stringa',
            'name.max' => 'Il nome supera la lunghezza consentita di 255 caratteri',
            'name.unique' => 'Il nome deve essere univoco',
        ];
        
        //valdazione effettiva
        $validator = Validator::make($record, $rules, $messages);

        //se la validazione fallisce, la risposta è un messaggio di errore
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Errore di validazione nel record ',
                'details' => $validator->errors()
            ], 422);
        }

        //se la validazione va a buon fine, il codice prosegue
        return null;

    }

    //metodo INSERT
    public function insert(Request $request){



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
        foreach($request->data as $record){
            
            if ($tab === 'prodotti') {
                $errorResponse = $this->validateProduct($record);
                if ($errorResponse) return $errorResponse;
            } else if ($tab === 'categorie') {
                $errorResponse = $this->validateCategory($record);
                if ($errorResponse) return $errorResponse;
            }

             //creo il record nella tabella
            $modelClass::create($record);
            $counter++;
            }
        //a seconda di quanti record ho inserito la risposta sarà diversa
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

    //metodo UPDATE
    public function update(Request $request){

        //trasformazione dei dati ricevuti per trovare la tabella
        $tab = strtolower($request->tab);

        //se l'utente ha inserito i numeri, li trasformo nelle stringhe corrispondenti 
        if($tab == 1){
            $tab = 'prodotti';
        } else if($tab == 2){
            $tab = 'categorie';
        } else if($tab != 'prodotti' && $tab != 'categorie'){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }

        //associo le variabili ai dati della request
        $modelClass = $this->getTable($tab);

        /*a seconda della tabella selezionata, verifico se esiste un oggetto della classe associato al code*/
        if($tab == 'prodotti'){

            $record = $modelClass::where('code', $request->code)->first();

            if(!$record){
            return response()->json([
                'error'=>"Nessun prodotto trovato con questo codice!"
            ], 422);

        }

        }else if($tab == 'categorie'){
            $record = $modelClass::where('id', $request->code)->first();
            
            if(!$record){
            return response()->json([
                'error'=>"Nessuna categoria trovata con questo codice!"
            ], 422);
        }
        }

        //verifico che il field inserito sia corretto

        /*creo un array con i campi consentiti, che varia a seconda della tabella selezionata*/
        $allowedFields = $tab == 'prodotti' ? ['name', 'description', 'price', 'category_id'] : ['name'];
        
        //verifico se il field selezionato è presente nell'array di controllo
        if(in_array($request->field, $allowedFields)){
            $field = $request->field;
        } else if($request->field == 'code'){
            return response()->json([
                'error' => 'Non puoi modificare il codice dei prodotti o categorie!'
            ]);
        }else {
            return response()->json([
                'error' => 'Campo inserito non valido!'
            ], 422);
        }

        //infine aggiorno il campo selezionato ed esistente con il nuovo valore.
        $value = $request->value;
        $record->$field = $value;

        //effettuo una nuova validazione del prodotto
        if($tab == 'prodotti'){
            $errorResponse = $this->validateProduct($record->toArray(), true);
            if ($errorResponse) return $errorResponse;
        } else if ($tab == 'categorie') {
            $errorResponse = $this->validateCategory($record->toArray(), true);
            if ($errorResponse) return $errorResponse;
        }

        //aggiorno il record
        $record->save();

        return response()->json([
            'message' => 'Record aggiornato con successo!'
        ], 200);
        
}
}
