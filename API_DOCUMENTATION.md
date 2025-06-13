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

- **Payload:** 
```json 
{ 
"email": "admin@test.com",
"password": "password"
}
```
> Utilizzare le credenziali indicate per testing. Sono presenti nel seeder.

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

| Campo          | Tipo di dato   | Descrizione                |
| -------------- | -------------- | -------------------------- |
| Code           | Stringa        | Chiave primaria            |
| Name           | Stringa        | Nome del prodotto          |
| *Description*  | *Stringa*      | *Descrizione del prodotto* |
| Price          | Float          | Prezzo del prodotto        |
| *Category_id*  | *Integer*      | *Foreign key di categorie* |
>I campi in corsivo non sono obbligatori.

 


