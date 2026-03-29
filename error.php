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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

/** @var \Joomla\CMS\Document\ErrorDocument $this */

http_response_code(404);

$app            = Factory::getApplication();
$config         = Factory::getConfig();
$langTag        = Factory::getLanguage()->getTag();
$params         = $this->params;
$notfound_alias = $params->get('notfound_alias');

$langUrl = substr($langTag, 0, -3);
$file    = Uri::root() . $langUrl . '/' . $notfound_alias;

$arrContextOpt = [
    "ssl" => [
        "verify_peer"      => false,
        "verify_peer_name" => false
    ]
];

if ($config->get('offline') == 1) {
    echo "Website is in Offline Mode: custom error 404 page works only in Online Mode";
} else {
    if (empty($notfound_alias)) {
        echo "Error 404 - Page Not Found";
    } else {
        $file_headers = @get_headers($file);
        if ($file_headers && (strpos($file_headers[0], '404') !== false || strpos($file_headers[0], '508') !== false)) {
            echo "Error 404 - Page defined in settings not found";
        } else {
            $content = @file_get_contents($file, false, stream_context_create($arrContextOpt));
            if ($content) {
                echo $content;
            } else {
                echo "Error 404 - Page Not Found";
            }
        }
    }
}
?>
