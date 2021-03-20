<?php

require_once __DIR__ . '/ClassPathDictionary.php'; // class autoloader

use src\Models\OcConfig\OcConfig;
use src\Models\User\UserAuthorization;
use src\Utils\Debug\ErrorHandler;
use src\Utils\I18n\I18n;
use src\Utils\View\View;

ErrorHandler::install();

session_start();

ob_start();

require_once(__DIR__ . '/settingsGlue.inc.php');

// TODO: kojoty: it should be removed after config refactoring
// now if common.inc.php is not loaded in global context settings are not accessible
$GLOBALS['config'] = $config;

require_once(__DIR__ . '/common_tpl_funcs.php'); // template engine

// yepp, we will use UTF-8
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
mb_language('uni');

if (php_sapi_name() != 'cli') { // this is not neccesarry for command-line scripts...
    //detecting errors
    //TODO: this is never set and should be removed but it needs to touch hungreds of files...
    $error = false;

    UserAuthorization::verify();

    initTemplateSystem();
    I18n::init();
}

function initTemplateSystem(){
    // create global view variable (used in templates)
    // TODO: it should be moved to context..
    if (! isset($GLOBALS['view'])) {
        $GLOBALS['view'] = new View();
    }

    //by default, use start template
    if (! isset($GLOBALS['tplname'])){
        $GLOBALS['tplname'] = 'start';
    }

    // load vars from settings...
    tpl_set_var('site_name', OcConfig::getSiteName());
    tpl_set_var('contact_mail', OcConfig::getEmailAddrOcTeam(true));

    // set wikiLinks used in translations
    foreach(OcConfig::getWikiLinks() as $key => $value){
        tpl_set_var('wiki_link_' . $key, $value);
    }

    tpl_set_var('title', htmlspecialchars(OcConfig::getSitePageTitle(), ENT_COMPAT, 'UTF-8'));
    tpl_set_var('bodyMod', '');
    tpl_set_var('cachemap_header', '');
    tpl_set_var('htmlheaders', '');

    $GLOBALS['tpl_subtitle'] = '';
}
