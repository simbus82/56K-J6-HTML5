<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Agency56k\Template\Html56k\Site\Font;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/**
 * FontOptimizer — Gestione ottimizzata del caricamento font.
 *
 * Funzionalità:
 * - Inietta <link rel="preload"> per i font critici (max 2)
 * - Supporta font self-hosted in formato WOFF2
 * - crossorigin="anonymous" incluso automaticamente (obbligatorio anche per self-hosted)
 * - Attivabile/disattivabile da parametri template
 *
 * Regole di performance (dal blueprint):
 * - Mai Google Fonts CDN → sempre self-hosted per GDPR e performance
 * - Solo WOFF2 → supporto >97%, compressione migliore
 * - Preload max 2 font → ogni preload compete per bandwidth
 * - font-display: swap → mostra testo subito con fallback calibrato
 * - size-adjust sul fallback → elimina CLS durante lo swap
 */
class FontOptimizer
{
    /**
     * Font critici da preloadare.
     * Ogni entry ha:
     *   'file'   => nome file nella cartella fonts/ del template
     *   'weight' => peso/range (per documentazione, non usato nel preload)
     *
     * NOTA: Modificare questo array per adattarlo ai font utilizzati nel progetto.
     */
    private const CRITICAL_FONTS = [
        [
            'file'   => 'inter-latin-var.woff2',
            'weight' => '100 900',
        ],
        // Decommentare se si usa un font heading diverso dal body:
        // [
        //     'file'   => 'heading-latin.woff2',
        //     'weight' => '600 800',
        // ],
    ];

    /**
     * Inietta i tag <link rel="preload"> per i font critici nel <head>.
     *
     * Deve essere chiamato PRIMA dell'output HTML nel template (in index.php).
     * Joomla si occupa di renderizzare gli addHeadLink nel <head>.
     *
     * @param   \Joomla\CMS\Document\HtmlDocument  $document  Il documento HTML del template
     * @param   \Joomla\Registry\Registry           $params    I parametri del template
     *
     * @return  void
     */
    public static function preload($document, $params): void
    {
        // Controlla se il font preload è abilitato nei parametri
        if ((int) $params->get('font_preload', 1) === 0) {
            return;
        }

        // Percorso base dei font nel template
        $templateName = $document->template ?? 'html56k';
        $fontsPath    = 'templates/' . $templateName . '/fonts';

        foreach (self::CRITICAL_FONTS as $font) {
            $fontFile = $font['file'];
            $fontUrl  = $fontsPath . '/' . $fontFile;

            // Verifica che il file esista fisicamente prima di preloadarlo
            $fullPath = JPATH_ROOT . '/' . $fontUrl;
            if (!file_exists($fullPath)) {
                continue;
            }

            // Inietta il preload tramite l'API di Joomla
            $document->addHeadLink(
                $fontUrl,
                'preload',
                'rel',
                [
                    'as'          => 'font',
                    'type'        => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ]
            );
        }
    }
}
