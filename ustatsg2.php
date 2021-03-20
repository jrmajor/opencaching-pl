<?php

use src\Models\ApplicationContainer;
use src\Models\OcConfig\OcConfig;
use src\Utils\Database\XDb;

require_once(__DIR__ . '/lib/common.inc.php');

//user logged in?
if (! ApplicationContainer::GetAuthorizedUser()) {
    $target = urlencode(tpl_get_current_page());
    tpl_redirect('login.php?target=' . $target);

    exit;
}

// check for old-style parameters
if (isset($_REQUEST['userid'])) {
    $user_id = $_REQUEST['userid'];
}

/** @var View */
$view->setTemplate('ustat');
$view->setVar('userId', $user_id);
$view->setVar('displayCreatedStats', true);

$content = '';

$rsGeneralStat = XDb::xSql(
    'SELECT  hidden_count, founds_count, log_notes_count, notfounds_count, username
    FROM `user` WHERE user_id= ? LIMIT 1', $user_id);

$user_record = XDb::xFetchArray($rsGeneralStat);
tpl_set_var('username', htmlspecialchars($user_record['username']));

if ($user_record['founds_count'] == 0) {
    $content .= '<p>&nbsp;</p><p>&nbsp;</p><div class="content2-container bg-blue02"><p class="content-title-noshade-size1">&nbsp;<img src="/images/blue/logs.png" class="icon32" alt="Caches Find" title="Caches Find" />&nbsp;&nbsp;&nbsp;' . tr('graph_find') . '</p></div><br /><br /><p> <b>' . tr('there_is_no_caches_found') . '</b></p>';
} else {
    // calculate diif days between date of register on OC  to current date
    $ddays = XDb::xMultiVariableQueryValue(
        'SELECT TO_DAYS( NOW() ) - TO_DAYS(`date_created`) `diff` FROM `user` WHERE user_id= :1 LIMIT 1 ', 0, $user_id);

    $rsGeneralStat = XDb::xSql(
        'SELECT YEAR(`date_created`) usertime,hidden_count, founds_count, log_notes_count, username
        FROM `user` WHERE user_id= ? LIMIT 1', $user_id);

    if ($rsGeneralStat !== false) {
        $user_record = XDb::xFetchArray($rsGeneralStat);
        XDb::xFreeResults($rsGeneralStat);

        tpl_set_var('username', htmlspecialchars($user_record['username']));
    }
    $content .= '<p>&nbsp;</p><p>&nbsp;</p><div class="content2-container bg-blue02"><p class="content-title-noshade-size1">&nbsp;<img src="/images/blue/logs.png" class="icon32" alt="Caches Find" title="Caches Find" />&nbsp;&nbsp;&nbsp;' . tr('graph_find') . '</p></div><br />';
    $content .= '<p><img src="graphs/PieGraphustat.php?userid=' . $user_id . '&amp;t=cf"  border="0" alt="" width="500" height="300" /></p>';

    $year = date('Y');

    $content .= '<p><img src="graphs/BarGraphustat.php?userid=' . $user_id . '&amp;t=cfm' . $year . '"  border="0" alt="" width="500" height="200" /></p>';

    if ($user_record['usertime'] != $year) {
        $yearr = $year - 1;
        $content .= '<p><img src="graphs/BarGraphustat.php?userid=' . $user_id . '&amp;t=cfm' . $yearr . '"  border="0" alt="" width="500" height="200" /></p>';
    }

    $content .= '<p><img src="graphs/BarGraphustat.php?userid=' . $user_id . '&amp;t=cfy"  border="0" alt="" width="500" height="200" /></p>';
}

// Parameter
$jpeg_qualitaet = 80;
$fontfile = './resources/fonts/arial.ttf';
$tplpath = 'images/PLmapa.gif';
$im = imagecreatefromgif($tplpath);
$clrWhite = imagecolorallocate($im, 255, 255, 255);
$clrBorder = imagecolorallocate($im, 70, 70, 70);
$clrBlack = imagecolorallocate($im, 0, 0, 0);
$clrRed = imagecolorallocate($im, 255, 0, 0);
$clrBlue = imagecolorallocate($im, 0, 0, 255);
$fontsize = 18;

$wojewodztwa = [
    'PL11' => [110, 138], // Lodzkie
    'PL12' => [155, 108], // Mazowieckie
    'PL21' => [135, 208], // Malopolskie
    'PL22' => [103, 188], // Slaskie
    'PL31' => [200, 150], // Lubelskie
    'PL32' => [180, 200], // Podkarpackie
    'PL33' => [146, 170], // Swietokrzyskie
    'PL34' => [195, 75], // Podlaskie
    'PL41' => [65, 115], // Wielkopolskie
    'PL42' => [26, 55], // Zachodniopmorskie
    'PL43' => [19, 100], // Lubuskie
    'PL51' => [35, 149], // Dolnoslaskie
    'PL52' => [78, 169], // Opolskie
    'PL61' => [90, 85], // Kujawskie
    'PL62' => [145, 50], // Warminskie
    'PL63' => [85, 43],     // Pomorskie
];
$wyniki = XDb::xSql(
    "SELECT cache_location.code3 wojewodztwo, COUNT(*) ilosc FROM cache_logs, cache_location
    WHERE cache_logs.user_id= ? AND cache_logs.type='1'
        AND cache_logs.deleted='0'
        AND cache_location.code3 IN ('PL11','PL12','PL21','PL22','PL31','PL32','PL33','PL34','PL41','PL42','PL43','PL51','PL52','PL61','PL62','PL63')
        AND cache_logs.cache_id=cache_location.cache_id
    GROUP BY cache_location.code3", $user_id);

while ($wynik = XDb::xFetchArray($wyniki)) {
    $text = $wynik['ilosc'];

    if ($text != '0')
        imagettftext($im, 14, 0, $wojewodztwa[$wynik['wojewodztwo']][0], $wojewodztwa[$wynik['wojewodztwo']][1], $clrBlack, $fontfile, $text);
}

// write output
imagejpeg($im, OcConfig::getDynFilesPath() . 'images/statpics/mapstat' . $user_id . '.jpg', $jpeg_qualitaet);
imagedestroy($im);
// generate number for refresh image
$rand = rand();
$content .= '<p style="margin-left: 125px;"><img src="/images/statpics/mapstat' . $user_id . '.jpg?rand=' . $rand . '" border="0" alt="" width="250" height="235" /></p>';

tpl_set_var('content', $content);

tpl_BuildTemplate();
