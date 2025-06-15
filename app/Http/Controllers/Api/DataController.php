<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Services\TabsMappingService;
use App\Services\ModelValidatorService;
use Illuminate\Support\Facades\Validator;


class DataController extends Controller
{
    protected $tabsMappingService;
    protected $modelValidatorService;

    //costruttore con dependency injection dei services
    public function __construct(TabsMappingService $tabsMappingService, ModelValidatorService $modelValidatorService){
        $this->tabsMappingService = $tabsMappingService;
        $this->modelValidatorService = $modelValidatorService;
    }



    //metodo INSERT
    public function insert(Request $request){

        //richiamo al service per la risoluzione del modello inserito dall'utente
        $tab = $this->tabsMappingService->resolve($request->tab);

        //se la tabella non esiste, restituisco json di errore
        if(!$tab){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }


        //creo la variabile counter per restituire il numero di record inseriti
        $counter = 0;
        
        
        foreach($request->data as $record){
            
            //valido ogni nuovo record inserito
            switch($tab){
                case 'prodotti':
                    $validator=$this->modelValidatorService->validate($tab, $record);
                    break;
                default:
                    $validator=$this->modelValidatorService->validate($tab, $record);
                    break;
            }

            //in caso la validazione fallisca, restituisco json di errore
            if ($validator){
                return response()->json([
                    'error' => "Errore nell'inserimento dei dati",
                    'details' => $validator->messages()
                ], 422);
            }

             //altrimenti, creo il record nella tabella
            $tab::create($record);
            $counter++;
        }
        
        //a seconda di quanti record ho inserito la risposta sarà diversa
        if($counter == 1){
            return response()->json([
                'message' => "Hai inserito un nuovo record nella tabella {$this->tabsMappingService->tabName}!"
            ]);
        } else if($counter > 1) {
            return response()->json([
                'message' => "Hai inserito $counter nuovi record nella tabella {$this->tabsMappingService->tabName}!"
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
