<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/** @var \Joomla\CMS\Document\HtmlDocument $this */
/** @var \Joomla\CMS\Application\SiteApplication $app */
/** @var \Joomla\Registry\Registry $params */
/** @var string $leftspan */
/** @var string $rightspan */
/** @var string $contentwidth */
?>
<section class="header">
    <header class="container">
        <div id="header" class="row">
            <?php if ($this->countModules('top')) : ?>
                <div id="top" class="col-12">
                    <jdoc:include type="modules" name="top" style="html5" />
                </div>
            <?php endif; ?>
            
            <?php if ($this->countModules('logo')) : ?>
                <div id="logo" class="col-md-4 text-start">
                    <jdoc:include type="modules" name="logo" style="html5" />
                </div>
            <?php endif; ?>
            
            <?php if ($this->countModules('payoff')) : ?>
                <div id="payoff" class="col-md-8 text-end">
                    <jdoc:include type="modules" name="payoff" style="html5" />
                </div>
            <?php endif; ?>
            
            <nav class="col-12">
                <div id="menu">
                    <jdoc:include type="modules" name="menu" style="html5" />
                </div>
            </nav>
        </div>
    </header>
</section>

<?php if ($this->countModules('section-top')) : ?>
    <section class="container my-3">
        <div id="section-top" class="row">
            <jdoc:include type="modules" name="section-top" style="html5" />
        </div>
    </section>
<?php endif; ?>

<section class="container my-4">
    <div id="center" class="row">
        <?php if ($this->countModules('left')) : ?>
            <aside id="sidebar-left" class="<?php echo $leftspan; ?>">
                <jdoc:include type="modules" name="left" style="html5" />
            </aside>
        <?php endif; ?>

        <main id="content" class="<?php echo $contentwidth; ?>">
            <jdoc:include type="message" />
            
            <?php if ($this->countModules('center-top')) : ?>
                <section class="row mb-3">
                    <div id="center-top" class="col-12">
                        <jdoc:include type="modules" name="center-top" style="html5" />
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($params->get('showcomponent') == 1) : ?>
                <section class="component-wrapper">
                    <jdoc:include type="component" />
                </section>
            <?php endif; ?>

            <?php if ($this->countModules('center-bottom')) : ?>
                <section class="row mt-3">
                    <div id="center-bottom" class="col-12">
                        <jdoc:include type="modules" name="center-bottom" style="html5" />
                    </div>
                </section>
            <?php endif; ?>
        </main>

        <?php if ($this->countModules('right')) : ?>
            <aside id="sidebar-right" class="<?php echo $rightspan; ?>">
                <jdoc:include type="modules" name="right" style="html5" />
            </aside>
        <?php endif; ?>
    </div>
</section>

<?php if ($this->countModules('section-bottom')) : ?>
    <section class="container my-3">
        <div id="section-bottom" class="row">
            <jdoc:include type="modules" name="section-bottom" style="html5" />
        </div>
    </section>
<?php endif; ?>

<div id="bottom" class="mt-auto py-4 bg-light">
    <footer class="container">
        <div id="footer" class="row">
            <div class="col-12">
                <jdoc:include type="modules" name="footer" style="html5" />
            </div>
        </div>
    </footer>
    
    <?php if ($this->countModules('copyright')) : ?>
        <section class="container mt-3 pt-3 border-top">
            <div id="copyright" class="row text-center">
                <div class="col-12 small">
                    <jdoc:include type="modules" name="copyright" style="html5" />
                </div>
            </div>
        </section>
    <?php endif; ?>
</div>

