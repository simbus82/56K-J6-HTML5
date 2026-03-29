# Html56K - Joomla 6 Responsive Template

[![Deployment status from DeployBot](https://56k.deploybot.com/badge/77558060029390/84717.svg)](https://deploybot.com)

**Html56K** è un template moderno e leggero per **Joomla 5 e 6**, sviluppato da **56K Agency**. È l'evoluzione del precedente progetto `Qhtml5`, completamente riscritto per supportare gli ultimi standard web e le API di Joomla.

## Caratteristiche Principali

- **Joomla 6 Ready**: Piena compatibilità con le ultime versioni di Joomla, inclusa l'architettura a namespace (`Agency56k\Template\Html56k`).
- **Bootstrap 5**: Utilizza il sistema a griglia di Bootstrap 5 per un layout responsive e moderno.
- **Web Asset Manager**: Gestione ottimizzata di CSS e JS tramite `joomla.asset.json`.
- **Compilatore SCSS Integrato**: Include la libreria `scssphp` per compilare i file SCSS direttamente dall'admin del template.
- **Modalità Sviluppo/Produzione**: 
    - *Sviluppo*: Compilazione SCSS on-the-fly e cache busting automatico.
    - *Produzione*: Utilizzo di file CSS compressi per massime prestazioni.
- **Privacy e SEO**: Struttura pulita, rimozione di meta generator e aree per codice personalizzato (tracking pixel, favicon, ecc.).

## Requisiti

- Joomla 5.x / 6.x
- PHP 8.1 o superiore (consigliato 8.2+)

## Installazione

1. Scarica o clona il repository.
2. Comprimi il contenuto della cartella in un file `.zip`.
3. Carica e installa tramite il pannello di gestione estensioni di Joomla.

## Sviluppo SCSS

Il template supporta la compilazione automatica. All'interno delle impostazioni del template:
1. Imposta **Modalità Compilazione SCSS** su **Sviluppo**.
2. Modifica i file in `/scss/template.scss`.
3. Al refresh della pagina, il file `/css/template.css` verrà aggiornato automaticamente.

## Changelog

### 6.0.0 (Marzo 2026)
- **Rebranding**: Cambio nome da Qhtml5 a Html56K.
- **Aggiornamento Core**: Migrazione completa a Joomla 6.
- **Namespace**: Implementazione namespace PSR-4.
- **Griglia**: Passaggio da Bootstrap 2/3 a Bootstrap 5.
- **Libreria SCSS**: Inserimento compiler `scssphp` v1.13.0.
- **Cleanup**: Rimozione totale del codice legacy di Joomla 3.

### 6.1.0 (Marzo 2026)
- **Font Optimizer**: Font self-hosted WOFF2 con fallback calibrato (`size-adjust`) per zero CLS.
- **Preload Font**: Tag `<link rel="preload">` automatici per i font critici, attivabile da admin.
- **Asset Fonts**: Nuovo asset `template.fonts` nel Web Asset Manager con `css/fonts.css`.
- **Pulizia Legacy**: Rimozione `package/`, `build/bump.php`, `.travis.yml`.
- **Build Script**: Nuovo `build/build_zip.php` per generare pacchetto di installazione.

### 6.2.0 (Marzo 2026)
- **Lazy Loading Intelligente**: Post-processore HTML con gestione automatica della priorità LCP.
- **Performance Pipeline**: Nuovo `PerformanceHelper` come orchestratore dei moduli di performance su `onAfterRender`.
- **LCP Priority**: La prima immagine grande riceve automaticamente `fetchpriority="high"`.
- **Iframe Lazy**: Tutti gli iframe (YouTube, Maps, ecc.) ricevono `loading="lazy"`.

### 6.3.0 (Marzo 2026)
- **CriticalCSS Engine**: Estrazione automatica del CSS critico via analisi DOM statica, cache per tipo pagina.
- **CSS Parser PHP**: Parser CSS leggero integrato, zero dipendenze esterne (no Composer).
- **DOM Matcher**: Conversione CSS→XPath per matching selettori contro il DOM.
- **Async CSS Loading**: I fogli di stile non critici vengono caricati con `media="print"` + `onload="this.media='all'"`.
- **Cache per Tipo Pagina**: Cache separata per homepage, article, category-blog, category-list, default.

### 6.4.0 (Marzo 2026)
- **Asset Pipeline**: Ottimizzazione automatica del Web Asset Manager di Joomla 6.
- **Force Defer**: Post-processore che forza `defer` su tutti gli script render-blocking.
- **Disable Font Awesome**: Toggle admin per rimuovere Font Awesome dal frontend.
- **Asset Loading**: Refactoring del caricamento asset con `useStyle()`/`useScript()` espliciti.

## Roadmap (Prossimi Sviluppi)

I prossimi step per rendere **Html56K** un template state-of-the-art per le performance:

- [x] **CriticalCSS Engine** — PHP puro, cache per tipo pagina, analisi DOM statica.
- [x] **Font Optimizer** — Self-host, subset, preload, size-adjust fallback, zero CLS.
- [x] **Asset Pipeline** — Ottimizzazione del WAM di Joomla 6 con defer/async automatico e concatenazione intelligente.
- [ ] **Image Pipeline** — Generazione WebP/AVIF on-the-fly e gestione della priorità LCP.
- [x] **Lazy Loading** — `loading="lazy"` + `decoding="async"` + `fetchpriority="high"` per LCP.
- [ ] **Resource Hints** — Autogenerazione di preconnect, dns-prefetch e preload.
- [ ] **Service Worker** — Cache shell opzionale per la navigazione offline e velocizzazione.
- [ ] **HTML Minification** — Rimozione spazi e commenti nell'output finale (tramite evento `onAfterRender`).

## Storia del Progetto (Legacy)

<details>
<summary>Visualizza la cronologia della versione 3.x (Qhtml5)</summary>

### Qhtml5 - Versione per Joomla 3.x

Questa è la storia originale del template prima del rebranding in Html56K.

#### 2.8.0 (17/09/2019)
- Rimozione supporto IE.
- Pulizia feature inutilizzate.
- Ottimizzazione caricamento CSS/JS core.
- Miglioramento gestione JQuery.
- Integrazione RealFaviconGenerator.
- Aggiunta selettore "browser upgrade".

#### 2.6.3 (08/03/2018)
- Risolto bug su PageClass.
- Lavori sul pacchetto di installazione.

#### 2.6.0 (11/05/2017)
- Inserimento codice personalizzato in HEAD e BODY.
- Selettore versione Bootstrap.
- Ottimizzazioni Speed & SEO.

#### 14/02/2017
- Miglioramenti generali e bugfix.

#### 15/09/2016
- Pulizia profonda del codice.

#### 20/04/2015
- Implementazione Auto Deploy sul server.
- Creazione branch Development.

</details>

---

Sviluppato con ❤️ da [56K Agency](https://www.56k.agency)
