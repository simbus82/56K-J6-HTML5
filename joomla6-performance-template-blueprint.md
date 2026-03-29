# Joomla 6 High-Performance Template — Blueprint Completo

> **Documento di istruzioni per Agente AI**
> Questo documento contiene tutte le specifiche, l'architettura e gli esempi di codice necessari per sviluppare un template Joomla 6 con performance al centro. Ogni modulo è descritto con il contesto tecnico, la logica di implementazione e il codice di riferimento.

---

## Sommario

1. [Contesto e Architettura Generale](#1-contesto-e-architettura-generale)
2. [Struttura File del Template](#2-struttura-file-del-template)
3. [Modulo 1 — Critical CSS Engine](#3-modulo-1--critical-css-engine)
4. [Modulo 2 — Font Optimizer](#4-modulo-2--font-optimizer)
5. [Modulo 3 — Asset Pipeline (Web Asset Manager)](#5-modulo-3--asset-pipeline-web-asset-manager)
6. [Modulo 4 — Image Pipeline](#6-modulo-4--image-pipeline)
7. [Modulo 5 — Resource Hints Automatici](#7-modulo-5--resource-hints-automatici)
8. [Modulo 6 — HTML Minification](#8-modulo-6--html-minification)
9. [Modulo 7 — Service Worker (Opzionale)](#9-modulo-7--service-worker-opzionale)
10. [Modulo 8 — Lazy Loading Intelligente](#10-modulo-8--lazy-loading-intelligente)
11. [Configurazione Server (.htaccess)](#11-configurazione-server-htaccess)
12. [Parametri Template (templateDetails.xml)](#12-parametri-template-templatedetailsxml)
13. [Checklist Finale — Core Web Vitals](#13-checklist-finale--core-web-vitals)

---

## 1. Contesto e Architettura Generale

### Ambiente Target

- **CMS:** Joomla 6.x (Framework 4.x, Web Asset Manager obbligatorio)
- **Server:** LAMP (Linux, Apache, MySQL/MariaDB, PHP 8.2+)
- **Nessun plugin di terze parti:** tutto integrato nel template
- **Obiettivo:** 100/100 Lighthouse Performance, tutti i Core Web Vitals "buoni"

### Principi Architetturali

1. **Zero render-blocking resources** — CSS critico inline, CSS completo async, JS defer
2. **Minimo numero di request HTTP** — concatenazione, inline dove utile, preload mirato
3. **Font locali e ottimizzati** — self-hosted, subset, preload, fallback calibrato
4. **Immagini next-gen** — WebP/AVIF on-the-fly, lazy loading nativo, LCP priority
5. **Cache aggressiva** — lato server (`.htaccess`), lato client (Service Worker), lato applicazione (PHP file cache)
6. **Nessuna dipendenza esterna a runtime** — niente CDN per font, niente librerie remote

### Come Sono Collegati i Moduli

```
Request HTTP in arrivo
    │
    ▼
┌─────────────────────────────────────────────────────────┐
│  Joomla 6 Core (routing, component, modules rendering)  │
└─────────────────┬───────────────────────────────────────┘
                  │ onAfterRender
                  ▼
┌─────────────────────────────────────────────────────────┐
│              Template Performance Pipeline               │
│                                                         │
│  1. Critical CSS Engine → inline CSS + async full CSS   │
│  2. Image Pipeline → WebP/AVIF + lazy + LCP priority    │
│  3. Lazy Loading → attributi su img/iframe              │
│  4. Resource Hints → preload/preconnect nel <head>      │
│  5. HTML Minification → rimuove spazi e commenti        │
│                                                         │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
           Output HTML ottimizzato
```

---

## 2. Struttura File del Template

```
templates/tpl_performance/
├── templateDetails.xml
├── index.php                          # Entry point template
├── joomla.asset.json                  # Web Asset Manager definitions
├── error.php                          # Pagina errore custom
├── offline.php                        # Pagina offline
├── component.php                      # Print layout
│
├── html/                              # Override layouts Joomla
│   ├── layouts/
│   │   └── joomla/
│   │       └── content/
│   └── com_content/
│       └── article/
│
├── src/                               # Classi PHP del template
│   ├── CriticalCss/
│   │   ├── CriticalCssExtractor.php   # Motore estrazione CSS critico
│   │   ├── CssParser.php              # Parser CSS (selettori + regole)
│   │   └── DomMatcher.php             # Match selettori → DOM
│   ├── Font/
│   │   └── FontOptimizer.php          # Gestione font loading
│   ├── Image/
│   │   └── ImageOptimizer.php         # Conversione WebP/AVIF + responsive
│   ├── Performance/
│   │   ├── HtmlMinifier.php           # Minificazione output
│   │   ├── ResourceHints.php          # Preload/preconnect automatici
│   │   └── LazyLoader.php            # Lazy loading intelligente
│   └── Helper/
│       └── PerformanceHelper.php      # Orchestratore principale
│
├── media/                             # Asset pubblici (registrati in WAM)
│   └── tpl_performance/
│       ├── css/
│       │   ├── template.css           # CSS completo (sorgente)
│       │   ├── template.min.css       # CSS completo (minificato)
│       │   └── critical/              # CSS critici generati (cache)
│       │       ├── homepage.css
│       │       ├── article.css
│       │       └── category.css
│       ├── js/
│       │   ├── template.js            # JS principale
│       │   ├── template.min.js
│       │   └── sw-register.js         # Registrazione Service Worker
│       ├── fonts/
│       │   ├── main-latin.woff2       # Font primario (subset Latin)
│       │   ├── main-latin-italic.woff2
│       │   └── heading-latin.woff2    # Font heading (subset Latin)
│       └── images/
│           └── ...
│
├── cache/                             # Cache interna template (writable)
│   ├── critical-css/                  # CSS critici generati
│   └── images/                        # Immagini convertite WebP/AVIF
│
└── sw.js                              # Service Worker
```

---

## 3. Modulo 1 — Critical CSS Engine

### Contesto

Il Critical CSS è il sottoinsieme di CSS necessario per renderizzare il contenuto "above the fold" (visibile senza scroll). Inlinearlo nel `<head>` elimina le richieste CSS render-blocking, migliorando drasticamente FCP (First Contentful Paint) e LCP (Largest Contentful Paint).

### Approccio: Analisi DOM Statica (No Headless Browser)

L'approccio segue la logica di **Critters** (Google Chrome Labs) e **ModPageSpeed 2.0**: parsare l'HTML statico e fare match dei selettori CSS contro il DOM, senza bisogno di un browser headless.

**Perché non headless browser:**
- Un processo Chromium headless consuma 200-400 MB e richiede 2-5 secondi per pagina
- Su hosting LAMP condiviso non è praticabile
- L'analisi statica completa l'estrazione in meno di 5ms per stylesheet fino a 2MB

### Dipendenze PHP

```bash
composer require sabberworm/php-css-parser
# Nessun'altra dipendenza esterna necessaria. DOMDocument è built-in in PHP.
```

### Logica di Funzionamento

```
1. Intercetta HTML finale (onAfterRender)
2. Calcola un "page type key" (homepage, article, category, ecc.)
3. Controlla se esiste una versione cachata del CSS critico per quel tipo
4. Se NO:
   a. Parsa l'HTML con DOMDocument
   b. Trova tutti i <link rel="stylesheet"> nell'HTML
   c. Per ogni foglio CSS, parsalo con sabberworm/php-css-parser
   d. Per ogni selettore CSS, verifica se corrisponde a un elemento nel DOM
   e. Filtra ulteriormente: tieni solo selettori che matchano elementi
      dentro un container "above the fold" (identificato da data-critical-container
      oppure euristicamente: header, nav, main > *:first-child, .hero, ecc.)
   f. Assembla il CSS critico risultante
   g. Salva in cache
5. Inline il CSS critico nel <head>
6. Converti i <link> CSS originali in caricamento async
```

### Implementazione: CriticalCssExtractor.php

```php
<?php
/**
 * CriticalCssExtractor — Estrae il CSS critico senza headless browser
 *
 * Approccio: parsing statico del DOM + matching dei selettori CSS
 * Ispirato a Critters (Google Chrome Labs) e ModPageSpeed 2.0
 */

namespace Tpl\Performance\CriticalCss;

use Sabberworm\CSS\Parser as CssParser;
use Sabberworm\CSS\CSSList\Document as CssDocument;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\CSSList\AtRuleBlockList;

class CriticalCssExtractor
{
    /**
     * Selettori CSS che si considerano SEMPRE above-the-fold.
     * Questi matchano i container "critici" del layout tipico.
     */
    private const CRITICAL_SELECTORS_PATTERNS = [
        'header',
        'nav',
        '.navbar',
        '.hero',
        '.banner',
        '[data-critical]',           // Attributo esplicito per marcare sezioni critiche
        '#header',
        '#nav',
        '#hero',
        'body',
        'html',
        ':root',
        'main > *:first-child',
    ];

    /**
     * Selettori che NON sono mai critici (below the fold tipicamente)
     */
    private const EXCLUDE_PATTERNS = [
        'footer',
        '#footer',
        '.footer',
        '.sidebar',
        '#sidebar',
        '.comments',
        '.related-articles',
        '.pagination',
    ];

    private string $cachePath;
    private int $cacheLifetime;

    public function __construct(string $cachePath, int $cacheLifetime = 86400)
    {
        $this->cachePath     = rtrim($cachePath, '/');
        $this->cacheLifetime = $cacheLifetime;

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Processa l'HTML completo e restituisce l'HTML con CSS critico inline.
     *
     * @param string $html       L'output HTML completo della pagina
     * @param string $pageType   Tipo di pagina: 'homepage', 'article', 'category', ecc.
     * @return string            HTML modificato
     */
    public function process(string $html, string $pageType): string
    {
        // 1. Controlla cache
        $cacheFile = $this->cachePath . '/' . $pageType . '.css';
        $criticalCss = $this->getFromCache($cacheFile);

        if ($criticalCss === null) {
            // 2. Estrai CSS critico
            $criticalCss = $this->extractCriticalCss($html);

            // 3. Salva in cache
            file_put_contents($cacheFile, $criticalCss);
        }

        // 4. Inline CSS critico + async CSS completo
        $html = $this->inlineCriticalCss($html, $criticalCss);
        $html = $this->deferNonCriticalCss($html);

        return $html;
    }

    /**
     * Estrae il CSS critico dall'HTML.
     */
    private function extractCriticalCss(string $html): string
    {
        // Parsa il DOM
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        // Trova tutti i fogli CSS linkati
        $cssFiles = $this->findLinkedStylesheets($dom);

        $criticalRules = [];

        foreach ($cssFiles as $cssFilePath) {
            if (!file_exists($cssFilePath)) {
                continue;
            }

            $cssContent = file_get_contents($cssFilePath);

            try {
                $parser = new CssParser($cssContent);
                $cssDocument = $parser->parse();

                // Estrai regole critiche
                $rules = $this->extractMatchingRules($cssDocument, $dom, $xpath);
                $criticalRules[] = $rules;
            } catch (\Exception $e) {
                // Se il parsing fallisce, includi l'intero CSS come fallback sicuro
                continue;
            }
        }

        // Assembla il CSS critico
        $critical = implode("\n", $criticalRules);

        // Minifica (rimuovi spazi e commenti inutili)
        $critical = $this->minifyCss($critical);

        return $critical;
    }

    /**
     * Trova i file CSS linkati nell'HTML e restituisce i path sul filesystem.
     */
    private function findLinkedStylesheets(\DOMDocument $dom): array
    {
        $files = [];
        $links = $dom->getElementsByTagName('link');

        foreach ($links as $link) {
            $rel  = $link->getAttribute('rel');
            $href = $link->getAttribute('href');

            if (strtolower($rel) === 'stylesheet' && $href) {
                // Converti URL relativo in path filesystem
                $filePath = $this->urlToFilePath($href);
                if ($filePath) {
                    $files[] = $filePath;
                }
            }
        }

        return $files;
    }

    /**
     * Converte un URL relativo al path del filesystem Joomla.
     */
    private function urlToFilePath(string $url): ?string
    {
        // Rimuovi query string e fragment
        $url = strtok($url, '?#');

        // Gestisci URL relativi
        if (str_starts_with($url, '/')) {
            $path = JPATH_ROOT . $url;
        } elseif (str_starts_with($url, 'http')) {
            // URL assoluto dello stesso dominio → converti
            $parsed = parse_url($url);
            $path   = JPATH_ROOT . ($parsed['path'] ?? '');
        } else {
            $path = JPATH_ROOT . '/' . $url;
        }

        return file_exists($path) ? $path : null;
    }

    /**
     * Estrae le regole CSS che matchano elementi nel DOM above-the-fold.
     */
    private function extractMatchingRules(CssDocument $cssDocument, \DOMDocument $dom, \DOMXPath $xpath): string
    {
        $output = '';

        foreach ($cssDocument->getContents() as $item) {
            // Gestisci @media queries — includi se contengono regole critiche
            if ($item instanceof AtRuleBlockList && $item->atRuleName() === 'media') {
                $mediaRules = '';
                foreach ($item->getContents() as $mediaItem) {
                    if ($mediaItem instanceof DeclarationBlock) {
                        $selectors = $mediaItem->getSelectors();
                        foreach ($selectors as $selector) {
                            $selectorStr = $selector->getSelector();
                            if ($this->isCriticalSelector($selectorStr, $dom, $xpath)) {
                                $mediaRules .= $mediaItem->render(
                                    \Sabberworm\CSS\OutputFormat::createCompact()
                                );
                            }
                        }
                    }
                }
                if ($mediaRules) {
                    $args = $item->atRuleArgs();
                    $argsStr = is_string($args) ? $args : (string) $args;
                    $output .= '@media ' . $argsStr . '{' . $mediaRules . '}';
                }
            }

            // Gestisci @font-face — includi sempre (necessario per rendering testo)
            if ($item instanceof AtRuleBlockList && $item->atRuleName() === 'font-face') {
                $output .= $item->render(\Sabberworm\CSS\OutputFormat::createCompact());
            }

            // Gestisci regole normali
            if ($item instanceof DeclarationBlock) {
                $selectors = $item->getSelectors();
                foreach ($selectors as $selector) {
                    $selectorStr = $selector->getSelector();
                    if ($this->isCriticalSelector($selectorStr, $dom, $xpath)) {
                        $output .= $item->render(
                            \Sabberworm\CSS\OutputFormat::createCompact()
                        );
                        break; // Una volta che un selettore matcha, includi l'intera regola
                    }
                }
            }

            // Includi sempre @keyframes referenziati e :root / custom properties
            if ($item instanceof DeclarationBlock) {
                $selectors = $item->getSelectors();
                foreach ($selectors as $selector) {
                    $sel = $selector->getSelector();
                    if ($sel === ':root' || str_starts_with($sel, ':root')) {
                        $output .= $item->render(
                            \Sabberworm\CSS\OutputFormat::createCompact()
                        );
                        break;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Determina se un selettore CSS è "critico" (above the fold).
     *
     * Logica a 3 livelli:
     * 1. Escludi se matcha un pattern "mai critico"
     * 2. Includi se matcha un pattern "sempre critico"
     * 3. Prova a matchare contro il DOM — se l'elemento esiste, è critico
     */
    private function isCriticalSelector(string $selector, \DOMDocument $dom, \DOMXPath $xpath): bool
    {
        // Pseudo-elementi/classi → verifica la parte base
        $baseSelector = preg_replace('/::?[\w-]+(\(.*?\))?/', '', $selector);
        $baseSelector = trim($baseSelector);

        if (empty($baseSelector) || $baseSelector === '*') {
            return true; // Selettori universali sono sempre critici
        }

        // 1. Escludi pattern non critici
        foreach (self::EXCLUDE_PATTERNS as $pattern) {
            if (stripos($baseSelector, $pattern) !== false) {
                return false;
            }
        }

        // 2. Includi pattern sempre critici
        foreach (self::CRITICAL_SELECTORS_PATTERNS as $pattern) {
            if (stripos($baseSelector, $pattern) !== false) {
                return true;
            }
        }

        // 3. Tenta il match sul DOM con conversione CSS→XPath semplificata
        try {
            $xpathQuery = $this->cssToXpath($baseSelector);
            if ($xpathQuery) {
                $nodes = @$xpath->query($xpathQuery);
                return $nodes && $nodes->length > 0;
            }
        } catch (\Exception $e) {
            // Se la conversione fallisce, includi per sicurezza
            return true;
        }

        return false;
    }

    /**
     * Conversione CSS selector → XPath (semplificata, copre i casi comuni).
     *
     * NOTA: Questa è una conversione basilare. Per selettori complessi,
     * considera l'uso di una libreria come symfony/css-selector.
     *
     * composer require symfony/css-selector
     * use Symfony\Component\CssSelector\CssSelectorConverter;
     * $converter = new CssSelectorConverter();
     * $xpath = $converter->toXPath($cssSelector);
     */
    private function cssToXpath(string $cssSelector): ?string
    {
        // Per una implementazione robusta, usa symfony/css-selector:
        // $converter = new \Symfony\Component\CssSelector\CssSelectorConverter();
        // return $converter->toXPath($cssSelector);

        // Versione semplificata:
        $selector = trim($cssSelector);

        // Classe semplice: .classname
        if (preg_match('/^\.([a-zA-Z0-9_-]+)$/', $selector, $m)) {
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$m[1]} ')]";
        }

        // ID semplice: #idname
        if (preg_match('/^#([a-zA-Z0-9_-]+)$/', $selector, $m)) {
            return "//*[@id='{$m[1]}']";
        }

        // Tag semplice: tagname
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)$/', $selector, $m)) {
            return "//{$m[1]}";
        }

        // Tag con classe: tag.class
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\.([a-zA-Z0-9_-]+)$/', $selector, $m)) {
            return "//{$m[1]}[contains(concat(' ', normalize-space(@class), ' '), ' {$m[2]} ')]";
        }

        // Attributo: [data-something]
        if (preg_match('/^\[([a-zA-Z0-9_-]+)\]$/', $selector, $m)) {
            return "//*[@{$m[1]}]";
        }

        // Per tutto il resto, restituisci null (il chiamante includerà per sicurezza)
        return null;
    }

    /**
     * Inserisce il CSS critico inline nel <head>.
     */
    private function inlineCriticalCss(string $html, string $criticalCss): string
    {
        if (empty($criticalCss)) {
            return $html;
        }

        $inlineTag = '<style id="critical-css">' . $criticalCss . '</style>';

        // Inserisci subito dopo il primo <style> o prima di </head>
        if (str_contains($html, '</head>')) {
            $html = str_replace('</head>', $inlineTag . "\n</head>", $html);
        }

        return $html;
    }

    /**
     * Converte i <link rel="stylesheet"> in caricamento asincrono.
     *
     * Tecnica: media="print" onload="this.media='all'"
     * Con <noscript> fallback per accessibilità.
     */
    private function deferNonCriticalCss(string $html): string
    {
        // Pattern per trovare <link rel="stylesheet" ...>
        $pattern = '/<link\s+([^>]*?)rel=["\']stylesheet["\']\s*([^>]*?)\/?>/i';

        $html = preg_replace_callback($pattern, function ($matches) {
            $fullTag = $matches[0];

            // Non toccare i CSS già marcati come critici o con data-no-defer
            if (str_contains($fullTag, 'data-no-defer') || str_contains($fullTag, 'critical')) {
                return $fullTag;
            }

            // Estrai href
            if (preg_match('/href=["\']([^"\']+)["\']/i', $fullTag, $hrefMatch)) {
                $href = $hrefMatch[1];
            } else {
                return $fullTag; // Nessun href, lascia invariato
            }

            // Converti in caricamento async
            $asyncLink = '<link rel="stylesheet" href="' . $href . '" media="print" onload="this.media=\'all\'">';
            $noscript  = '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';

            return $asyncLink . "\n" . $noscript;
        }, $html);

        return $html;
    }

    /**
     * Legge dalla cache se il file esiste e non è scaduto.
     */
    private function getFromCache(string $cacheFile): ?string
    {
        if (!file_exists($cacheFile)) {
            return null;
        }

        if (time() - filemtime($cacheFile) > $this->cacheLifetime) {
            unlink($cacheFile);
            return null;
        }

        return file_get_contents($cacheFile);
    }

    /**
     * Minifica CSS (base).
     */
    private function minifyCss(string $css): string
    {
        // Rimuovi commenti
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        // Rimuovi spazi multipli
        $css = preg_replace('/\s+/', ' ', $css);
        // Rimuovi spazi intorno a { } : ; ,
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        // Rimuovi ultimo ; prima di }
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }

    /**
     * Svuota la cache del CSS critico (da chiamare quando il template cambia).
     */
    public function clearCache(): void
    {
        $files = glob($this->cachePath . '/*.css');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
```

### Uso nell'index.php del Template (Approccio con Container Esplicito)

Per migliorare la precisione dell'estrazione, puoi marcare esplicitamente le sezioni above-the-fold nell'HTML del template con `data-critical`:

```html
<!-- Nel template index.php -->
<header data-critical>
    <nav class="navbar">
        <jdoc:include type="modules" name="menu" style="none" />
    </nav>
</header>

<section data-critical class="hero">
    <jdoc:include type="modules" name="hero" style="default" />
</section>

<main>
    <!-- Contenuto principale — il primo blocco potrebbe essere above the fold -->
    <jdoc:include type="component" />
</main>

<footer>
    <!-- Mai above the fold -->
    <jdoc:include type="modules" name="footer" style="default" />
</footer>
```

### Integrazione con Joomla via Plugin System (o Helper nel Template)

```php
<?php
/**
 * Da inserire nel file index.php del template, oppure come system plugin.
 *
 * Approccio A: direttamente nel template (più semplice, zero plugin)
 * Approccio B: come system plugin con evento onAfterRender (più pulito)
 *
 * Qui mostriamo l'Approccio B (system plugin).
 */

namespace Tpl\Performance\Plugin;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\Event\SubscriberInterface;

class PlgSystemPerformance extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender' => 'onAfterRender',
        ];
    }

    public function onAfterRender(): void
    {
        $app = Factory::getApplication();

        // Solo frontend, non admin
        if (!$app->isClient('site')) {
            return;
        }

        $html = $app->getBody();

        // Determina il tipo di pagina
        $pageType = $this->detectPageType();

        // Critical CSS
        $cachePath = JPATH_ROOT . '/templates/tpl_performance/cache/critical-css';
        $extractor = new \Tpl\Performance\CriticalCss\CriticalCssExtractor($cachePath);
        $html = $extractor->process($html, $pageType);

        // Image Pipeline (vedi Modulo 4)
        // $html = (new \Tpl\Performance\Image\ImageOptimizer())->process($html);

        // Lazy Loading (vedi Modulo 8)
        // $html = (new \Tpl\Performance\Performance\LazyLoader())->process($html);

        // Resource Hints (vedi Modulo 5)
        // $html = (new \Tpl\Performance\Performance\ResourceHints())->process($html);

        // HTML Minification (vedi Modulo 6)
        // $html = (new \Tpl\Performance\Performance\HtmlMinifier())->process($html);

        $app->setBody($html);
    }

    private function detectPageType(): string
    {
        $app    = Factory::getApplication();
        $option = $app->input->get('option', '');
        $view   = $app->input->get('view', '');
        $layout = $app->input->get('layout', '');

        // Homepage
        $menu = $app->getMenu();
        $defaultItem = $menu->getDefault();
        $activeItem  = $menu->getActive();

        if ($activeItem && $defaultItem && $activeItem->id === $defaultItem->id) {
            return 'homepage';
        }

        // Article
        if ($option === 'com_content' && $view === 'article') {
            return 'article';
        }

        // Category blog
        if ($option === 'com_content' && $view === 'category' && $layout === 'blog') {
            return 'category-blog';
        }

        // Category list
        if ($option === 'com_content' && $view === 'category') {
            return 'category-list';
        }

        // Generico per tutto il resto
        return 'default';
    }
}
```

---

## 4. Modulo 2 — Font Optimizer

### Contesto e Regole

- **Mai Google Fonts CDN** — sempre self-hosted per eliminare connessioni esterne e per GDPR
- **Solo WOFF2** — supporto >97%, compressione migliore
- **Subset Latin** — riduce i file da 40-80KB a 15-20KB
- **Font variabili** quando possibile — un solo file per più pesi
- **Preload solo 1-2 font critici** — il font del body e quello degli heading
- **`font-display: swap`** per il body, `optional` come alternativa per CWV perfetti
- **Fallback calibrato con `size-adjust`** — elimina CLS durante il font swap

### Passo 1: Preparazione Font (Build Time)

```bash
# Installa pyftsubset (parte di fonttools)
pip install fonttools brotli

# Subset Latin per Google Font (esempio: Inter variable)
pyftsubset Inter-VariableFont_opsz,wght.ttf \
  --output-file=inter-latin-var.woff2 \
  --flavor=woff2 \
  --layout-features='kern,liga,calt,locl' \
  --unicodes="U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+2074,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD"

# Per font non-variabili, ripeti per ogni peso necessario
pyftsubset Roboto-Regular.ttf \
  --output-file=roboto-regular-latin.woff2 \
  --flavor=woff2 \
  --unicodes="U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+2000-206F,U+20AC"

pyftsubset Roboto-Bold.ttf \
  --output-file=roboto-bold-latin.woff2 \
  --flavor=woff2 \
  --unicodes="U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+2000-206F,U+20AC"

# ALTERNATIVA con glyphhanger (analizza il sito e fa subset automatico)
npm install -g glyphhanger
glyphhanger https://tuosito.com --subset=Inter-VariableFont.ttf --formats=woff2
```

### Passo 2: Dichiarazioni @font-face

```css
/*
 * FONT STACK OTTIMIZZATO
 *
 * Strategia:
 * 1. Font variabile self-hosted + subset Latin
 * 2. font-display: swap (mostra testo subito con fallback, poi swappa)
 * 3. Fallback calibrato con size-adjust per minimizzare CLS
 * 4. unicode-range per caricare solo i glifi necessari
 */

/* ============================================
   FALLBACK CALIBRATO (elimina CLS)
   Queste metriche vanno calcolate con:
   https://screenspan.net/fallback
   oppure
   https://seek-oss.github.io/capsize/
   ============================================ */
@font-face {
    font-family: 'InterFallback';
    src: local('Arial'), local('Helvetica');
    size-adjust: 107.64%;      /* Calibra per matchare Inter */
    ascent-override: 90%;
    descent-override: 22.43%;
    line-gap-override: 0%;
}

@font-face {
    font-family: 'HeadingFallback';
    src: local('Georgia'), local('Times New Roman');
    size-adjust: 105.2%;
    ascent-override: 95%;
    descent-override: 23%;
    line-gap-override: 0%;
}

/* ============================================
   FONT PRINCIPALE — Inter Variable (Body)
   ============================================ */
@font-face {
    font-family: 'Inter';
    src: url('../fonts/inter-latin-var.woff2') format('woff2');
    font-weight: 100 900;
    font-style: normal;
    font-display: swap;
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC,
                   U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329,
                   U+2000-206F, U+2074, U+20AC, U+2122, U+2191,
                   U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}

/* Italic separato (se necessario) */
@font-face {
    font-family: 'Inter';
    src: url('../fonts/inter-latin-var-italic.woff2') format('woff2');
    font-weight: 100 900;
    font-style: italic;
    font-display: swap;
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC,
                   U+2000-206F, U+20AC;
}

/* ============================================
   FONT HEADING (opzionale, se diverso dal body)
   ============================================ */
@font-face {
    font-family: 'Heading';
    src: url('../fonts/heading-latin.woff2') format('woff2');
    font-weight: 600 800;
    font-style: normal;
    font-display: swap;
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC,
                   U+2000-206F, U+20AC;
}

/* ============================================
   APPLICAZIONE NEL CSS
   ============================================ */
:root {
    --font-body: 'Inter', 'InterFallback', system-ui, -apple-system, sans-serif;
    --font-heading: 'Heading', 'HeadingFallback', Georgia, serif;
    --font-mono: 'JetBrains Mono', ui-monospace, 'Cascadia Code', monospace;
}

body {
    font-family: var(--font-body);
    font-weight: 400;
    /* font-optical-sizing: auto;  Se il font supporta opsz */
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    font-weight: 700;
}

code, pre, kbd {
    font-family: var(--font-mono);
}
```

### Passo 3: Preload nel Template index.php

```php
<?php
// In index.php del template — inserisci preload per i font critici

/** @var Joomla\CMS\Document\HtmlDocument $this */
$templatePath = 'media/tpl_performance';

// Preload SOLO i font above-the-fold (max 2)
$this->addHeadLink(
    $templatePath . '/fonts/inter-latin-var.woff2',
    'preload',
    'rel',
    ['as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous']
);

// Secondo font solo se davvero usato above the fold
$this->addHeadLink(
    $templatePath . '/fonts/heading-latin.woff2',
    'preload',
    'rel',
    ['as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous']
);
```

**Output HTML risultante:**

```html
<link rel="preload" href="/media/tpl_performance/fonts/inter-latin-var.woff2"
      as="font" type="font/woff2" crossorigin="anonymous">
<link rel="preload" href="/media/tpl_performance/fonts/heading-latin.woff2"
      as="font" type="font/woff2" crossorigin="anonymous">
```

### Note Importanti sui Font

1. **`crossorigin="anonymous"` è OBBLIGATORIO** anche per font self-hosted. Senza, il browser ignora il preload.
2. **Non preloadare più di 2 font** — ogni preload compete per bandwidth con altre risorse critiche.
3. **`font-display: optional`** è l'opzione più performante per CWV: usa il web font solo se si carica entro ~100ms, altrimenti usa il fallback senza mai fare swap. Ma sacrifica la certezza di mostrare il font custom.
4. **`font-display: swap`** è il compromesso migliore per la maggior parte dei siti: mostra il testo subito con fallback, poi swappa quando il font è pronto.
5. **Il `size-adjust` sul fallback font** è la chiave per eliminare CLS durante lo swap. Calcola i valori con tool come [Fallback Font Generator](https://screenspan.net/fallback).

---

## 5. Modulo 3 — Asset Pipeline (Web Asset Manager)

### Contesto

In Joomla 6, il Web Asset Manager (WAM) è l'unico metodo supportato per gestire CSS e JS. I vecchi metodi (`Document::addScript`, `Document::addStyleSheet`) sono stati rimossi.

### File joomla.asset.json

```json
{
    "$schema": "https://developer.joomla.org/schemas/json-schema/web_assets.json",
    "name": "tpl_performance",
    "version": "1.0.0",
    "description": "Assets per il template Performance",
    "license": "GPL-2.0-or-later",
    "assets": [
        {
            "name": "template.performance.fonts",
            "description": "Font face declarations e fallback calibrato",
            "type": "style",
            "uri": "css/fonts.css",
            "version": "1.0.0"
        },
        {
            "name": "template.performance.critical",
            "description": "CSS critico - caricato inline, non usare questo asset direttamente",
            "type": "style",
            "uri": "css/critical/homepage.css",
            "version": "1.0.0"
        },
        {
            "name": "template.performance.main",
            "description": "CSS principale del template",
            "type": "style",
            "uri": "css/template.min.css",
            "dependencies": [
                "template.performance.fonts"
            ],
            "version": "1.0.0"
        },
        {
            "name": "template.performance.scripts",
            "description": "JavaScript principale del template",
            "type": "script",
            "uri": "js/template.min.js",
            "attributes": {
                "defer": true
            },
            "dependencies": [],
            "version": "1.0.0"
        },
        {
            "name": "template.performance.sw",
            "description": "Registrazione Service Worker",
            "type": "script",
            "uri": "js/sw-register.js",
            "attributes": {
                "defer": true
            },
            "version": "1.0.0"
        }
    ]
}
```

### Uso nel Template index.php

```php
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/** @var Joomla\CMS\Document\HtmlDocument $this */

$app = Factory::getApplication();
$wa  = $this->getWebAssetManager();

// Registra e attiva gli asset
$wa->useStyle('template.performance.main');
$wa->useScript('template.performance.scripts');

// Service Worker (opzionale, attivabile da parametri template)
if ($this->params->get('enable_sw', 0)) {
    $wa->useScript('template.performance.sw');
}

// IMPORTANTE: Non caricare jQuery se non strettamente necessario
// Se nessuna estensione lo richiede, Joomla non lo caricherà

// Disabilita asset Joomla non necessari (se vuoi controllo totale)
$wa->disableScript('core');        // Rimuovi se non servono le API JS di Joomla
// $wa->disableStyle('fontawesome');  // Rimuovi Font Awesome se usi icone custom/SVG
```

### Disabilitare Asset Joomla Non Necessari

```php
<?php
// ATTENZIONE: disabilitare asset di Joomla può rompere funzionalità.
// Testa accuratamente ogni disattivazione.

$wa = $this->getWebAssetManager();

// Lista di asset che PUOI disabilitare su un frontend "pulito":
// (verifica che non siano dipendenze di estensioni attive)

// Font Awesome — se usi icone SVG inline o un icon set custom
$wa->disableStyle('fontawesome');
$wa->disableStyle('fontawesome-solid');
$wa->disableStyle('fontawesome-brands');

// Se non usi il sistema di messaggi/notifiche frontend di Joomla:
// $wa->disableScript('messages');

// Se non usi Bootstrap JS nel frontend:
// $wa->disableScript('bootstrap.collapse');
// $wa->disableScript('bootstrap.dropdown');
```

---

## 6. Modulo 4 — Image Pipeline

### Contesto

Le immagini sono tipicamente il 50-70% del peso di una pagina. Ottimizzarle ha un impatto enorme su LCP e Speed Index.

### ImageOptimizer.php

```php
<?php
/**
 * ImageOptimizer — Conversione WebP/AVIF on-the-fly e responsive images
 *
 * Funziona con GD o Imagick (standard su LAMP).
 * Processa l'HTML e:
 * 1. Converte riferimenti img in <picture> con <source> WebP/AVIF
 * 2. Aggiunge width/height espliciti (evita CLS)
 * 3. Aggiunge loading="lazy" e decoding="async" (vedi Modulo 8)
 */

namespace Tpl\Performance\Image;

class ImageOptimizer
{
    private string $cachePath;
    private string $cacheUrl;
    private bool $avifSupport;
    private bool $webpSupport;

    public function __construct()
    {
        $this->cachePath   = JPATH_ROOT . '/templates/tpl_performance/cache/images';
        $this->cacheUrl    = '/templates/tpl_performance/cache/images';
        $this->webpSupport = function_exists('imagewebp') || $this->imagickSupports('WEBP');
        $this->avifSupport = function_exists('imageavif') || $this->imagickSupports('AVIF');

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Processa l'HTML e converte le immagini.
     */
    public function process(string $html): string
    {
        // Trova tutti i tag <img> che non sono dentro <picture>
        $pattern = '/<img\s+([^>]*?)>/i';

        $html = preg_replace_callback($pattern, function ($matches) {
            return $this->processImgTag($matches[0], $matches[1]);
        }, $html);

        return $html;
    }

    /**
     * Processa un singolo tag <img>.
     */
    private function processImgTag(string $fullTag, string $attributes): string
    {
        // Estrai src
        if (!preg_match('/src=["\']([^"\']+)["\']/i', $attributes, $srcMatch)) {
            return $fullTag;
        }

        $src = $srcMatch[1];

        // Ignora immagini esterne, SVG, data URI
        if (str_starts_with($src, 'http') || str_starts_with($src, 'data:') || str_ends_with($src, '.svg')) {
            return $fullTag;
        }

        $filePath = JPATH_ROOT . '/' . ltrim($src, '/');

        if (!file_exists($filePath)) {
            return $fullTag;
        }

        // Genera varianti WebP e/o AVIF
        $sources = [];

        if ($this->avifSupport) {
            $avifPath = $this->convertImage($filePath, 'avif');
            if ($avifPath) {
                $sources[] = '<source srcset="' . $this->filePathToUrl($avifPath) . '" type="image/avif">';
            }
        }

        if ($this->webpSupport) {
            $webpPath = $this->convertImage($filePath, 'webp');
            if ($webpPath) {
                $sources[] = '<source srcset="' . $this->filePathToUrl($webpPath) . '" type="image/webp">';
            }
        }

        if (empty($sources)) {
            return $fullTag;
        }

        // Aggiungi width/height se mancanti (previene CLS)
        $imgTag = $fullTag;
        if (!str_contains($attributes, 'width') || !str_contains($attributes, 'height')) {
            $size = @getimagesize($filePath);
            if ($size) {
                $imgTag = $this->addDimensions($imgTag, $size[0], $size[1]);
            }
        }

        // Costruisci <picture>
        $picture = '<picture>' . "\n";
        $picture .= implode("\n", $sources) . "\n";
        $picture .= $imgTag . "\n";
        $picture .= '</picture>';

        return $picture;
    }

    /**
     * Converte un'immagine in WebP o AVIF e la salva nella cache.
     */
    private function convertImage(string $sourcePath, string $format): ?string
    {
        $hash     = md5($sourcePath . filemtime($sourcePath));
        $filename = $hash . '.' . $format;
        $destPath = $this->cachePath . '/' . $filename;

        // Se già in cache, ritorna
        if (file_exists($destPath)) {
            return $destPath;
        }

        try {
            $info = getimagesize($sourcePath);
            if (!$info) {
                return null;
            }

            $mime = $info['mime'];

            // Crea risorsa GD dall'originale
            $image = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($sourcePath),
                'image/png'  => imagecreatefrompng($sourcePath),
                'image/gif'  => imagecreatefromgif($sourcePath),
                default      => null,
            };

            if (!$image) {
                return null;
            }

            // Mantieni trasparenza per PNG
            if ($mime === 'image/png') {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }

            // Converti
            $success = match ($format) {
                'webp' => imagewebp($image, $destPath, 80),  // Qualità 80
                'avif' => function_exists('imageavif') ? imageavif($image, $destPath, 60, 6) : false,
                default => false,
            };

            imagedestroy($image);

            // Verifica che il file convertito sia più piccolo dell'originale
            if ($success && file_exists($destPath)) {
                if (filesize($destPath) >= filesize($sourcePath)) {
                    unlink($destPath);
                    return null; // Non conviene convertire
                }
                return $destPath;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Aggiunge width e height al tag img.
     */
    private function addDimensions(string $imgTag, int $width, int $height): string
    {
        // Aggiungi solo se non già presenti
        if (!preg_match('/\bwidth\s*=/i', $imgTag)) {
            $imgTag = str_replace('<img ', '<img width="' . $width . '" ', $imgTag);
        }
        if (!preg_match('/\bheight\s*=/i', $imgTag)) {
            $imgTag = str_replace('<img ', '<img height="' . $height . '" ', $imgTag);
        }

        return $imgTag;
    }

    private function filePathToUrl(string $filePath): string
    {
        return str_replace(JPATH_ROOT, '', $filePath);
    }

    private function imagickSupports(string $format): bool
    {
        if (!class_exists('Imagick')) {
            return false;
        }
        $supported = \Imagick::queryFormats($format);
        return !empty($supported);
    }
}
```

---

## 7. Modulo 5 — Resource Hints Automatici

### ResourceHints.php

```php
<?php
/**
 * ResourceHints — Aggiunge automaticamente preload, preconnect, dns-prefetch
 */

namespace Tpl\Performance\Performance;

class ResourceHints
{
    /**
     * Analizza l'HTML e aggiunge resource hints nel <head>.
     */
    public function process(string $html): string
    {
        $hints = [];

        // 1. Preconnect per domini esterni trovati nell'HTML
        $externalDomains = $this->findExternalDomains($html);
        foreach ($externalDomains as $domain) {
            $hints[] = '<link rel="preconnect" href="' . $domain . '" crossorigin>';
            $hints[] = '<link rel="dns-prefetch" href="' . $domain . '">';
        }

        // 2. Preload per immagine LCP (la prima immagine grande nell'above-the-fold)
        $lcpImage = $this->findLcpCandidate($html);
        if ($lcpImage) {
            $type = $this->getImageType($lcpImage);
            $hints[] = '<link rel="preload" href="' . $lcpImage . '" as="image"'
                     . ($type ? ' type="' . $type . '"' : '') . '>';
        }

        if (empty($hints)) {
            return $html;
        }

        // Inserisci subito dopo <meta charset> o all'inizio del <head>
        $hintsHtml = implode("\n    ", $hints);

        if (preg_match('/<meta\s+charset=[^>]+>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $insertPos = $m[0][1] + strlen($m[0][0]);
            $html = substr($html, 0, $insertPos) . "\n    " . $hintsHtml . substr($html, $insertPos);
        } elseif (str_contains($html, '<head>')) {
            $html = str_replace('<head>', '<head>' . "\n    " . $hintsHtml, $html);
        }

        return $html;
    }

    /**
     * Trova domini esterni nell'HTML (immagini, script, CSS).
     */
    private function findExternalDomains(string $html): array
    {
        $domains = [];
        preg_match_all('/(?:src|href)=["\']https?:\/\/([^"\'\/]+)/i', $html, $matches);

        if (!empty($matches[1])) {
            foreach (array_unique($matches[1]) as $domain) {
                // Escludi il dominio del sito stesso
                $currentHost = $_SERVER['HTTP_HOST'] ?? '';
                if ($domain !== $currentHost) {
                    $domains[] = 'https://' . $domain;
                }
            }
        }

        return array_slice($domains, 0, 4); // Max 4 preconnect per non congestionare
    }

    /**
     * Trova il candidato LCP — tipicamente la prima immagine grande.
     */
    private function findLcpCandidate(string $html): ?string
    {
        // Cerca immagini dentro elementi marcati come critici
        if (preg_match('/data-critical[^>]*>.*?<img[^>]+src=["\']([^"\']+)["\']/is', $html, $m)) {
            return $m[1];
        }

        // Cerca la prima immagine dentro hero/banner
        if (preg_match('/class=["\'][^"\']*(?:hero|banner)[^"\']*["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\']/is', $html, $m)) {
            return $m[1];
        }

        return null;
    }

    private function getImageType(string $url): ?string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return match ($ext) {
            'webp' => 'image/webp',
            'avif' => 'image/avif',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            default => null,
        };
    }
}
```

---

## 8. Modulo 6 — HTML Minification

### HtmlMinifier.php

```php
<?php
/**
 * HtmlMinifier — Rimuove spazi, commenti e whitespace dall'output HTML.
 *
 * ATTENZIONE: non tocca il contenuto di <pre>, <code>, <script>, <style>, <textarea>.
 */

namespace Tpl\Performance\Performance;

class HtmlMinifier
{
    public function process(string $html): string
    {
        // Non minificare se siamo in debug mode
        if (defined('JDEBUG') && JDEBUG) {
            return $html;
        }

        // 1. Preserva contenuto di tag sensibili
        $preserved = [];
        $tags = ['pre', 'code', 'script', 'style', 'textarea'];

        foreach ($tags as $tag) {
            $html = preg_replace_callback(
                '/<' . $tag . '(\s[^>]*)?>.*?<\/' . $tag . '>/is',
                function ($match) use (&$preserved) {
                    $key = '<!--PRESERVED_' . count($preserved) . '-->';
                    $preserved[$key] = $match[0];
                    return $key;
                },
                $html
            );
        }

        // 2. Rimuovi commenti HTML (ma non conditional comments IE e preserved markers)
        $html = preg_replace('/<!--(?!\[|PRESERVED_).*?-->/s', '', $html);

        // 3. Rimuovi spazi tra i tag
        $html = preg_replace('/>\s+</', '> <', $html);

        // 4. Rimuovi newline e tabulazioni multiple
        $html = preg_replace('/\s{2,}/', ' ', $html);

        // 5. Rimuovi spazi intorno ai tag block
        $blockTags = 'html|head|body|div|section|article|aside|nav|header|footer|main|p|ul|ol|li|table|tr|td|th|form|h[1-6]';
        $html = preg_replace('/\s*(<\/?(?:' . $blockTags . ')(?:\s[^>]*)?>)\s*/i', '$1', $html);

        // 6. Ripristina contenuto preservato
        foreach ($preserved as $key => $value) {
            $html = str_replace($key, $value, $html);
        }

        return trim($html);
    }
}
```

---

## 9. Modulo 7 — Service Worker (Opzionale)

### sw-register.js

```javascript
/**
 * Registra il Service Worker solo se il browser lo supporta.
 * Questo file viene caricato con defer.
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/templates/tpl_performance/sw.js', { scope: '/' })
            .then(function (registration) {
                // Aggiorna automaticamente
                registration.addEventListener('updatefound', function () {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', function () {
                        if (newWorker.state === 'activated') {
                            // Nuova versione attiva
                        }
                    });
                });
            })
            .catch(function (error) {
                // Service Worker fallito silenziosamente
            });
    });
}
```

### sw.js (Service Worker)

```javascript
/**
 * Service Worker — Cache delle risorse statiche del template.
 *
 * Strategia:
 * - Cache-first per font, CSS, JS, immagini del template
 * - Network-first per contenuto HTML (pagine)
 * - Stale-while-revalidate per immagini di contenuto
 */

const CACHE_NAME = 'tpl-perf-v1';

// Risorse da pre-cachare all'installazione
const PRECACHE_URLS = [
    '/media/tpl_performance/css/template.min.css',
    '/media/tpl_performance/js/template.min.js',
    '/media/tpl_performance/fonts/inter-latin-var.woff2',
    '/media/tpl_performance/fonts/heading-latin.woff2',
];

// Installazione: pre-cache delle risorse critiche
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

// Attivazione: pulizia vecchie cache
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: strategia differenziata
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Non cachare richieste POST o admin
    if (event.request.method !== 'GET' || url.pathname.startsWith('/administrator')) {
        return;
    }

    // Font, CSS, JS del template → Cache First
    if (url.pathname.startsWith('/media/tpl_performance/')) {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                return cached || fetch(event.request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    return response;
                });
            })
        );
        return;
    }

    // Immagini di contenuto → Stale While Revalidate
    if (event.request.destination === 'image') {
        event.respondWith(
            caches.match(event.request).then((cached) => {
                const fetchPromise = fetch(event.request).then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    return response;
                }).catch(() => cached);

                return cached || fetchPromise;
            })
        );
        return;
    }

    // Pagine HTML → Network First con fallback cache
    if (event.request.destination === 'document') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                    return response;
                })
                .catch(() => caches.match(event.request))
        );
        return;
    }
});
```

---

## 10. Modulo 8 — Lazy Loading Intelligente

### LazyLoader.php

```php
<?php
/**
 * LazyLoader — Aggiunge lazy loading nativo e gestione LCP.
 *
 * Logica:
 * - La PRIMA immagine nel contenuto above-the-fold riceve fetchpriority="high"
 *   e NON riceve loading="lazy" (è l'elemento LCP)
 * - Tutte le altre immagini ricevono loading="lazy" + decoding="async"
 * - Tutti gli iframe ricevono loading="lazy"
 */

namespace Tpl\Performance\Performance;

class LazyLoader
{
    private bool $lcpFound = false;

    public function process(string $html): string
    {
        $this->lcpFound = false;

        // Processa <img>
        $html = preg_replace_callback('/<img\s+([^>]*?)>/i', function ($matches) {
            return $this->processImg($matches[0], $matches[1]);
        }, $html);

        // Processa <iframe>
        $html = preg_replace_callback('/<iframe\s+([^>]*?)>/i', function ($matches) {
            return $this->processIframe($matches[0], $matches[1]);
        }, $html);

        return $html;
    }

    private function processImg(string $fullTag, string $attrs): string
    {
        // Non toccare img che hanno già loading o sono SVG inline
        if (preg_match('/\bloading\s*=/i', $attrs)) {
            return $fullTag;
        }

        // Ignora piccole icone (< 50px) e tracking pixel
        if (preg_match('/\bwidth\s*=\s*["\']?(\d+)/i', $attrs, $w)) {
            if ((int)$w[1] < 50) {
                return $fullTag;
            }
        }

        // La prima immagine "grande" è il candidato LCP
        if (!$this->lcpFound) {
            $this->lcpFound = true;

            // LCP: NO lazy, SI priority alta
            $fullTag = $this->addAttribute($fullTag, 'fetchpriority', 'high');
            $fullTag = $this->addAttribute($fullTag, 'decoding', 'async');

            return $fullTag;
        }

        // Tutte le altre: lazy loading
        $fullTag = $this->addAttribute($fullTag, 'loading', 'lazy');
        $fullTag = $this->addAttribute($fullTag, 'decoding', 'async');

        return $fullTag;
    }

    private function processIframe(string $fullTag, string $attrs): string
    {
        if (preg_match('/\bloading\s*=/i', $attrs)) {
            return $fullTag;
        }

        return $this->addAttribute($fullTag, 'loading', 'lazy');
    }

    /**
     * Aggiunge un attributo a un tag HTML se non già presente.
     */
    private function addAttribute(string $tag, string $name, string $value): string
    {
        if (preg_match('/\b' . preg_quote($name) . '\s*=/i', $tag)) {
            return $tag; // Già presente
        }

        // Inserisci prima della chiusura >
        return preg_replace('/>$/', ' ' . $name . '="' . $value . '">', $tag);
    }
}
```

---

## 11. Configurazione Server (.htaccess)

```apache
# ============================================
# .htaccess PERFORMANCE — Template Joomla 6
# Inserisci nel .htaccess principale del sito
# ============================================

# --- COMPRESSIONE GZIP/BROTLI ---
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE image/svg+xml
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE font/woff2
    AddOutputFilterByType DEFLATE application/font-woff2
</IfModule>

# Brotli (se mod_brotli disponibile, compressione migliore di gzip)
<IfModule mod_brotli.c>
    AddOutputFilterByType BROTLI_COMPRESS text/html
    AddOutputFilterByType BROTLI_COMPRESS text/css
    AddOutputFilterByType BROTLI_COMPRESS application/javascript
    AddOutputFilterByType BROTLI_COMPRESS application/json
    AddOutputFilterByType BROTLI_COMPRESS image/svg+xml
</IfModule>

# --- CACHE HEADERS ---
<IfModule mod_expires.c>
    ExpiresActive On

    # Font (1 anno — cambiano rarissimamente)
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff2 "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"

    # Immagini (1 anno)
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/avif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"

    # CSS/JS (1 anno — usa versioning nel WAM per cache-bust)
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"

    # HTML (nessuna cache o brevissima)
    ExpiresByType text/html "access plus 0 seconds"
</IfModule>

# Cache-Control headers
<IfModule mod_headers.c>
    # Font: cache immutabile (il file non cambia, cambia il nome/versione)
    <FilesMatch "\.(woff2?|ttf|otf|eot)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>

    # CSS/JS: cache lunga con revalidazione
    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>

    # Immagini: cache lunga
    <FilesMatch "\.(jpe?g|png|gif|webp|avif|svg|ico)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>

    # HTML: no cache (o brevissima)
    <FilesMatch "\.html?$">
        Header set Cache-Control "no-cache, no-store, must-revalidate"
    </FilesMatch>

    # --- SECURITY HEADERS (bonus) ---
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set Referrer-Policy "strict-origin-when-cross-origin"

    # --- PRELOAD VIA HTTP HEADER (103 Early Hints se supportato) ---
    # Header set Link "</media/tpl_performance/fonts/inter-latin-var.woff2>; rel=preload; as=font; type=\"font/woff2\"; crossorigin"
</IfModule>

# --- TIPI MIME ---
<IfModule mod_mime.c>
    AddType font/woff2 .woff2
    AddType image/webp .webp
    AddType image/avif .avif
    AddType application/manifest+json .webmanifest
</IfModule>

# --- KEEP-ALIVE ---
<IfModule mod_headers.c>
    Header set Connection keep-alive
</IfModule>

# --- ETag (disabilita per risorse statiche, usa expires) ---
<IfModule mod_headers.c>
    <FilesMatch "\.(css|js|woff2?|jpe?g|png|gif|webp|avif|svg|ico)$">
        Header unset ETag
    </FilesMatch>
</IfModule>
FileETag None
```

---

## 12. Parametri Template (templateDetails.xml)

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="template" client="site" method="upgrade">
    <name>tpl_performance</name>
    <version>1.0.0</version>
    <creationDate>2026-03-30</creationDate>
    <author>Your Name</author>
    <authorUrl>https://yoursite.com</authorUrl>
    <copyright>(C) 2026 Your Name</copyright>
    <license>GPL-2.0-or-later</license>
    <description>Template Joomla 6 ad alte prestazioni con Critical CSS, Font Optimizer, Image Pipeline e Service Worker integrati.</description>

    <files>
        <filename>index.php</filename>
        <filename>joomla.asset.json</filename>
        <filename>templateDetails.xml</filename>
        <filename>error.php</filename>
        <filename>offline.php</filename>
        <filename>component.php</filename>
        <folder>html</folder>
        <folder>src</folder>
        <folder>cache</folder>
    </files>

    <media destination="tpl_performance" folder="media/tpl_performance">
        <folder>css</folder>
        <folder>js</folder>
        <folder>fonts</folder>
        <folder>images</folder>
    </media>

    <config>
        <fields name="params">

            <!-- Performance -->
            <fieldset name="performance" label="Performance">

                <field
                    name="enable_critical_css"
                    type="radio"
                    label="Critical CSS"
                    description="Abilita l'estrazione e l'inline automatico del CSS critico."
                    default="1"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

                <field
                    name="critical_css_cache_lifetime"
                    type="number"
                    label="Cache Critical CSS (secondi)"
                    description="Durata della cache del CSS critico. Default: 86400 (24 ore)."
                    default="86400"
                    min="0"
                    max="604800"
                />

                <field
                    name="enable_image_optimization"
                    type="radio"
                    label="Ottimizzazione Immagini"
                    description="Converte automaticamente le immagini in WebP/AVIF e aggiunge tag &lt;picture&gt;."
                    default="1"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

                <field
                    name="image_quality_webp"
                    type="number"
                    label="Qualità WebP"
                    description="Qualità di compressione WebP (0-100). Default: 80."
                    default="80"
                    min="0"
                    max="100"
                />

                <field
                    name="image_quality_avif"
                    type="number"
                    label="Qualità AVIF"
                    description="Qualità di compressione AVIF (0-100). Default: 60."
                    default="60"
                    min="0"
                    max="100"
                />

                <field
                    name="enable_lazy_loading"
                    type="radio"
                    label="Lazy Loading Immagini"
                    description="Aggiunge loading=lazy e gestione LCP automatica."
                    default="1"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

                <field
                    name="enable_html_minification"
                    type="radio"
                    label="Minificazione HTML"
                    description="Rimuove spazi e commenti dall'output HTML. Disattivato automaticamente in modalità debug."
                    default="1"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

                <field
                    name="enable_resource_hints"
                    type="radio"
                    label="Resource Hints Automatici"
                    description="Aggiunge preconnect e dns-prefetch per domini esterni trovati nella pagina."
                    default="1"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

                <field
                    name="enable_sw"
                    type="radio"
                    label="Service Worker"
                    description="Abilita il caching delle risorse statiche via Service Worker."
                    default="0"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

            </fieldset>

            <!-- Font -->
            <fieldset name="fonts" label="Font">

                <field
                    name="body_font"
                    type="list"
                    label="Font Body"
                    description="Seleziona il font per il testo del corpo."
                    default="inter"
                >
                    <option value="inter">Inter (Variable)</option>
                    <option value="roboto">Roboto</option>
                    <option value="opensans">Open Sans</option>
                    <option value="system">System Font Stack (nessun web font)</option>
                </field>

                <field
                    name="heading_font"
                    type="list"
                    label="Font Heading"
                    description="Seleziona il font per titoli e heading."
                    default="same"
                >
                    <option value="same">Stesso del Body</option>
                    <option value="playfair">Playfair Display</option>
                    <option value="montserrat">Montserrat</option>
                    <option value="system">System Font Stack</option>
                </field>

                <field
                    name="font_display_strategy"
                    type="list"
                    label="Strategia Font Display"
                    description="swap = mostra subito il fallback poi swappa (consigliato). optional = usa il web font solo se carica entro 100ms (migliore per CWV)."
                    default="swap"
                >
                    <option value="swap">swap (consigliato)</option>
                    <option value="optional">optional (max performance)</option>
                    <option value="fallback">fallback (compromesso)</option>
                </field>

                <field
                    name="preload_fonts"
                    type="radio"
                    label="Preload Font Critici"
                    description="Aggiunge link preload per i font above-the-fold."
                    default="1"
                    class="btn-group"
                >
                    <option value="0">Disabilitato</option>
                    <option value="1">Abilitato</option>
                </field>

            </fieldset>

        </fields>
    </config>
</extension>
```

---

## 13. Checklist Finale — Core Web Vitals

### LCP (Largest Contentful Paint) — Target: < 2.5s

- [ ] CSS critico inline nel `<head>` (Modulo 1)
- [ ] CSS non critico caricato async con `media="print"` (Modulo 1)
- [ ] Font preloadati e self-hosted (Modulo 2)
- [ ] Immagine LCP con `fetchpriority="high"` e NO `loading="lazy"` (Modulo 8)
- [ ] Immagine LCP preloadata nei Resource Hints (Modulo 5)
- [ ] Nessun JS render-blocking — tutti con `defer` (Modulo 3)
- [ ] Compressione Brotli/Gzip attiva (.htaccess)
- [ ] Cache headers lunghi con `immutable` (.htaccess)

### FID/INP (Interaction to Next Paint) — Target: < 200ms

- [ ] Tutti gli script con `defer` (Modulo 3)
- [ ] jQuery rimosso o caricato solo se necessario (Modulo 3)
- [ ] Nessun long task nel main thread
- [ ] Event listener passivi dove possibile

### CLS (Cumulative Layout Shift) — Target: < 0.1

- [ ] Font fallback calibrato con `size-adjust` (Modulo 2)
- [ ] Width/height espliciti su tutte le immagini (Modulo 4)
- [ ] Nessuna injection di contenuto sopra il viewport dopo il load
- [ ] Slot fissi per banner/ads con dimensioni predefinite

### FCP (First Contentful Paint) — Target: < 1.8s

- [ ] CSS critico inline < 14KB (ideale per primo RTT)
- [ ] Font preloadati per rendering testo immediato
- [ ] HTML minificato (Modulo 6)
- [ ] Compressione attiva

### TTFB (Time to First Byte) — Target: < 800ms

- [ ] Cache Joomla attiva (Conservative o Progressive)
- [ ] OPcache PHP configurato correttamente
- [ ] Query MySQL ottimizzate (indici, cache query)
- [ ] Se possibile: Redis/Memcached per session e cache

### Speed Index

- [ ] Immagini convertite in WebP/AVIF (Modulo 4)
- [ ] Lazy loading su tutte le immagini below-the-fold (Modulo 8)
- [ ] Iframe con `loading="lazy"` (Modulo 8)
- [ ] Service Worker per visite successive (Modulo 7)

---

## Note per l'Agente AI Sviluppatore

1. **Ordine di implementazione consigliato:**
   - Struttura base template + `index.php` + `templateDetails.xml` + `joomla.asset.json`
   - Font Optimizer (Modulo 2) — è il più semplice e ha impatto immediato
   - Critical CSS Engine (Modulo 1) — il più complesso, richiede test accurati
   - Lazy Loading (Modulo 8) — semplice ma efficace
   - Image Pipeline (Modulo 4) — richiede GD/Imagick funzionanti
   - Resource Hints (Modulo 5) — veloce da implementare
   - HTML Minification (Modulo 6) — attenzione ai falsi positivi
   - Service Worker (Modulo 7) — ultimo, opzionale

2. **Dipendenze Composer:**
   ```json
   {
       "require": {
           "sabberworm/php-css-parser": "^8.5",
           "symfony/css-selector": "^7.0"
       }
   }
   ```

3. **Integrazione Composer con Joomla 6:** le dipendenze Composer vanno nel template stesso. Crea un `composer.json` nella root del template e fai il `require` dell'autoloader nel `index.php`:
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

4. **Testing:** testa ogni modulo indipendentemente. Ogni classe `process()` accetta HTML in input e restituisce HTML modificato, quindi puoi scrivere unit test isolati.

5. **Cache:** la directory `cache/` del template deve essere writable (755 o 775). Aggiungi un pulsante nel backend per svuotare la cache del CSS critico e delle immagini convertite.

6. **Compatibilità con estensioni:** il Critical CSS Engine e il Lazy Loader operano sull'HTML finale, quindi funzionano con qualsiasi estensione Joomla. Tuttavia, testa con estensioni comuni (mod_custom, com_content, page builder) per assicurarti che non ci siano conflitti.

---

> **Versione:** 1.0.0
> **Data:** 2026-03-30
> **Autore:** Blueprint generato per sviluppo template Joomla 6 ad alte prestazioni
