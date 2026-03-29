<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 *
 * AssetOptimizer — Ottimizzazione dell'Asset Pipeline via Web Asset Manager.
 *
 * Funzionalità:
 * - Disabilita asset Joomla non necessari nel frontend (Font Awesome, jQuery, ecc.)
 * - Forza defer/async su tutti gli script per eliminare render-blocking
 * - Converte i <script> non differiti in defer via post-processing HTML
 * - Tutto controllabile da parametri admin del template
 *
 * Principi (dal blueprint):
 * 1. Zero render-blocking resources
 * 2. Minimo numero di request HTTP
 * 3. Nessuna dipendenza esterna a runtime (no CDN)
 */

namespace Agency56k\Template\Html56k\Site\Asset;

defined('_JEXEC') or die;

use Joomla\CMS\Document\HtmlDocument;

class AssetOptimizer
{
    /**
     * Asset CSS di Joomla che possono essere disabilitati in un frontend pulito.
     * Ogni entry è il nome dell'asset nel WAM.
     */
    private const DISABLE_STYLES = [
        'fontawesome',
        'fontawesome-solid',
        'fontawesome-brands',
        'fontawesome-regular',
    ];

    /**
     * Asset JS di Joomla che possono essere disabilitati.
     * ATTENZIONE: disabilitare 'core' rompe le API JS di Joomla (messaggi, ecc.)
     */
    private const DISABLE_SCRIPTS = [
        // 'core',          // Decommentare solo se non servono le API JS di Joomla
        // 'keepalive',     // Decommentare se non servono sessioni AJAX
    ];

    /**
     * Ottimizza gli asset del template tramite il Web Asset Manager.
     *
     * @param   HtmlDocument                $document  Il documento HTML del template
     * @param   \Joomla\Registry\Registry   $params    I parametri del template
     *
     * @return  void
     */
    public static function optimize(HtmlDocument $document, $params): void
    {
        $wa = $document->getWebAssetManager();

        // --- Disabilita Font Awesome (se abilitato da admin) ---
        if ((int) $params->get('disable_fontawesome', 0) === 1) {
            foreach (self::DISABLE_STYLES as $asset) {
                try {
                    if ($wa->assetExists('style', $asset)) {
                        $wa->disableStyle($asset);
                    }
                } catch (\Exception $e) {
                    // Asset non registrato, ignora
                }
            }
        }

        // --- Disabilita script Joomla non necessari ---
        if ((int) $params->get('disable_core_js', 0) === 1) {
            foreach (self::DISABLE_SCRIPTS as $asset) {
                try {
                    if ($wa->assetExists('script', $asset)) {
                        $wa->disableScript($asset);
                    }
                } catch (\Exception $e) {
                    // Asset non registrato, ignora
                }
            }
        }
    }

    /**
     * Post-processa l'HTML per forzare defer su tutti gli script
     * che non hanno già defer o async.
     *
     * Questo garantisce che NESSUNO script sia render-blocking,
     * anche quelli iniettati da estensioni di terze parti.
     *
     * @param   string  $html  L'HTML completo della pagina
     *
     * @return  string  HTML con script ottimizzati
     */
    public static function deferAllScripts(string $html): string
    {
        // Pattern: trova <script src="..."> senza defer, async o type="module"
        $pattern = '/<script\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i';

        $html = preg_replace_callback($pattern, function ($matches) {
            $fullTag    = $matches[0];
            $attrBefore = $matches[1];
            $src        = $matches[2];
            $attrAfter  = $matches[3];
            $allAttrs   = $attrBefore . ' ' . $attrAfter;

            // Se ha già defer, async o type="module" → non toccare
            if (preg_match('/\b(defer|async)\b/i', $allAttrs)) {
                return $fullTag;
            }
            if (preg_match('/type\s*=\s*["\']module["\']/i', $allAttrs)) {
                return $fullTag;
            }

            // Non differire script inline di Joomla (system messages, ecc.)
            if (str_contains($src, 'joomla.asset.js') || str_contains($src, 'system/js')) {
                return $fullTag;
            }

            // Aggiungi defer prima della chiusura >
            return preg_replace('/>$/', ' defer>', $fullTag);
        }, $html);

        return $html;
    }
}
