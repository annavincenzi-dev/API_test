<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'tab' => 'required|string',
            'code' => 'required|string|max:4',
            'updates' => 'required|array|min:1',
            'updates.*.value' => 'required',

        ];

        // per la categoria non è necessario specificare il campo: l'unico modificabile è name
        switch ($this->tab) {
            case 'categorie':
                $rules['updates.*.field'] = 'required|string|in:name';
                break;
            default:
                $rules['updates.*.field'] = 'required|string|in:name,description,price,category_id';
                break;
        }

        return $rules;
    }

    


    public function messages(): array
    {
        //il messaggio cambia a seconda del modello
        switch($this->tab){
            case 'categorie':
                $modelMessage = "ID Categoria";
                break;
            default:
                $modelMessage = "Codice Prodotto"; 
                break;
        }

        // campi modificabili
        switch($this->tab){
            case 'categorie':
                $updatables = "name";
                break;
            default:
                $updatables = "name, description, price, category_id";
                break;
        }

        $messages = [
            'tab.required' => 'Per favore, inserisci la tabella di riferimento',
            'tab.string' => 'Tabella di riferimento non valida.',
            'code.required' => "Per favore, inserisci un $modelMessage da modificare",
            'code.string' => "Per favore, inserisci un $modelMessage valido.",
            'code.max' => "$modelMessage supera la lunghezza consentita di 4 caratteri.",
            'updates.*.field.required' => "Campo da modificare richiesto. Campi modificabili: $updatables",
            'updates.*.field.string' => 'Il campo inserito non è valido',
            'updates.*.field.in' => "Campo non valido. Campi modificabili: $updatables",
            'updates.*.value.required' => 'Per favore, inserisci il nuovo valore del campo da aggiornare',
        ];

        return $messages;
    }
}
