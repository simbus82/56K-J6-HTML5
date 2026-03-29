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
 */

namespace Agency56k\Template\Html56k\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Agency56k\Template\Html56k\Site\Performance\LazyLoader;

class PerformanceHelper
{
    /**
     * Registra il post-processore HTML sull'evento onAfterRender.
     *
     * Deve essere chiamato dall'index.php del template.
     * I moduli verranno eseguiti dopo che Joomla ha assemblato
     * l'intero output HTML della pagina.
     *
     * @param   \Joomla\Registry\Registry  $params  I parametri del template
     *
     * @return  void
     */
    public static function register($params): void
    {
        $app = Factory::getApplication();

        // Solo frontend
        if (!$app->isClient('site')) {
            return;
        }

        // Registra il listener sull'evento onAfterRender
        $app->registerEvent('onAfterRender', function () use ($params) {
            self::processOutput($params);
        });
    }

    /**
     * Processa l'output HTML finale applicando i moduli di performance attivi.
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

        // Modulo 8: Lazy Loading Intelligente
        if ((int) $params->get('lazy_loading', 1) === 1) {
            $lazyLoader = new LazyLoader();
            $html = $lazyLoader->process($html);
        }

        // [Futuro] Modulo 1: Critical CSS Engine
        // if ((int) $params->get('critical_css', 0) === 1) { ... }

        // [Futuro] Modulo 5: Resource Hints
        // if ((int) $params->get('resource_hints', 0) === 1) { ... }

        // [Futuro] Modulo 6: HTML Minification
        // if ((int) $params->get('html_minify', 0) === 1) { ... }

        $app->setBody($html);
    }
}
