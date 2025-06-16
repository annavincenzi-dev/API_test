# Implementazione in progetti esistenti

## Indice
- [Setup Sanctum](#setup-sanctum)
- [Setup Routes](#setup-routes)
- [Setup Controllers](#setup-controllers)
- [Setup Models](#setup-models)
- [Setup Migrations](#setup-migrations) oppure [SQL](#or-setup-sql)
- [Setup Requests](#setup-form-requests)
- [Setup Services](#setup-services)
- [Setup Interfaccia](#setup-interfaccia)

---

## Setup **Sanctum**

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

## Setup **Routes**

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

## Setup **Controllers**

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

    public function __construct(TabsMappingService $tabsMappingService, ModelValidatorService $modelValidatorService){
        $this->tabsMappingService = $tabsMappingService;
        $this->modelValidatorService = $modelValidatorService;
    }

    public function insert(Request $request){

        $tab = $this->tabsMappingService->resolve($request->tab);

        if(!$tab){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }

        $counter = 0;

        foreach($request->data as $record){
            
            switch($tab){
                case 'prodotti':
                    $validator=$this->modelValidatorService->validate($tab, $record);
                    break;
                default:
                    $validator=$this->modelValidatorService->validate($tab, $record);
                    break;
            }

            if ($validator){
                return response()->json([
                    'error' => "Errore nell'inserimento dei dati",
                    'details' => $validator->messages()
                ], 422);
            }

            $tab::create($record);
            $counter++;
        }

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

    public function update(Request $request){

        $tab = $this->tabsMappingService->resolve($request->tab);

        if(!$tab){
            return response()->json([
                'error' => 'La tabella selezionata non esiste! :('
            ], 422);
        }
        $record = $this->tabsMappingService->findRecordbyCode($this->tabsMappingService->tabName, $request->code);

        if(!$record){
            $message = $this->tabsMappingService->tabName == 'prodotti' ? 'Nessun prodotto trovato con questo codice.' : 'Nessuna categoria trovata con questo ID.';
            return response()->json([
                'error' => $message
            ], 422);
        }

        $dataToUpdate = [];
        foreach ($request->input('updates') as $update) {
        $field = $update['field'] ?? 'name';
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
```
---

## Setup **Models**

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

    public static function recordValidator($record, $updating = false){
        
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
        ];

        if (!$updating) {
        $rules['code'] = ['required','string','max:4', Rule::unique('products', 'code')];
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

## Setup **Migrations**

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

## OR Setup **SQL**

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

## Setup **Form Requests**

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
            'updates' => 'required|array|min:1',
            'updates.*.value' => 'required',

        ];

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
        switch($this->tab){
            case 'categorie':
                $modelMessage = "ID Categoria";
                break;
            default:
                $modelMessage = "Codice Prodotto"; 
                break;
        }

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

```
---

## Setup **Services**

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

    public function findRecordbyCode($tabName, $code){

        $record = $this->tabs[$tabName]::where($tabName == 'prodotti' ? 'code' : 'id', $code)->first();

        return $record;  
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

## Setup **Interfaccia**

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