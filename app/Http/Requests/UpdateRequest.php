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
            'value' => 'required',
        ];

        // per la categoria non è necessario specificare il campo: l'unico modificabile è name
        switch ($this->tab) {
            case 'categorie':
                $rules['field'] = 'string';
                break;
            default:
                $rules['field'] = 'required|string';
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

        $messages = [
            'tab.required' => 'Per favore, inserisci la tabella di riferimento',
            'tab.string' => 'Tabella di riferimento non valida.',
            'code.required' => "Per favore, inserisci un $modelMessage da modificare",
            'code.string' => "Per favore, inserisci un $modelMessage valido.",
            'code.max' => "$modelMessage supera la lunghezza consentita di 4 caratteri.",
            'field.required' => 'Campo da modificare richiesto. Campi modificabili: name, description, price, category_id',
            'field.string' => 'Il campo inserito non è valido',
            'value.required' => 'Per favore, inserisci il nuovo valore del campo da aggiornare',
        ];

        return $messages;
    }
}
