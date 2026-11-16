# TicketDesk

Applicazione web per la gestione dei ticket di assistenza, sviluppata con **Laravel 13, React 19 e TypeScript**.

TicketDesk permette a più aziende di gestire le proprie richieste di supporto attraverso un'unica applicazione, mantenendo separati utenti, ticket e categorie. Ogni utente ha un ruolo che determina quali informazioni può visualizzare e quali operazioni può effettuare.

Il progetto comprende un backend REST, un'interfaccia web responsive e una suite di test automatizzati.

---

## Indice

1. Panoramica del progetto
2. Tecnologie utilizzate
3. Architettura e organizzazione del codice
4. Funzionalità implementate
5. Ruoli e autorizzazioni
6. Requisiti per l'installazione
7. Installazione guidata dopo il clone
8. Configurazione del database
9. Dati dimostrativi e credenziali
10. Compilazione e avvio dell'applicazione
11. Come provare TicketDesk
12. API REST
13. Test e controlli di qualità
14. Scelte tecniche e considerazioni progettuali
15. Risoluzione dei problemi più comuni

---

## 1. Panoramica del progetto

TicketDesk nasce per gestire il ciclo di vita delle richieste di assistenza all'interno di un'organizzazione.

Un utente può aprire un ticket per segnalare un problema, descrivere la richiesta e associarla a una categoria. Gli operatori autorizzati possono prendere in carico il ticket, aggiornarne lo stato e aggiungere commenti per seguire l'avanzamento della lavorazione.

L'applicazione è stata progettata considerando tre esigenze principali:

- **Separazione dei dati:** ogni azienda accede esclusivamente alle informazioni di propria competenza.
- **Controllo degli accessi:** le operazioni disponibili dipendono dal ruolo e, in alcuni casi, dall'assegnazione del ticket.
- **Semplicità d'utilizzo:** le funzionalità principali sono accessibili attraverso un'interfaccia responsive, senza dover interagire direttamente con le API.

### Ciclo di vita di un ticket

Un ticket può trovarsi in uno dei seguenti stati:

| Stato | Significato |
|---|---|
| `open` | Il ticket è aperto e attende la lavorazione |
| `in_progress` | Il ticket è in lavorazione |
| `closed` | La richiesta è stata chiusa |

Le transizioni non sono completamente libere: il backend verifica che il cambiamento richiesto sia consentito e che l'utente disponga dei permessi necessari.

Le priorità disponibili sono `low`, `medium` e `high`.

---

## 2. Tecnologie utilizzate

Il progetto è suddiviso in due applicazioni, contenute rispettivamente nelle directory `backend` e `frontend`.

### Backend

| Tecnologia | Utilizzo |
|---|---|
| PHP 8.3+ | Linguaggio del backend |
| Laravel 13 | Framework applicativo e API REST |
| Laravel Sanctum 4 | Autenticazione mediante token |
| MySQL | Persistenza dei dati |
| Eloquent ORM | Modelli e relazioni tra entità |
| Laravel Policies | Autorizzazioni e controllo degli accessi |
| Form Request / validazione Laravel | Validazione degli input |
| API Resources | Serializzazione delle risposte |
| Pest 5 | Test automatici |

**Perché Laravel?**

Laravel permette di organizzare in modo chiaro routing, logica applicativa, autorizzazioni, validazione e accesso al database.

Ho utilizzato gli strumenti già disponibili nel framework, evitando di introdurre livelli di astrazione non necessari per le dimensioni del progetto.

### Frontend

| Tecnologia | Utilizzo |
|---|---|
| React 19 | Interfaccia utente |
| TypeScript 6 | Tipizzazione e controllo statico |
| Vite 8 | Server di sviluppo e compilazione |
| React Router | Navigazione tra le pagine |
| Tailwind CSS 4 | Stili e layout responsive |
| Radix UI / shadcn/ui | Componenti e primitive di interfaccia |
| Magic UI | Effetti e dettagli visivi |
| TanStack Query | Gestione delle richieste e dei dati remoti |
| React Hook Form | Gestione dei form |
| Zod | Definizione e validazione degli schemi |
| Lucide React | Icone |
| Sonner | Notifiche e feedback |

**Perché React e TypeScript?**

React permette di separare l'interfaccia in componenti riutilizzabili, mentre TypeScript rende più esplicite le strutture dati scambiate con il backend e aiuta a individuare errori durante lo sviluppo.

Le funzionalità sono organizzate per area applicativa, così che pagine, componenti e logica possano essere mantenuti senza concentrare tutto in un unico file.

### Database

È stato scelto **MySQL**, utilizzando le migration Laravel per definire e versionare la struttura del database.

Il database viene popolato attraverso un seeder dedicato, così da fornire un ambiente dimostrativo utilizzabile fin dalla prima installazione.

---

## 3. Architettura e struttura del progetto

La repository contiene due directory principali:

```text
ticketdesk/
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   │       └── Api/
│   │   ├── Models/
│   │   └── Policies/
│   ├── bootstrap/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/
│   │   └── ticketdesk/
│   ├── routes/
│   │   └── web.php
│   └── tests/
│       ├── Feature/
│       └── Unit/
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── features/
│   │   │   ├── auth/
│   │   │   ├── tickets/
│   │   │   └── categories/
│   │   ├── lib/
│   │   ├── App.tsx
│   │   └── main.tsx
│   ├── package.json
│   └── vite.config.ts
└── README.md
```

### Comunicazione tra frontend e backend

Il frontend React comunica con Laravel attraverso richieste HTTP agli endpoint `/api/*`.

Laravel si occupa di:

- autenticare l'utente;
- validare i dati ricevuti;
- applicare le regole di autorizzazione;
- verificare l'appartenenza delle risorse all'azienda;
- eseguire le operazioni sul database;
- restituire risposte JSON.

Il frontend gestisce invece la navigazione, i form, la visualizzazione delle informazioni e i feedback verso l'utente.

### Un unico server per la versione compilata

Durante lo sviluppo è possibile utilizzare Vite separatamente.

Per provare la versione completa dell'applicazione, però, **non servono due server**.

La compilazione del frontend genera i file statici all'interno di `backend/public/ticketdesk`.

Laravel distribuisce quindi sia l'interfaccia React sia gli endpoint API, utilizzando un unico indirizzo:

`http://127.0.0.1:8000`

---

## 4. Funzionalità implementate

### Autenticazione

- Accesso tramite email e password
- Autenticazione delle richieste API mediante token Sanctum
- Recupero del profilo dell'utente autenticato
- Logout con revoca del token corrente
- Protezione delle funzionalità riservate agli utenti autenticati

### Gestione dei ticket

- Visualizzazione dei ticket autorizzati
- Creazione di un nuovo ticket
- Consultazione del dettaglio
- Modifica dei dati del ticket
- Eliminazione, quando consentita
- Gestione della categoria
- Gestione della priorità
- Assegnazione e rimozione dell'assegnatario
- Aggiornamento dello stato
- Riapertura dei ticket chiusi, quando autorizzata

### Ricerca e organizzazione

L'elenco dei ticket supporta:

- ricerca per titolo;
- filtro per stato;
- filtro per priorità;
- filtro per categoria;
- filtro per assegnatario;
- ordinamento;
- paginazione.

La ricerca e i filtri vengono elaborati dal backend, mantenendo i risultati coerenti con i permessi dell'utente.

### Commenti

Gli utenti autorizzati possono consultare e aggiungere commenti ai ticket.

I commenti sono supportati anche sui ticket chiusi, così da permettere eventuali comunicazioni successive alla chiusura.

### Categorie

Le categorie permettono di classificare le richieste.

Gli amministratori possono creare, modificare ed eliminare categorie, nel rispetto delle regole applicative. Una categoria associata a ticket esistenti non può essere eliminata.

### Interfaccia

Il frontend include:

- layout responsive;
- navigazione tra le sezioni;
- tema chiaro e scuro;
- form con validazione;
- notifiche per errori e operazioni completate;
- visualizzazione delle azioni coerente con il ruolo dell'utente.

Le autorizzazioni effettive vengono comunque verificate dal backend.

---

## 5. Ruoli e autorizzazioni

TicketDesk utilizza tre ruoli applicativi.

### Admin

L'amministratore gestisce le risorse della propria azienda.

Può visualizzare e gestire i ticket aziendali, assegnare o riassegnare richieste, modificare gli stati consentiti, riaprire ticket chiusi ed eliminare ticket.

Può inoltre gestire le categorie.

### Supervisor

Il supervisore può visualizzare i ticket della propria azienda, ma ha permessi operativi più limitati.

Può intervenire sui ticket assegnati a sé stesso o non ancora assegnati, nel rispetto delle regole previste.

Può anche prendere in carico un ticket libero, ma non assegnarlo arbitrariamente a un altro utente o riassegnare ticket già presi in carico.

### Requester

Il richiedente può creare ticket e consultare quelli che ha aperto personalmente.

Può modificarli quando le regole applicative lo consentono e aggiungere commenti ai ticket a cui ha accesso.

Non può intervenire sui ticket di altri utenti.

### Isolamento tra aziende

Ogni utente appartiene a un'azienda.

I dati di ticket e categorie sono associati alla rispettiva azienda. Questa associazione viene utilizzata dal backend per limitare le risorse accessibili.

Un amministratore non può visualizzare o modificare ticket appartenenti a un'altra azienda, nonostante disponga del ruolo più elevato.

Il controllo viene applicato lato server: non dipende soltanto dalla visualizzazione o meno di un pulsante nel frontend.

---

## 6. Requisiti

Prima di procedere con l'installazione, assicurarsi di avere:

| Software | Requisito |
|---|---|
| PHP | Versione 8.3 o superiore |
| Composer | Installazione funzionante |
| MySQL | Server locale disponibile |
| Node.js | Versione compatibile con Vite 8 |
| npm | Disponibile insieme a Node.js |
| Git | Per clonare la repository |

PHP deve disporre delle estensioni necessarie a Laravel e della connessione PDO per MySQL.

Per controllare le versioni installate:

```bash
php -v
composer --version
mysql --version
node -v
npm -v
git --version
```

Verificare inoltre che il server MySQL sia avviato.

**Nota:** PHP, Composer, Node.js e MySQL devono essere installati prima di proseguire. Il repository non comprende questi programmi né le dipendenze già installate.

---

## 7. Installazione guidata

Di seguito sono riportati tutti i passaggi per configurare TicketDesk a partire da una nuova copia della repository.

### Passaggio 1 — Clonare il progetto

Aprire un terminale nella directory in cui si desidera scaricare il progetto.

```bash
git clone <URL-DELLA-REPOSITORY>
cd ticketdesk
```

L'URL va sostituito con quello della repository GitHub.

Al termine del clone saranno disponibili le directory `backend` e `frontend`.

### Passaggio 2 — Installare le dipendenze PHP

Entrare nella directory del backend:

```bash
cd backend
```

Installare i pacchetti Composer:

```bash
composer install
```

Composer scaricherà le dipendenze definite nel progetto, compresi Laravel e i pacchetti necessari ai test.

Non è necessario installare Laravel globalmente.

### Passaggio 3 — Preparare il file di configurazione

Laravel legge le impostazioni locali dal file `.env`.

Crearlo copiando il file di esempio:

```bash
cp .env.example .env
```

Generare quindi la chiave applicativa:

```bash
php artisan key:generate
```

Il file `.env` contiene configurazioni specifiche del proprio ambiente e non deve essere caricato nella repository.

---

## 8. Configurazione di MySQL

### Passaggio 4 — Creare il database

Avviare MySQL e creare un database vuoto chiamato `ticketdesk`.

Da un client MySQL è possibile eseguire:

```sql
CREATE DATABASE ticketdesk
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

È possibile usare anche uno strumento grafico come MySQL Workbench, purché venga creato un database con lo stesso nome.

### Passaggio 5 — Configurare la connessione

Aprire `backend/.env` e impostare i parametri del proprio server MySQL.

Esempio:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticketdesk
DB_USERNAME=root
DB_PASSWORD=
```

**Importante:** `root` e la password vuota sono soltanto valori d'esempio. Utilizzare le credenziali effettive della propria installazione MySQL.

Se il file `.env` contiene già impostazioni del database, sostituirle con quelle corrette.

### Passaggio 6 — Eseguire le migration

Restando nella directory `backend`, eseguire:

```bash
php artisan migrate
```

Laravel creerà le tabelle previste dal progetto.

Le migration definiscono la struttura dei dati, incluse le relazioni tra aziende, utenti, categorie, ticket e commenti.

### Passaggio 7 — Caricare i dati dimostrativi

Per poter provare immediatamente l'applicazione, eseguire:

```bash
php artisan db:seed
```

Questo comando richiama `TicketDeskSeeder`, che inizializza:

- 2 aziende;
- 6 utenti con ruoli differenti;
- 3 categorie per azienda;
- 3 ticket iniziali per azienda.

I ticket iniziali presentano stati e priorità differenti, così da permettere di provare filtri, assegnazioni e operazioni autorizzate.

Su un database vuoto è anche possibile eseguire migration e seeding con un unico comando:

```bash
php artisan migrate --seed
```

**Attenzione:** il seeder è destinato alla preparazione dei dati dimostrativi. Rieseguirlo su dati demo già modificati può riportare alcune informazioni ai valori iniziali.

---

## 9. Account dimostrativi

I sei account sono suddivisi tra due aziende.

La password iniziale comune è:

`password`

### Adriatica Logistica S.r.l.

| Utente | Ruolo | Email |
|---|---|---|
| Marco Rossi | Admin | `marco.rossi@azienda1.test` |
| Luca Bianchi | Supervisor | `luca.bianchi@azienda1.test` |
| Giulia Moretti | Requester | `giulia.moretti@azienda1.test` |

### Puglia Servizi Digitali S.r.l.

| Utente | Ruolo | Email |
|---|---|---|
| Andrea Romano | Admin | `andrea.romano@azienda2.test` |
| Francesca Greco | Supervisor | `francesca.greco@azienda2.test` |
| Davide Conti | Requester | `davide.conti@azienda2.test` |

Tutti gli indirizzi utilizzano il dominio `.test`, riservato a scopi dimostrativi.

Le password sono destinate esclusivamente agli account creati dal seeder in ambiente locale.

---

## 10. Installazione del frontend

### Passaggio 8 — Installare le dipendenze Node.js

Dalla directory `backend`, tornare alla root e aprire `frontend`:

```bash
cd ../frontend
```

Installare le dipendenze usando il lockfile:

```bash
npm ci
```

È preferibile `npm ci` rispetto a `npm install` durante la prima installazione del progetto, perché utilizza le versioni risolte presenti in `package-lock.json`.

### Passaggio 9 — Compilare React

Sempre dalla directory `frontend`, eseguire:

```bash
npm run build
```

Questo comando esegue il controllo TypeScript e la compilazione Vite in modalità integrata.

I file compilati vengono scritti in:

```text
backend/public/ticketdesk/
```

La compilazione comprende HTML, JavaScript, CSS e asset necessari al frontend.

Gli asset compilati possono essere rigenerati in qualsiasi momento ripetendo `npm run build`.

---

## 11. Avviare TicketDesk

### Passaggio 10 — Avviare Laravel

Tornare alla directory `backend`:

```bash
cd ../backend
```

Avviare il server locale:

```bash
php artisan serve
```

Per impostazione predefinita, Laravel utilizza l'indirizzo:

**http://127.0.0.1:8000**

Aprire l'indirizzo nel browser.

La pagina iniziale verrà servita da Laravel utilizzando la build React realizzata nel passaggio precedente.

Non è necessario avviare contemporaneamente Vite per utilizzare questa versione dell'applicazione.

### Riepilogo dei comandi

Dopo aver configurato correttamente MySQL, la procedura essenziale è:

```bash
# Clonazione
git clone <URL-DELLA-REPOSITORY>
cd ticketdesk

# Preparazione backend
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Configurare MySQL nel file .env,
# quindi eseguire:
php artisan migrate --seed

# Compilazione frontend
cd ../frontend
npm ci
npm run build

# Avvio
cd ../backend
php artisan serve
```

A questo punto TicketDesk è pronto per essere utilizzato.

---

## 12. Come provare l'applicazione

Questa sezione propone alcune verifiche pratiche per esplorare il comportamento del software e confrontare i permessi dei diversi ruoli.

### Prova 1 — Accesso come amministratore

Aprire `http://127.0.0.1:8000` ed effettuare il login con:

**Email:** `marco.rossi@azienda1.test`  
**Password:** `password`

Marco Rossi è amministratore di Adriatica Logistica.

Dopo l'accesso è possibile consultare i ticket iniziali della propria azienda.

Provare a:

1. Visualizzare l'elenco dei ticket.
2. Cercare un ticket per titolo.
3. Filtrare per stato e priorità.
4. Aprire il dettaglio di un ticket.
5. Creare un nuovo ticket.
6. Modificarne descrizione, priorità o categoria.
7. Assegnarlo a un utente autorizzato.
8. Cambiarne lo stato.
9. Aggiungere un commento.
10. Gestire le categorie.

Per verificare la riapertura, utilizzare un ticket chiuso e provare l'operazione disponibile all'amministratore.

### Prova 2 — Accesso come supervisore

Effettuare il logout e accedere con:

**Email:** `luca.bianchi@azienda1.test`  
**Password:** `password`

Il supervisore vede i ticket della propria azienda, ma non dispone di tutti i permessi dell'amministratore.

Provare a prendere in carico un ticket libero e a modificarne i dati.

Confrontare poi le operazioni disponibili su un ticket assegnato a un altro utente.

Le restrizioni vengono applicate anche dal backend e non soltanto dall'interfaccia.

### Prova 3 — Accesso come richiedente

Effettuare il logout e accedere con:

**Email:** `giulia.moretti@azienda1.test`  
**Password:** `password`

Giulia è un richiedente.

L'elenco contiene soltanto i ticket che ha creato personalmente.

Provare ad aprire un nuovo ticket, consultarlo e aggiungere un commento.

Le operazioni riservate ad amministratori o supervisori non sono consentite.

### Prova 4 — Verificare la separazione tra aziende

Effettuare il logout e accedere con:

**Email:** `andrea.romano@azienda2.test`  
**Password:** `password`

Andrea è amministratore di Puglia Servizi Digitali.

Anche se possiede il ruolo Admin, non può accedere ai ticket e alle categorie di Adriatica Logistica.

Questo comportamento è fondamentale: il ruolo determina le autorizzazioni operative, mentre l'azienda determina l'ambito dei dati accessibili.

### Prova 5 — Gestione delle categorie

Accedere con un amministratore e aprire la sezione delle categorie.

Provare a creare una nuova categoria e successivamente a modificarla.

È inoltre possibile verificare che una categoria già collegata a un ticket non possa essere eliminata.

In questo caso il backend rifiuta l'operazione per preservare la coerenza dei dati.

### Prova 6 — Chiusura della sessione

Effettuare il logout.

Il token di autenticazione corrente viene revocato e le API protette non possono più essere utilizzate con quel token.

Per accedere nuovamente alle funzionalità dell'applicazione è necessario effettuare un nuovo login.

---

## 13. API REST

Il backend espone 18 endpoint sotto il prefisso `/api`.

### Autenticazione

| Metodo | Endpoint | Descrizione |
|---|---|---|
| POST | `/api/login` | Autenticazione |
| GET | `/api/user` | Profilo corrente |
| POST | `/api/logout` | Revoca del token corrente |

### Ticket

| Metodo | Endpoint | Descrizione |
|---|---|---|
| GET | `/api/tickets` | Elenco ticket |
| POST | `/api/tickets` | Creazione ticket |
| GET | `/api/tickets/{ticket}` | Dettaglio ticket |
| PATCH | `/api/tickets/{ticket}` | Modifica ticket |
| DELETE | `/api/tickets/{ticket}` | Eliminazione ticket |
| PATCH | `/api/tickets/{ticket}/status` | Aggiornamento stato |
| PATCH | `/api/tickets/{ticket}/assign` | Gestione assegnazione |

### Commenti

| Metodo | Endpoint | Descrizione |
|---|---|---|
| GET | `/api/tickets/{ticket}/comments` | Elenco commenti |
| POST | `/api/tickets/{ticket}/comments` | Inserimento commento |

### Categorie

| Metodo | Endpoint | Descrizione |
|---|---|---|
| GET | `/api/categories` | Elenco categorie |
| POST | `/api/categories` | Creazione categoria |
| GET | `/api/categories/{category}` | Dettaglio categoria |
| PATCH | `/api/categories/{category}` | Modifica categoria |
| DELETE | `/api/categories/{category}` | Eliminazione categoria |

### Utenti

| Metodo | Endpoint | Descrizione |
|---|---|---|
| GET | `/api/users` | Utenti della propria azienda |

Tutti gli endpoint, eccetto il login, richiedono autenticazione mediante Laravel Sanctum.

Le richieste autenticate utilizzano un header Bearer token:

```http
Authorization: Bearer <TOKEN>
Accept: application/json
```

I dati inviati alle API vengono validati dal backend. Le operazioni non consentite vengono rifiutate anche qualora siano effettuate direttamente tramite un client HTTP.

### Codici di risposta

A seconda dell'operazione, l'API può restituire:

| Codice | Significato |
|---|---|
| 200 | Richiesta completata |
| 201 | Risorsa creata |
| 204 | Operazione completata senza contenuto |
| 401 | Autenticazione mancante o non valida |
| 403 | Operazione non autorizzata |
| 404 | Risorsa non trovata o non accessibile |
| 409 | Conflitto con le regole applicative |
| 422 | Dati non validi |

---

## 14. Test e controlli di qualità

### Test backend

Il backend utilizza Pest.

Per eseguire la suite automatica:

```bash
cd backend
php artisan test
```

All'ultima verifica, la suite comprendeva:

**96 test superati e 263 asserzioni.**

I test coprono:

- login e logout;
- autenticazione e token revocati;
- accesso ai dati del profilo;
- autorizzazioni in base al ruolo;
- separazione tra aziende;
- creazione, lettura, modifica ed eliminazione dei ticket;
- ricerca, filtri e paginazione;
- assegnazioni;
- transizioni di stato;
- commenti;
- categorie;
- validazione dei dati;
- gestione degli errori.

### Controlli frontend

Per verificare il codice frontend:

```bash
cd frontend
npm run lint
npm run build
```

`npm run lint` esegue ESLint.

`npm run build` esegue il controllo TypeScript e la compilazione di produzione.

### Collaudo manuale

Oltre ai test automatizzati, sono state eseguite prove manuali sui principali flussi dell'interfaccia, incluse autenticazione, navigazione, gestione ticket, categorie, assegnazioni, stati e permessi.

I controlli sono stati effettuati utilizzando account con ruoli differenti.

---

## 15. Scelte progettuali

### Autorizzazioni lato server

I permessi non vengono gestiti esclusivamente dal frontend.

L'interfaccia può nascondere o disabilitare operazioni non disponibili, ma la decisione finale viene presa dal backend attraverso le regole di autorizzazione.

In questo modo, una richiesta HTTP costruita manualmente non può aggirare i controlli previsti.

### Separazione dei dati per azienda

Il progetto utilizza un database condiviso e associa le risorse alla rispettiva azienda.

Non è stata introdotta una struttura con database separati per ogni cliente: per le dimensioni e gli obiettivi del progetto, l'isolamento logico mediante relazioni e controlli applicativi risulta più semplice da gestire.

### Validazione e regole di business

Le richieste API vengono validate prima delle operazioni sul database.

Le transizioni di stato, le assegnazioni e le modifiche ai ticket sono soggette a controlli specifici.

Questo permette di evitare operazioni incoerenti, come assegnare ticket chiusi o collegare un ticket a una categoria appartenente a un'altra azienda.

### Frontend organizzato per funzionalità

Il codice React è suddiviso in aree applicative:

- autenticazione;
- gestione ticket;
- gestione categorie;
- componenti condivisi;
- servizi e utility.

Questa organizzazione riduce le responsabilità dei singoli file e facilita eventuali estensioni.

### Distribuzione tramite Laravel

Per semplificare l'esecuzione del progetto, la build React viene copiata nella directory pubblica Laravel.

La versione compilata non richiede un processo Node.js in esecuzione: Laravel serve direttamente gli asset statici e gestisce le API.

Si tratta di una scelta pensata per facilitare soprattutto l'installazione e la valutazione locale del progetto.

---

## 16. Risoluzione dei problemi

### Errore di connessione a MySQL

Verificare che:

- MySQL sia avviato;
- il database `ticketdesk` esista;
- le credenziali in `.env` siano corrette;
- la porta configurata sia quella utilizzata dal server.

Dopo eventuali modifiche al file `.env`:

```bash
php artisan config:clear
```

### Errore relativo alla chiave applicativa

Verificare di aver creato `.env` ed eseguire:

```bash
php artisan key:generate
```

### Tabelle non presenti

Eseguire:

```bash
php artisan migrate
```

Se si desidera creare anche gli account dimostrativi:

```bash
php artisan db:seed
```

### La pagina React non viene caricata

Controllare di aver eseguito la compilazione nella directory `frontend`:

```bash
npm run build
```

La build deve generare i file in `backend/public/ticketdesk`.

Dopo la compilazione, riavviare Laravel se necessario.

### Porta 8000 già occupata

Avviare Laravel su un'altra porta:

```bash
php artisan serve --port=8001
```

Aprire quindi l'indirizzo corrispondente.

### Credenziali demo non funzionanti

Verificare di aver eseguito:

```bash
php artisan db:seed
```

Controllare inoltre di utilizzare gli indirizzi email riportati nella sezione dedicata agli account dimostrativi.

### Le dipendenze frontend non si installano

Verificare la versione di Node.js e la compatibilità con Vite 8.

In seguito, dalla directory `frontend`, ripetere:

```bash
npm ci
```

### Verificare le rotte disponibili

Laravel permette di visualizzare gli endpoint registrati con:

```bash
php artisan route:list --path=api
```

### Pulire la cache di Laravel

Se una modifica alla configurazione non viene applicata:

```bash
php artisan optimize:clear
```

---

## Conclusione

TicketDesk è stato sviluppato con l'obiettivo di realizzare un'applicazione completa ma comprensibile, mantenendo una chiara separazione tra interfaccia, API REST, autorizzazioni e persistenza dei dati.

La presenza di account dimostrativi e test automatizzati permette di verificare direttamente sia le funzionalità principali sia le regole applicative che governano la gestione dei ticket.