<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //solo se l'utente è loggato con token
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tab' => 'required|string',
            'data' => 'required|array|min:1',
        ];
    }

    public function messages(): array
    {
        $messages = [
            'tab.required' => 'Per favore, inserisci la tabella di riferimento',
            'tab.string' => 'Tabella di riferimento non valida.',
            'data.array' => 'Inserisci i dati in formato: ARRAY',
            'data.min' => 'Inserisci almeno un dato valido.',
        ];

        // il messaggio varia a seconda del modello
        switch ($this->tab) {
            case 'prodotti':
                $messages['data.required'] = 'Per favore, inserisci i dati del prodotto. Dati obbligatori: codice, nome, prezzo; Dati facoltativi: descrizione, ID categoria';
                break;
            default:
                $messages['data.required'] = 'Per favore, inserisci il nome della categoria';
                break;
        }

        return $messages;
    } 
}
