<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 *
 * PerformanceHelper — Orchestratore dei moduli di performance.
 *
 * Registra un callback sull'evento onAfterRender di Joomla per
 * processare l'HTML finale con i vari ottimizzatori.
 *
 * Pipeline attiva:
 * 1. Critical CSS → inline CSS critico + async CSS completo
 * 2. Lazy Loading → loading="lazy" + fetchpriority="high" per LCP
 */

namespace Agency56k\Template\Html56k\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Agency56k\Template\Html56k\Site\Performance\LazyLoader;
use Agency56k\Template\Html56k\Site\CriticalCss\CriticalCssExtractor;

class PerformanceHelper
{
    /**
     * Registra il post-processore HTML sull'evento onAfterRender.
     *
     * @param   \Joomla\Registry\Registry  $params  I parametri del template
     *
     * @return  void
     */
    public static function register($params): void
    {
        $app = Factory::getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $app->registerEvent('onAfterRender', function () use ($params) {
            self::processOutput($params);
        });
    }

    /**
     * Processa l'output HTML finale applicando i moduli di performance attivi.
     *
     * L'ordine è importante:
     * 1. Critical CSS → Deve agire PRIMA del lazy loading perché modifica i <link>
     * 2. Lazy Loading → Aggiunge attributi alle <img> e <iframe>
     *
     * @param   \Joomla\Registry\Registry  $params  I parametri del template
     *
     * @return  void
     */
    private static function processOutput($params): void
    {
        $app  = Factory::getApplication();
        $html = $app->getBody();

        if (empty($html)) {
            return;
        }

        // Modulo 1: Critical CSS Engine
        if ((int) $params->get('critical_css', 0) === 1) {
            $cachePath     = JPATH_THEMES . '/html56k/cache/critical-css';
            $cacheLifetime = (int) $params->get('critical_css_lifetime', 86400);
            $pageType      = self::detectPageType();

            $extractor = new CriticalCssExtractor($cachePath, $cacheLifetime);
            $html      = $extractor->process($html, $pageType);
        }

        // Modulo 8: Lazy Loading Intelligente
        if ((int) $params->get('lazy_loading', 1) === 1) {
            $lazyLoader = new LazyLoader();
            $html       = $lazyLoader->process($html);
        }

        // [Futuro] Modulo 5: Resource Hints
        // [Futuro] Modulo 6: HTML Minification

        $app->setBody($html);
    }

    /**
     * Rileva il tipo di pagina Joomla corrente.
     *
     * Usato per generare chiavi di cache specifiche per tipo pagina
     * nel Critical CSS Engine.
     *
     * @return  string  Tipo di pagina: 'homepage', 'article', 'category-blog', 'category-list', 'default'
     */
    private static function detectPageType(): string
    {
        $app    = Factory::getApplication();
        $option = $app->input->get('option', '');
        $view   = $app->input->get('view', '');
        $layout = $app->input->get('layout', '');

        // Homepage
        $menu        = $app->getMenu();
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

        // Generico
        return 'default';
    }
}
