<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 *
 * CssParser — Parser CSS leggero senza dipendenze esterne.
 *
 * Parsa un file CSS e restituisce un array strutturato di regole.
 * Copre i casi più comuni: selettori, regole normali, @media, @font-face, @keyframes.
 * Non richiede sabberworm/php-css-parser né altre librerie Composer.
 */

namespace Agency56k\Template\Html56k\Site\CriticalCss;

defined('_JEXEC') or die;

class CssParser
{
    /**
     * Parsa una stringa CSS e restituisce un array di regole strutturate.
     *
     * Ogni regola è un array con:
     *   'type'      => 'rule' | 'media' | 'font-face' | 'keyframes' | 'other'
     *   'selectors' => array di selettori (solo per type=rule)
     *   'content'   => il contenuto CSS della regola (declarations)
     *   'raw'       => la regola CSS completa come stringa
     *   'media'     => la condizione @media (solo per type=media)
     *   'rules'     => sotto-regole (solo per type=media)
     *
     * @param   string  $css  Il contenuto CSS da parsare
     *
     * @return  array  Array di regole
     */
    public static function parse(string $css): array
    {
        // Rimuovi commenti CSS
        $css = preg_replace('!/\*.*?\*/!s', '', $css);

        // Normalizza spazi
        $css = preg_replace('/\s+/', ' ', $css);
        $css = trim($css);

        $rules = [];
        $pos   = 0;
        $len   = strlen($css);

        while ($pos < $len) {
            // Salta spazi
            while ($pos < $len && ctype_space($css[$pos])) {
                $pos++;
            }

            if ($pos >= $len) {
                break;
            }

            // @media query
            if (substr($css, $pos, 6) === '@media') {
                $rule = self::parseAtMedia($css, $pos);
                if ($rule) {
                    $rules[] = $rule;
                }
                continue;
            }

            // @font-face
            if (substr($css, $pos, 10) === '@font-face') {
                $rule = self::parseAtBlock($css, $pos, 'font-face');
                if ($rule) {
                    $rules[] = $rule;
                }
                continue;
            }

            // @keyframes
            if (preg_match('/^@(?:-webkit-)?keyframes/i', substr($css, $pos, 25))) {
                $rule = self::parseAtBlock($css, $pos, 'keyframes');
                if ($rule) {
                    $rules[] = $rule;
                }
                continue;
            }

            // Altre @ rules (@charset, @import, ecc.)
            if ($css[$pos] === '@') {
                $rule = self::parseAtBlock($css, $pos, 'other');
                if ($rule) {
                    $rules[] = $rule;
                }
                continue;
            }

            // Regola normale: selettore { dichiarazioni }
            $rule = self::parseRule($css, $pos);
            if ($rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Parsa una regola CSS normale (selettore + dichiarazioni).
     */
    private static function parseRule(string $css, int &$pos): ?array
    {
        $len = strlen($css);

        // Trova la posizione della {
        $bracePos = strpos($css, '{', $pos);
        if ($bracePos === false) {
            $pos = $len;
            return null;
        }

        $selectorStr = trim(substr($css, $pos, $bracePos - $pos));

        // Trova la } corrispondente
        $endPos = self::findClosingBrace($css, $bracePos);
        if ($endPos === false) {
            $pos = $len;
            return null;
        }

        $content = substr($css, $bracePos + 1, $endPos - $bracePos - 1);
        $raw     = $selectorStr . '{' . $content . '}';

        // Splitta selettori multipli (separati da virgola)
        $selectors = array_map('trim', explode(',', $selectorStr));
        $selectors = array_filter($selectors, function ($s) {
            return !empty($s);
        });

        $pos = $endPos + 1;

        return [
            'type'      => 'rule',
            'selectors' => array_values($selectors),
            'content'   => trim($content),
            'raw'       => $raw,
        ];
    }

    /**
     * Parsa un blocco @media e le sue sotto-regole.
     */
    private static function parseAtMedia(string $css, int &$pos): ?array
    {
        $len = strlen($css);

        // Trova la { del blocco media
        $bracePos = strpos($css, '{', $pos);
        if ($bracePos === false) {
            $pos = $len;
            return null;
        }

        $mediaCondition = trim(substr($css, $pos + 6, $bracePos - $pos - 6));

        // Trova la } corrispondente
        $endPos = self::findClosingBrace($css, $bracePos);
        if ($endPos === false) {
            $pos = $len;
            return null;
        }

        $innerCss = substr($css, $bracePos + 1, $endPos - $bracePos - 1);

        // Parsa le regole all'interno del @media (ricorsivo)
        $innerRules = self::parse($innerCss);

        $raw = '@media ' . $mediaCondition . '{' . $innerCss . '}';

        $pos = $endPos + 1;

        return [
            'type'  => 'media',
            'media' => $mediaCondition,
            'rules' => $innerRules,
            'raw'   => $raw,
        ];
    }

    /**
     * Parsa un blocco @at-rule generico (@font-face, @keyframes, ecc.).
     */
    private static function parseAtBlock(string $css, int &$pos, string $type): ?array
    {
        $len = strlen($css);

        $bracePos = strpos($css, '{', $pos);
        if ($bracePos === false) {
            // At-rule senza blocco (es. @import, @charset)
            $semicolonPos = strpos($css, ';', $pos);
            if ($semicolonPos !== false) {
                $raw = trim(substr($css, $pos, $semicolonPos - $pos + 1));
                $pos = $semicolonPos + 1;
                return [
                    'type' => $type,
                    'raw'  => $raw,
                ];
            }
            $pos = $len;
            return null;
        }

        $endPos = self::findClosingBrace($css, $bracePos);
        if ($endPos === false) {
            $pos = $len;
            return null;
        }

        $raw = trim(substr($css, $pos, $endPos - $pos + 1));
        $pos = $endPos + 1;

        return [
            'type' => $type,
            'raw'  => $raw,
        ];
    }

    /**
     * Trova la parentesi graffa di chiusura corrispondente,
     * gestendo la nidificazione.
     */
    private static function findClosingBrace(string $css, int $openPos): int|false
    {
        $len   = strlen($css);
        $depth = 1;
        $pos   = $openPos + 1;

        while ($pos < $len && $depth > 0) {
            if ($css[$pos] === '{') {
                $depth++;
            } elseif ($css[$pos] === '}') {
                $depth--;
            }
            if ($depth > 0) {
                $pos++;
            }
        }

        return $depth === 0 ? $pos : false;
    }

    /**
     * Minifica una stringa CSS.
     *
     * @param   string  $css  CSS da minificare
     *
     * @return  string  CSS minificato
     */
    public static function minify(string $css): string
    {
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }
}
