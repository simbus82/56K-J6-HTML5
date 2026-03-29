<?php
/**
 * @package      Html56K
 * @subpackage   Templates.Html56k
 * @author       56K Agency
 * @copyright    Copyright (C) 2026. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Agency56k\Template\Html56k\Site\Helper;

defined('_JEXEC') or die;

use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;

/**
 * Helper class for SCSS compilation in Joomla 6.
 */
class ScssHelper
{
    /**
     * Compiles SCSS to CSS based on template settings.
     *
     * @param   \Joomla\Registry\Registry  $params  Template parameters
     *
     * @return  void
     */
    public static function compile($params)
    {
        $app  = Factory::getApplication();
        $mode = (int) $params->get('mode', 0); // 0 = Production, 1 = Development

        $templatePath = JPATH_THEMES . '/html56k';
        $scssFile     = $templatePath . '/scss/template.scss';
        $cssFile      = $templatePath . '/css/template.css';

        // In Production mode, only compile if the CSS file doesn't exist
        if ($mode === 0 && File::exists($cssFile)) {
            return;
        }

        // Ensure the SCSS file exists
        if (!File::exists($scssFile)) {
            return;
        }

        // Load the library autoloader
        $libPath = $templatePath . '/libraries/scssphp/scss.inc.php';
        if (File::exists($libPath)) {
            require_once $libPath;
        } else {
            return;
        }

        try {
            $compiler = new Compiler();
            $compiler->setImportPaths($templatePath . '/scss/');

            // Set output style based on mode
            if ($mode === 0) {
                $compiler->setOutputStyle(OutputStyle::COMPRESSED);
            } else {
                $compiler->setOutputStyle(OutputStyle::EXPANDED);
            }

            // Sync template parameters with SCSS variables
            // Example: $compiler->addVariables(['primary-color' => $params->get('primary_color', '#007bff')]);
            
            $scssContent = file_get_contents($scssFile);
            $cssContent  = $compiler->compileString($scssContent)->getCss();

            // Write the compiled CSS to the file
            if (!Folder::exists($templatePath . '/css')) {
                Folder::create($templatePath . '/css');
            }

            File::write($cssFile, $cssContent);

        } catch (\Exception $e) {
            if ($mode === 1) {
                $app->enqueueMessage('SCSS Compilation Error: ' . $e->getMessage(), 'error');
            }
        }
    }
}
