# Progettazione tecnica API dinamica con Laravel
## Vincenzi Anna

## Indice
- [Introduzione](#introduzione)
- [Obiettivi principali](#obiettivi-principali-dellapplicazione)
- [Stack Tecnologico](#stack-tecnologico-utilizzato)
- [Funzionamento generale](#funzionamento-generale)
- [File principali](#file-principali)
- [Insert/Update Endpoints](#insertupdate-endpoints)
- [Tabelle e modelli](#tabelle-e-modelli)
- [Guida all'implementazione in un progetto esistente](API_DOCUMENTATION.md)
- [Testing Demo](https://youtu.be/xXUp_UKUxT0)

---

## Introduzione

Ho progettato con Laravel una semplice API REST per gestire in modo dinamico l'inserimento e l'aggiornamento di record nelle tabelle prodotti e categorie.

### Obiettivi principali dell'applicazione
1. Ricevere il nome di una tabella e operare sui modelli ad essa corrispondenti ✔️
2. Esporre come endpoint **insert** e **update** con payload **JSON** ✔️
3. Gestire almeno due modelli/tabelle (prodotti + categorie) ✔️
4. Supportare l'autenticazione ✔️
5. Poter facilmente essere integrata in un progetto ✔️
6. Fornire una buona documentazione ✔️

---

## Stack Tecnologico Utilizzato

- Laravel (versione 12.18.0)
- Laravel Sanctum
- MySQL
- Composer

### Testing

- Rest Client extension for VSCode
- Postman 

---

## Funzionamento generale

1. Le richieste API di inserimento e aggiornamento passano attraverso `InsertRequest.php` e `UpdateRequest.php` per la validazione di base. 

2. Viene quindi determinato dinamicamente il modello corrispondente tramite `TabsMappingService.php`.

3. I singoli dati sono validati con regole specifiche definite nei modelli e raggruppate nell'interfaccia `ModelValidator.php`.

4. In caso di validazione positiva, i dati vengono salvati nella tabella del database corrispondente.

5. Tutte le risposte, sia in caso di successo che di errore, sono in formato JSON e con messaggi chiari.

---

## Approccio e vantaggi

- **Supporto nativo** di Sanctum per l'autenticazione
- **Validazione centralizzata** tramite form requests e **coerente** con l'interfaccia *Model Validator*.
- **Separation of concerns:** compiti ben divisi tra Controller, Models, Services e Requests.
- **Architettura scalabile:** è molto semplice aggiungere nuove tabelle e modelli seguendo lo stesso schema.

---

## File principali

### `App/Contracts/ModelValidator.php`

- Definisce **un'interfaccia** che ogni modello deve implementare.

- Obbliga ogni modello a definire **due metodi**:

    - `recordValidator()`: regole di **validazione** per i dati.

    - `recordValidatorMessages()`: **messaggi** personalizzati da mostrare in caso di errore di validazione.

- Permette una **validazione coerente e centralizzata** su modelli diversi: prodotti e categorie avranno gli stessi metodi applicati in modi differenti.

---

### `Http/Requests/InsertRequest.php`

- Gestisce la **validazione** di base della richiesta di **inserimento**.

- **Verifica** ulteriormente che l'**utente** sia **autenticato** (lo fa già il middleware di Sanctum).

- **Controlla** la presenza e correttezza dei parametri **tab** (tabella di destinazione) e **data** (array di record da inserire).

- Definisce **messaggi di errore specifici** a seconda della tabella coinvolta (prodotti o categorie).

---

### `Http/Requests/UpdateRequest.php`

- Gestisce la **validazione** della richiesta di **aggiornamento**.

- **Verifica** ulteriormente che l'**utente** sia **autenticato** (lo fa già il middleware di Sanctum).

- Controlla la presenza e correttezza dei parametri **tab** (tabella), **code** (codice del prodotto oppure ID della categoria), **field** (campo da modificare) e **value** (nuovo valore).

-  Definisce **messaggi di errore specifici** a seconda della tabella coinvolta (prodotti o categorie).

---

### `Http/Controllers/Api/DataController.php`

- Controller principale dell'applicazione che **gestisce le chiamate API** per l’inserimento e l’aggiornamento dinamico dei dati.

- Quando riceve la richiesta, **identifica la tabella** tramite il servizio `TabsMappingService.php`.

- Per ogni record da inserire o aggiornare:
    - Effettua la **validazione** tramite `ModelValidatorService.php`.
    - In caso di errori, restituisce **messaggi dettagliati** in JSON.
    - In caso di successo, **salva i dati nel database** e restituisce un messaggio di successo.

---

### `Http/Services/ModelValidatorService.php`

- Servizio che astrae la logica di **validazione dei dati**.

- Riceve il modello, i dati da validare e un valore booleano che indica se si tratta di un update o di un inserimento.

- Chiama i metodi del modello per ottenere regole e messaggi di validazione.

- **Restituisce** un oggetto `Validator` in caso di **errori**, oppure **null** se la validazione è **superata**.

--- 

### `Services/TabsMappingService.php`

- Servizio che astrae il **mapping** tra la stringa fornita dall’utente (es. “prodotti” o “1”) e la classe modello corrispondente.

- Mantiene una proprietà *tabName* per fornire il nome della tabella risolta durante l’elaborazione.

- Ritorna null in caso di tabella non riconosciuta.

---

### `Http/Models/Product.php`

- Modello **Eloquent** per la tabella prodotti.

- Implementa l’interfaccia `ModelValidator.php`.

- Definisce:
    - Chiave primaria **code**
    - Campi fillable per il **mass assignment**
    - **Regole di validazione specifiche**, adattate alle fasi di insert e update.
    - **Messaggi di errore** dettagliati.
    - **Relazione** one to one con Category.

---

### `Http/Models/Category.php`

- Modello **Eloquent** per la tabella categorie.

- Implementa l’interfaccia `ModelValidator.php`.

- Definisce:
    - Campo *name* come unico campo fillable.
    - **Regole di validazione specifiche**, adattate alle fasi di insert e update.
    - **Messaggi di errore** dettagliati.
    - **Relazione** one to many con Product.

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
  "code": "P001",
  "updates": [
    {
      "field": "name",
      "value": "Prodotto aggiornato"
    },
    {
      "field": "price",
      "value": 15.99
    }
  ]
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

## Guida all'implementazione in un progetto esistente
[Guida all'utilizzo dell'API](API_DOCUMENTATION.md)

---

## Testing Demo
[Video demo](https://youtu.be/xXUp_UKUxT0)

