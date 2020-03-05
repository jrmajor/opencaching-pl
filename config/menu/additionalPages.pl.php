<?php

use src\Controllers\CacheAdoptionController;
use src\Controllers\CacheController;
use src\Controllers\CacheLogController;
use src\Utils\Uri\SimpleRouter;

/**
 * This is simple configuration of links presented in sidebar of the page
 * for authorized users only.
 *
 * This is the configuration for OCPL node only.
 *
 * If you want to customize footer for your node
 * create config for your node by copied this file and changing its name.
 *
 * Every record of $menu table should be table record in form:
 *  '<translation-key-used-as-link-text>' => '<url>',
 *
 * or if link needs to be open in a new window (use php array)
 *  '<translation-key-used-as-link-text>' => ['<url>'],
 *
 */

$menu = [ // DON'T CHANGE $menu var name!
    /* 'translation key' => 'url' */
    'mnu_cacheAdoption' => SimpleRouter::getLink(CacheAdoptionController::class),
    'mnu_searchUser' => '/searchuser.php',

    'mnu_newCaches' => SimpleRouter::getLink(CacheController::class, 'newCaches'),
    'mnu_newLogs' => SimpleRouter::getLink(CacheLogController::class, 'lastLogsList'),
    'mnu_incommingEvents' => SimpleRouter::getLink(CacheController::class, 'incomingEvents'),
    'mnu_recoCaches' => SimpleRouter::getLink(CacheController::class, 'recommended'),

    'mnu_FloppMap' => 'https://flopp-caching.de',
    'mnu_massLogsSave' => '/log_cache_multi_send.php',
    'mnu_openchecker' => '/openchecker.php',
    'mnu_download' => [$links['wiki']['downloads']],
    'mnu_qrCode' => SimpleRouter::getLink('UserUtils', 'qrCodeGen'),
];
