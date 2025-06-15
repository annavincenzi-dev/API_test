# API DOCUMENTATION

## Indice
- [Introduzione](#introduzione)
- [Authentication Endpoints](#authentication-endpoints)
- [Insert/Update Endpoints](#insertupdate-endpoints)
- [Tabelle e modelli](#tabelle-e-modelli)
- [Implementazione in progetti esistenti](#implementazione-in-progetti-esistenti)
- [Testing con Rest Client](#testing-con-rest-client)



## Introduzione

Questa API REST permette di inserire e aggiornare record nelle tabelle del database mappate su modelli Laravel, selezionate dinamicamente tramite un parametro `tab`.

L’ambiente è locale, su rete interna protetta.  
L’autenticazione è gestita tramite **Laravel Sanctum**.

---

## Authentication Endpoints

### Login

- **Endpoint:** `POST /api/login`
- **Esempio di payload:** 
```json 
{ 
"email": "admin@test.com",
"password": "password"
}
```
> L'utente deve già essere registrato tramite seeder o php artisan tinker.
- **Risposta**:
    - *200 OK* se l'autenticazione è andata a buon fine: ci verrà fornito il token da utilizzare nelle prossime chiamate
    - *401 Unauthorized* se le credenziali sono errate

---

### Logout

- **Endpoint:** `POST /api/logout`
- **Headers:**
    - `Accept: application/json`
    - `Authorization: Bearer {token}`

- **Risposta:** revoca del token di autenticazione.

---

## Insert/Update Endpoints

### Insert

- **Endpoint:** `POST /api/insert`
- **Headers:**
    - `Authorization: Bearer {token}`
    - `Accept: application/json`
    - `Content-Type: application/json`
- **Esempio di Payload:**
```json
{
  "tab": "prodotti",
  "data": [
    {
      "codice": "P001",
      "nome": "Prodotto A",
      "descrizione": "Descrizione del prodotto A",
      "prezzo": 5
    },
    {
      "codice": "P002",
      "nome": "Prodotto B",
      "descrizione": "Descrizione del prodotto B",
      "prezzo": 5
    }
  ]
}
```
- **Risposta:**
    - *200 OK* se l'inserimento è andato a buon fine, con eventuale counter dei record inseriti.
    - *422 Unprocessable Entity* se la validazione è fallita. Verranno mostrati i dettagli di cosa è andato storto.

---

### Update

- **Endpoint**: POST /api/update
- **Headers:**
    - `Authorization: Bearer {token}`
    - `Accept: application/json`
    - `Content-Type: application/json`
- **Esempio di Payload:**
```json
{
  "tab": "prodotti",
  "code" : "P101",
  "field" : "campo",
  "value" : "valore" 
}
```

- **Risposta:**
    - *200 OK* se la modifica del record è andata a buon fine.
    - *422 Unprocessable Entity* se la validazione è fallita. Verranno mostrati i dettagli di cosa è andato storto.
---

## Tabelle e Modelli

### Tabella **Products**

| Campo          | Tipo di dato   | Descrizione                |
| -------------- | -------------- | -------------------------- |
| Code           | Stringa        | Chiave primaria            |
| Name           | Stringa        | Nome del prodotto          |
| *Description*  | *Stringa*      | *Descrizione del prodotto* |
| Price          | Float          | Prezzo del prodotto        |
| *Category_id*  | *Integer*      | *Foreign key di categorie* |
>I campi in corsivo non sono obbligatori.

---

### Tabella **Categories**

| Campo   | Tipo di dato   | Descrizione            |
| ------- | -------------- | ---------------------- |
| ID      | Integer        | Chiave primaria        |
| Name    | Stringa        | Nome della categoria   |

---

## Implementazione in progetti esistenti

### Indice Setup

- [Sanctum](#setup-sanctum)
- [Routes](#setup-routes)
- [Controllers](#setup-controllers)
- [Models](#setup-models)
- [Migrations](#setup-migrations) oppure [SQL](#or-setup-sql)
- [Requests](#setup-form-requests)
- [Services](#setup-services)
- [Interfaccia](#setup-interfaccia)

---

### Setup **Sanctum**

- Nel terminale: 
```bash
composer require laravel/sanctum
```
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```
```bash
php artisan migrate
```

-Nel modello `User.php`
```php
class User extends Authenticatable
{
    use HasApiTokens, ...;

    code ...
}
```

---

### Setup **Routes**

- Nel terminale: 
```bash
php artisan install:api
```

- Nel file `/routes/api.php`
```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\DataController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/insert', [DataController::class, 'insert']);
    Route::post('/update', [DataController::class, 'update']);
});
```

---

### Setup **Controllers**

- Nel terminale
```bash
php artisan make:controller AuthController
```
```bash
php artisan make:controller Api/DataController
```

- Nel file `AuthController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request){

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if(! $user || ! Hash::check($request->password, $user->password)){

            return response()->json([
                'error' => 'Credenziali errate. Verifica e riprova!'
            ], 422);
            
        }

        $token = $user->createToken('api-token')->plainTextToken;

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
```

- Nel file `/Api/DataController.php`
```php
//codice da aggiornare con metodo update
```
---

### Setup **Models**

- Nel terminale 
```bash
php artisan make:model Product -m && php artisan make:model Category -m
```

- Nel file `/Models/Category.php`
```php
<?php

namespace App\Models;

use App\Models\Product;
use App\Contracts\ModelValidator;
use Illuminate\Database\Eloquent\Model;

class Category extends Model implements ModelValidator
{
    protected $fillable = [
        'name',
    ];

    public static function recordValidator($record, $updating = false){
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public static function recordValidatorMessages(){
            
        return [
            'name.required' => 'Nome della categoria obbligatorio',
            'name.string' => 'Il nome della categoria deve essere una stringa',
            'name.max' => 'Il nome della categoria supera la lunghezza consentita di 255 caratteri',
        ];
    }
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

```

- Nel file `/Models/Product.php`
```php
<?php

namespace App\Models;

use Illuminate\Validation\Rule;
use App\Contracts\ModelValidator;
use Illuminate\Database\Eloquent\Model;

class Product extends Model implements ModelValidator
{

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'category_id',
    ];

    public static function recordValidator($record, $updating = false){ù

        $rules = [
            'code' => ['required','string','max:4'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];

        if (!$updating) {
        $rules['code'][] = Rule::unique('products', 'code');
        }

        return $rules;
    }

    public static function recordValidatorMessages(){
            
        return [
            'code.required' => "Codice prodotto obbligatorio",
            'code.string' => "Il codice del prodotto deve essere una stringa",
            'code.max' => "Il codice del prodotto supera la lunghezza consentita di 4 caratteri",
            'code.unique' => 'Il codice del prodotto deve essere univoco',
            'name.required' => 'Nome del prodotto obbligatorio',
            'name.string' => 'Il nome del prodotto deve essere una stringa',
            'name.max' => 'Il nome del prodotto supera la lunghezza consentita di 255 caratteri',
            'description.string' => 'La descrizione del prodotto deve essere una stringa.',
            'description.max' => 'La descrizione del prodotto supera la lunghezza massima di 255 caratteri.',
            'price.required' => 'Prezzo del prodotto obbligatorio.',
            'price.numeric' => 'Il prezzo del prodotto deve essere un numero.',
            'price.min' => 'Il prezzo del prodottodeve essere maggiore o uguale a 0.',
            'category_id.exists' => 'La categoria di prodotti specificata non esiste.',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

---

### Setup **Migrations**

- Nel file `/database/migrations/xxxx_xx_xx_create_products_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('code')->primary();
            $table->string('name');
            $table->string('description');
            $table->float('price');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('categories');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

```

- Nel file `/database/migrations/xxxx_xx_xx_create_categories_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

```

### OR Setup **SQL**

- Puoi eseguire questi script SQL per la creazione delle tabelle di prova.

- Per la tabella `Products`

```sql
CREATE TABLE products (
  code VARCHAR(4) PRIMARY KEY NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255),
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  category_id INT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

- Per la tabella `Categories`
```sql
CREATE TABLE categories(
    id INT AUTO_INCREMENT PRIMARY KEY, 
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

### Setup **Form Requests**

- Nel terminale:
```bash
php artisan make:request InsertRequest && php artisan make:request UpdateRequest
```

- Nel file `InsertRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

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
```

- Nel file `UpdateRequest.php`
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'tab' => 'required|string',
            'code' => 'required|string|max:4',
            'value' => 'required',
        ];

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
```
---

### Setup **Services**

-In un nuovo file `app/Services/TabsMappingService.php`
```php
<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;

class TabsMappingService {

    public $tabName;

    protected $tabs = [
        'prodotti' => Product::class,
        'categorie' => Category::class,
    ];

    public function resolve($reqTab){
        $reqTab = strtolower($reqTab);

        switch($reqTab){
            case "1":
                $reqTab = 'prodotti';
                break;
            case "2":
                $reqTab = 'categorie';
                break;
            case 'prodotti':
            case 'categorie':
                break;
            default:
                return null;
        }

        if(array_key_exists($reqTab, $this->tabs)){
            $this->tabName = $reqTab;
            return $this->tabs[$reqTab];
        }

        return null;
    }
}
```

- In un nuovo file `app/Services/ModelValidatorService.php`
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class ModelValidatorService{
    public function validate($model, $data, $updating = false){
        
        $rules = $model::recordValidator($data, $updating);
        $messages = $model::recordValidatorMessages();

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return $validator;
        }else{
            return null;
        }
    }
}
```

### Setup **Interfaccia**

- In un nuovo file `app/Contracts/ModelValidator.php`
```php
<?php

namespace App\Contracts;

interface ModelValidator
{
    public static function recordValidator($record, $updating = false);
    public static function recordValidatorMessages();
}
```

## Testing con REST Client

1. ### Registrazione utente

    - Nel terminale
```bash
php artisan tinker
```
```php
use App\Models\User;
User::create([
    'name' => 'Mario',
    'email' => 'mario@rossi.com',
    'password' => bcrypt('password123'),
]);
```

2. ### Creazione del file di testing
    - In un nuovo file `api_test.http`
```http
### LOGIN
POST http://127.0.0.1:8000/api/login
Content-Type: application/json

{
  "email": "mario@rossi.com",
  "password": "password123"
}

### CREAZIONE CATEGORIA
POST http://127.0.0.1:8000/api/insert
Accept: application/json
Content-Type: application/json
Authorization: Bearer {il tuo token}

{
  "tab": "categorie",
  "data": [
    {
      "name": "TESTING CATEGORY"
    }
  ]
}

### CREAZIONE PRODOTTO
POST http://127.0.0.1:8000/api/insert
Content-Type: application/json
Authorization: Bearer {il tuo token}

{
  "tab": "prodotti",
  "data": [
    {
      "code": "P000",
      "name": "Prodotto 1",
      "description": "Descrizione prodotto 1",
      "price": 10.50,
      "category_id": 1
    },
    {
      "code": "P001",
      "name": "Prodotto 2",
      "description": "Descrizione prodotto 2",
      "price": 20.00,
      "category_id": 1
    },
    {
      "code": "P002",
      "name": "Prodotto 3",
      "description": "Descrizione prodotto 3",
      "price": 15.75,
      "category_id": 2
    }
  ]
}

### MODIFICA CATEGORIA
POST http://127.0.0.1:8000/api/update
Content-Type: application/json
Accept: application/json
Authorization: Bearer {il tuo token}

{
  "tab": "2",
  "code": "2",
  "field": "name",
  "value": "CATEGORIA MODIFICATA"
}

### MODIFICA PRODOTTO
POST http://127.0.0.1:8000/api/update
Content-Type: application/json
Accept: application/json
Authorization: Bearer {il tuo token}

{
  "tab": "1",
  "code": "2",
  "field": "name",
  "value": "CATEGORIA MODIFICATA"
}

### LOGOUT
POST http://127.0.0.1:8000/api/logout
Accept: application/json
Authorization: Bearer {il tuo token}
```  

3. ### Run dei test
    - Installare l'estensione di VSCode **REST Client**


 


