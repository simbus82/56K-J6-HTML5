# Istruzioni Generali per AI Agents lavorando su Html56K

Benvenuto! Questo repository contiene **Html56K**, un moderno template per Joomla 5 e 6 sviluppato da 56K Agency.

Quando esegui lavorazioni su questo progetto, **devi sempre e inderogabilmente completare le seguenti azioni** prima di chiudere la sessione di lavoro:

### 1. Gestione del Versioning (`templateDetails.xml`)
Se hai apportato modifiche al codice (PHP, JS, CSS, SCSS, XML manifest), devi aggiornare la versione del template:
- Apri il file `templateDetails.xml`.
- Aggiorna il tag `<version>X.Y.Z</version>` seguendo il versionamento semantico:
  - **Major (X)**: Modifiche incompatibili (breaking changes).
  - **Minor (Y)**: Aggiunta di nuove funzionalità retrocompatibili.
  - **Patch (Z)**: Fixing di bug o piccole ottimizzazioni.
- Aggiorna il tag `<creationDate>` con il mese e l'anno correnti o la data di release.

### 2. Gestione della Documentazione (`README.md`)
Il file README funge anche da changelog e roadmap del progetto:
- Apri il file `README.md`.
- **Roadmap**: Se hai implementato e testato una funzionalità che era presente nella sezione "Roadmap (Prossimi Sviluppi)", segnala come completata inserendo la `[x]`.
- **Changelog**: Inserisci le modifiche principali, le nuove logiche o le funzionalità nell'area del Changelog corrispondente alla versione appena rilasciata (creala se non esiste).

> **Nota per l'Agent**: La conformità a queste regole è essenziale per la pipeline di rilascio del team di 56K Agency. Esegui questi passaggi autonomamente al termine di ogni ticket/compito.
