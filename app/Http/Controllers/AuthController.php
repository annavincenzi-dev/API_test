<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //funzione di login
    public function login(Request $request){
        
        //Validazione dei dati
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        //Ricerca utente con e-mail indicata
        $user = User::where('email', $request->email)->first();

        //Nel caso l'utente o la password non corrispondano
        if(! $user || ! Hash::check($request->password, $user->password)){
            // ritorna errore 422
            return response()->json([
                'error' => 'Credenziali errate. Verifica e riprova!'
            ], 422);
            
        }

        // creazione del token di accesso
        $token = $user->createToken('api-token')->plainTextToken;

        //ritorna un json con il token
        return response()->json([
            'user' => $user->name,
            'message'=> 'Hai effettuato il login. Ecco il tuo preziosissimo token!',
            'access_token' => $token,
        ]);

    }

    public function logout(Request $request){
        $request->user()->tokens()->delete();

        return response()->json([
            'message'=>'Hai effettuato il logout.'
        ]);
    }
}
