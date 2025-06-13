# API DOCUMENTATION

## Introduzione

Questa API REST permette di inserire e aggiornare record nelle tabelle del database mappate su modelli Laravel, selezionate dinamicamente tramite un parametro `tab`.

L’ambiente è locale, su rete interna protetta.  
L’autenticazione è gestita tramite **Laravel Sanctum**.

---

## Authentication

### Login
**Endpoint:** `POST /api/login`
**Payload:** ```json
{
  "email": "admin@test.com",
  "password": "password"
}
> Utilizzare le credenziali indicate per testing. Sono presenti nel seeder.

Come risposta otterremo:
- **200 OK** se l'autenticazione è andata a buon fine: ci verrà fornito il token da utilizzare nelle prossime chiamate
- **401 Unauthorized** se le credenziali sono errate


### Logout
**Endpoint:** `POST /api/logout`

Come risposta otterremo la revoca del token di autenticazione.

---

## Endpoints

### Insert