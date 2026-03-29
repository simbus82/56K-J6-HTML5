<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 *
 * LazyLoader — Aggiunge lazy loading nativo e gestione LCP.
 *
 * Logica:
 * - La PRIMA immagine "grande" nel contenuto riceve fetchpriority="high"
 *   e NON riceve loading="lazy" (è l'elemento LCP candidato)
 * - Tutte le altre immagini ricevono loading="lazy" + decoding="async"
 * - Tutti gli iframe ricevono loading="lazy"
 * - Immagini con width < 50px (icone/tracking pixel) vengono ignorate
 * - Tag che hanno già l'attributo loading vengono lasciati invariati
 *
 * Funziona come post-processore sull'output HTML finale (onAfterRender).
 */

namespace Agency56k\Template\Html56k\Site\Performance;

defined('_JEXEC') or die;

class LazyLoader
{
    /**
     * Flag: indica se l'immagine LCP è già stata trovata nella pagina corrente.
     *
     * @var bool
     */
    private bool $lcpFound = false;

    /**
     * Processa l'HTML completo e aggiunge gli attributi di lazy loading.
     *
     * @param   string  $html  L'output HTML completo della pagina
     *
     * @return  string  HTML modificato con attributi lazy/LCP
     */
    public function process(string $html): string
    {
        $this->lcpFound = false;

        // Processa <img>
        $html = preg_replace_callback(
            '/<img\s+([^>]*?)>/i',
            function ($matches) {
                return $this->processImg($matches[0], $matches[1]);
            },
            $html
        );

        // Processa <iframe> (YouTube embed, Google Maps, ecc.)
        $html = preg_replace_callback(
            '/<iframe\s+([^>]*?)>/i',
            function ($matches) {
                return $this->processIframe($matches[0], $matches[1]);
            },
            $html
        );

        return $html;
    }

    /**
     * Processa un singolo tag <img>.
     *
     * @param   string  $fullTag  Il tag HTML completo
     * @param   string  $attrs    La stringa degli attributi
     *
     * @return  string  Tag modificato
     */
    private function processImg(string $fullTag, string $attrs): string
    {
        // Non toccare img che hanno già loading impostato manualmente
        if (preg_match('/\bloading\s*=/i', $attrs)) {
            return $fullTag;
        }

        // Non toccare SVG inline (data:image/svg)
        if (preg_match('/src\s*=\s*["\']data:image\/svg/i', $attrs)) {
            return $fullTag;
        }

        // Ignora immagini piccole (icone < 50px, tracking pixel 1x1)
        if (preg_match('/\bwidth\s*=\s*["\']?(\d+)/i', $attrs, $w)) {
            if ((int) $w[1] < 50) {
                return $fullTag;
            }
        }

        // Ignora immagini con data-no-lazy
        if (preg_match('/data-no-lazy/i', $attrs)) {
            return $fullTag;
        }

        // La prima immagine "grande" è il candidato LCP
        if (!$this->lcpFound) {
            $this->lcpFound = true;

            // LCP: NO lazy, SÌ priorità alta
            $fullTag = $this->addAttribute($fullTag, 'fetchpriority', 'high');
            $fullTag = $this->addAttribute($fullTag, 'decoding', 'async');

            return $fullTag;
        }

        // Tutte le altre: lazy loading
        $fullTag = $this->addAttribute($fullTag, 'loading', 'lazy');
        $fullTag = $this->addAttribute($fullTag, 'decoding', 'async');

        return $fullTag;
    }

    /**
     * Processa un singolo tag <iframe>.
     *
     * @param   string  $fullTag  Il tag HTML completo
     * @param   string  $attrs    La stringa degli attributi
     *
     * @return  string  Tag modificato
     */
    private function processIframe(string $fullTag, string $attrs): string
    {
        // Non toccare iframe che hanno già loading impostato
        if (preg_match('/\bloading\s*=/i', $attrs)) {
            return $fullTag;
        }

        return $this->addAttribute($fullTag, 'loading', 'lazy');
    }

    /**
     * Aggiunge un attributo HTML a un tag se non già presente.
     *
     * @param   string  $tag    Il tag HTML completo
     * @param   string  $name   Nome dell'attributo
     * @param   string  $value  Valore dell'attributo
     *
     * @return  string  Tag con attributo aggiunto
     */
    private function addAttribute(string $tag, string $name, string $value): string
    {
        // Se già presente, non duplicare
        if (preg_match('/\b' . preg_quote($name, '/') . '\s*=/i', $tag)) {
            return $tag;
        }

        // Inserisci prima della chiusura > o />
        return preg_replace('/\/?>$/', ' ' . $name . '="' . $value . '"$0', $tag);
    }
}
