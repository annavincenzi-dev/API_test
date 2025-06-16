<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class ModelValidatorService{
    public function validate($model, $data, $updating = false){
        
        $rules = $model::recordValidator($data, $updating);
        $messages = $model::recordValidatorMessages();

        $validator = Validator::make($data, $rules, $messages);

        //se la validazione fallisce, ritorno il validator per visualizzarne i messaggi.
        if ($validator->fails()) {
            return $validator;
        }else{
            return null;
        }
        
    }
}