# API DOCUMENTATION

## Introduzione

Questa API REST permette di inserire e aggiornare record nelle tabelle del database mappate su modelli Laravel, selezionate dinamicamente tramite un parametro `tab`.

L’ambiente è locale, su rete interna protetta.  
L’autenticazione è gestita tramite **Laravel Sanctum**.

---

## Authentication

--- 

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

- **Risposta:** revoca del token di autenticazione.

---

## Endpoints

---

### Insert
- **Endpoint:** `POST /api/insert`

- **Headers:**
    - `Authorization: Bearer {token*}`
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

- **Validazione**
    - `tab` deve corrispondere ad una tabella esistente.
    - ogni dato inserito in `data` viene validato

- **Risposta:**
    - *200 OK* se l'inserimento è andato a buon fine, con eventuale counter dei record inseriti.ù
    - *422 Unprocessable Entity* se la validazione è fallita. Verranno mostrati i dettagli di cosa è andato storto.

---

### Update
> Ancora da implementare!

---

## Tabelle e Modelli

---

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

### Setup **Routes API**
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

### Setup **Controller**

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
            'access_token' => $token,
            'token_type' => 'Bearer'
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
<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class DataController extends Controller
{
    protected $tabsMapping = [
        'prodotti' => Product::class,
        'categorie' => Category::class,
    ];


    protected function getTable($tab){
        return $this->tabsMapping[$tab] ?? null;
    }

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

    public function insert(Request $request){

        $request->validate([
            'tab' => 'required|string',
            'data' => 'required|array|min:1',
        ]);

        $tab = strtolower($request->tab);

        if($tab == 1){
            $tab = 'prodotti';
        } else if($tab == 2){
            $tab = 'categorie';
        } else if($tab != 'prodotti' && $tab != 'categorie'){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }

        $modelClass = $this->getTable($tab);
        $counter = 0;
        
        foreach($request->data as $index => $record){
            
            if ($tab === 'prodotti') {
                $errorResponse = $this->validateProduct($record, $index);
                if ($errorResponse) return $errorResponse;
            } else if ($tab === 'categorie') {
                $errorResponse = $this->validateCategory($record, $index);
                if ($errorResponse) return $errorResponse;
            }

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


```

---

### Setup **Models**

- Nel terminale 

```bash
php artisan make:model Product -m
```

```bash
php artisan make:model Category -m
```

- Nel file `/Models/Category.php`
```php
<?php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
    ];
    
    //funzione di relazione con prodotti
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

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'category_id',
    ];

    // funzione di relazione con categoria
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
            $table->string('code')->primary(); //utilizzo come chiave primaria il codice del prodotto
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




 


