<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Requests\InsertRequest;
use App\Http\Requests\UpdateRequest;
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
    public function insert(InsertRequest $request){

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
    public function update(UpdateRequest $request){

        //richiamo al service per la risoluzione del modello inserito dall'utente
        $tab = $this->tabsMappingService->resolve($request->tab);

        //se la tabella non esiste, restituisco json di errore
        if(!$tab){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }

        //trovo il record da aggiornare attraverso il codice/ID
        $record = $this->tabsMappingService->findRecordbyCode($this->tabsMappingService->tabName, $request->code);

        if(!$record){
            $message = $this->tabsMappingService->tabName == 'prodotti' ? 'Nessun prodotto trovato con questo codice.' : 'Nessuna categoria trovata con questo ID.';
            return response()->json([
                'error' => $message
            ], 422);
        }

        $dataToUpdate = [];
        foreach ($request->input('updates') as $update) {
        $field = $update['field'] ?? 'name'; //possibile solo per la classe categoria
        $dataToUpdate[$field] = $update['value'];
        }

        $validator = $this->modelValidatorService->validate($tab, $dataToUpdate, true);
        
        if ($validator) {
            return response()->json([
                'error' => "Errore nell'inserimento dei dati",
                'details' => $validator->messages()        
            ], 422);
        }


        foreach($dataToUpdate as $key => $value){
            $record->$key = $value;
        }
      
        $record->save();

        return response()->json(['message' => 'Aggiornamento del record completato!'], 200);        
    }

}
