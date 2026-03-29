<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 *
 * DomMatcher — Match selettori CSS contro il DOM HTML.
 *
 * Converte selettori CSS semplificati in XPath e verifica
 * se corrispondono a elementi presenti nel DOMDocument.
 * Copre i pattern più comuni (tag, classe, ID, attributi, combinazioni).
 */

namespace Agency56k\Template\Html56k\Site\CriticalCss;

defined('_JEXEC') or die;

class DomMatcher
{
    /**
     * Selettori CSS considerati SEMPRE above-the-fold (critici).
     */
    private const CRITICAL_PATTERNS = [
        'header', 'nav', '.navbar', '.hero', '.banner',
        '[data-critical]', '#header', '#nav', '#hero',
        'body', 'html', ':root',
    ];

    /**
     * Selettori CSS considerati MAI above-the-fold (esclusi).
     */
    private const EXCLUDE_PATTERNS = [
        'footer', '#footer', '.footer',
        '.sidebar', '#sidebar',
        '.comments', '.related-articles', '.pagination',
    ];

    /**
     * Verifica se un selettore CSS è "critico" (above the fold).
     *
     * Logica a 3 livelli:
     * 1. Escludi se matcha un pattern "mai critico"
     * 2. Includi se matcha un pattern "sempre critico"
     * 3. Prova a matchare contro il DOM — se l'elemento esiste, è critico
     *
     * @param   string      $selector  Il selettore CSS
     * @param   \DOMXPath   $xpath     L'oggetto XPath del documento
     *
     * @return  bool
     */
    public static function isCritical(string $selector, \DOMXPath $xpath): bool
    {
        // Rimuovi pseudo-classi e pseudo-elementi per il matching base
        $baseSelector = preg_replace('/::?[\w-]+(\(.*?\))?/', '', $selector);
        $baseSelector = trim($baseSelector);

        if (empty($baseSelector) || $baseSelector === '*') {
            return true; // Selettori universali sempre critici
        }

        // 1. Escludi pattern "mai critici"
        foreach (self::EXCLUDE_PATTERNS as $pattern) {
            if (stripos($baseSelector, $pattern) !== false) {
                return false;
            }
        }

        // 2. Includi pattern "sempre critici"
        foreach (self::CRITICAL_PATTERNS as $pattern) {
            if (stripos($baseSelector, $pattern) !== false) {
                return true;
            }
        }

        // 3. Prova il match sul DOM con conversione CSS → XPath
        try {
            $xpathQuery = self::cssToXpath($baseSelector);
            if ($xpathQuery !== null) {
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
     * Conversione CSS selector → XPath (semplificata).
     *
     * Copre i casi più comuni:
     *   .class, #id, tag, tag.class, tag#id, [attr], tag[attr]
     *
     * @param   string  $cssSelector  Il selettore CSS
     *
     * @return  string|null  La query XPath equivalente, o null se non convertibile
     */
    public static function cssToXpath(string $cssSelector): ?string
    {
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

        // Tag con ID: tag#id
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)#([a-zA-Z0-9_-]+)$/', $selector, $m)) {
            return "//{$m[1]}[@id='{$m[2]}']";
        }

        // Attributo: [data-something]
        if (preg_match('/^\[([a-zA-Z0-9_-]+)\]$/', $selector, $m)) {
            return "//*[@{$m[1]}]";
        }

        // Attributo con valore: [attr="value"]
        if (preg_match('/^\[([a-zA-Z0-9_-]+)=["\']([^"\']*)["\']?\]$/', $selector, $m)) {
            return "//*[@{$m[1]}='{$m[2]}']";
        }

        // Discendente semplice: parent child (con spazio)
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\s+([a-zA-Z][a-zA-Z0-9]*)$/', $selector, $m)) {
            return "//{$m[1]}//{$m[2]}";
        }

        // Figlio diretto: parent > child
        if (preg_match('/^([a-zA-Z][a-zA-Z0-9]*)\s*>\s*([a-zA-Z][a-zA-Z0-9]*)$/', $selector, $m)) {
            return "//{$m[1]}/{$m[2]}";
        }

        // Per selettori più complessi, restituisci null (il chiamante includerà per sicurezza)
        return null;
    }
}
