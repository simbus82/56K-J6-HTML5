<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Agency56k\Template\Html56k\Site;

defined('_JEXEC') or die;

use Agency56k\Template\Html56k\Site\Helper\ScssHelper;
use Agency56k\Template\Html56k\Site\Helper\PerformanceHelper;
use Agency56k\Template\Html56k\Site\Font\FontOptimizer;
use Agency56k\Template\Html56k\Site\Asset\AssetOptimizer;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\HtmlDocument $this */

$app             = Factory::getApplication();
$wa              = $this->getWebAssetManager();

// Output come HTML5
$this->setHtml5(true);

// Disabilita Generator
$this->setGenerator(null);

// Meta viewport
$this->setMetaData('viewport', 'width=device-width,initial-scale=1');

// Parametri template
$params = $this->params;

// Variabili attive
$option   = $app->input->getCmd('option', '');
$view     = $app->input->getCmd('view', '');
$layout   = $app->input->getCmd('layout', '');
$task     = $app->input->getCmd('task', '');
$itemid   = $app->input->getCmd('Itemid', '');
$sitename = $app->get('sitename');

// Menu attivo
$menu      = $app->getMenu()->getActive();
$pageclass = '';

if (is_object($menu)) {
    $pageclass = $menu->params->get('pageclass_sfx');
}

// Body class
$bodyclass = 'site ' . $option . ' view-' . $view
    . ($layout ? ' layout-' . $layout : ' no-layout')
    . ($task ? ' task-' . $task : ' no-task')
    . ($itemid ? ' itemid-' . $itemid : '')
    . ($pageclass ? ' ' . $pageclass : '')
    . ($this->direction === 'rtl' ? ' rtl' : '');

// Compilazione SCSS (sviluppo/produzione)
ScssHelper::compile($params);

// Font Optimizer — Preload font critici
FontOptimizer::preload($this, $params);

// Asset Pipeline — Disabilita asset Joomla non necessari
AssetOptimizer::optimize($this, $params);

// Performance Pipeline — Registra post-processore HTML (lazy loading, critical CSS, defer scripts)
PerformanceHelper::register($params);

// Caricamento Assets (Joomla 6 WAM)
$version = ($params->get('mode') == 1) ? time() : '6.3.0';
$wa->useStyle('template.fonts');
$wa->useStyle('template.main')->setAssetVersion($version);
$wa->useStyle('template.responsive');
$wa->useScript('template.js');
$wa->useScript('template.plugins');
$wa->useScript('template.custom');

// Calcolo larghezze (BS5)
$leftspan = 'col-md-3';
$rightspan = 'col-md-3';
$contentwidth = 'col-md-12';

if ($this->countModules('left') && $this->countModules('right')) {
    $contentwidth = 'col-md-6';
} elseif ($this->countModules('left') || $this->countModules('right')) {
    $contentwidth = 'col-md-9';
}

?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
    <?php if ($params->get('after_head_open')) : ?>
        <?php echo $params->get('after_head_open'); ?>
    <?php endif; ?>
    
    <?php if ($params->get('favicon_code')) : ?>
        <?php echo $params->get('favicon_code'); ?>
    <?php endif; ?>

    <jdoc:include type="head" />
    
    <?php if ($this->countModules('head')) : ?>
        <jdoc:include type="modules" name="head" style="raw" />
    <?php endif; ?>

    <?php if ($params->get('before_head_close')) : ?>
        <?php echo $params->get('before_head_close'); ?>
    <?php endif; ?>
</head>
<body class="<?php echo $bodyclass; ?>">
    <?php if ($params->get('after_body_open')) : ?>
        <?php echo $params->get('after_body_open'); ?>
    <?php endif; ?>

    <?php if ($params->get('browserupgrade') == '1') : ?>
        <?php if (file_exists(__DIR__ . '/includes/browserupgrade.php')) : ?>
            <?php include __DIR__ . '/includes/browserupgrade.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php include __DIR__ . '/template.php'; ?>

    <?php if ($params->get('before_body_close')) : ?>
        <?php echo $params->get('before_body_close'); ?>
    <?php endif; ?>

    <jdoc:include type="modules" name="debug" style="none" />
</body>
</html>

