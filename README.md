# Progettazione tecnica API dinamica con Laravel
## Vincenzi Anna
---
## Pre-coding
### Quali sono gli obiettivi del progetto?
L'obiettivo di questa prova è la progettazione di un'API REST dinamica che:
1. Riceva il nome di una tabella e operi sui modelli ad essa corrispondenti;
2. Esponga come endpoint **insert** e **update** con payload **JSON**
3. Gestisca almeno due modelli/tabelle (prodotti + extra)
4. Supporti l'autenticazione
5. Sia ben documentata
6. Possa facilmente essere integrata in un progetto

---

## Stack Tecnologico Utilizzato

- Laravel (versione 12.18.0)
- Laravel Sanctum
> l'ho scelto perché l'ho trovato una valida opzione per l'autenticazione tramite token ed è fornito direttamente dal framework.
- MySQL
- Composer
- Rest Client extension for vscode
>comodissima nella fase di testing

[Guida all'utilizzo dell'API](API_DOCUMENTATION.md)