<?php

use src\Libs\JpGraph\JpGraphLoader;
use src\Utils\Database\XDb;

require __DIR__ . '/../lib/common.inc.php';

// jpgraph package doesn't contains fonts
define('TTF_DIR', __DIR__ . '/../resources/fonts/');

JpGraphLoader::load();
JpGraphLoader::module('bar');
JpGraphLoader::module('date');

$year = '';
// check for old-style parameters
if (isset($_REQUEST['userid'], $_REQUEST['t'])) {
    $user_id = $_REQUEST['userid'];
    $titles = $_REQUEST['t'];

    if (strlen($titles) > 3) {
        $year = substr($titles, -4);
        $tit = substr($titles, 0, -4);
    } else {
        $tit = $titles;
    }
}

$y = [];
$x = [];

if ($tit == 'ccy') {
    $rsCreateCachesYear = XDb::xSql(
        'SELECT COUNT(*) `count`,YEAR(`date_created`) `year` FROM `caches`
        WHERE status <> 4 AND status <> 5 AND status <> 6 AND user_id= ?
        GROUP BY YEAR(`date_created`)
        ORDER BY YEAR(`date_created`) ASC', $user_id);

    if ($rsCreateCachesYear !== false) {
        $descibe = tr('annual_stat_created');
        $xtitle = '';

        while ($ry = XDb::xFetchArray($rsCreateCachesYear)) {
            $y[] = $ry['count'];
            $x[] = $ry['year'];
        }
    }
    XDb::xFreeResults($rsCreateCachesYear);
}

if ($tit == 'ccm') {
    for ($i = 1; $i < 13; $i++) {
        $month = $i;
        $rsCreateCachesMonth = XDb::xSql(
            'SELECT COUNT(*) `count`, MONTH(`date_created`) `month`, YEAR(`date_created`) `year` FROM `caches`
            WHERE status <> 4 AND status <> 5 AND status <> 6 AND user_id= ? AND YEAR(`date_created`)= ? AND MONTH(`date_created`)= ?
            GROUP BY MONTH(`date_created`), YEAR(`date_created`)
            ORDER BY YEAR(`date_created`) ASC, MONTH(`date_created`) ASC',
            $user_id, $year, $month);

        $descibe = tr('monthly_stat_created_user');
        $xtitle = $year;
        $rm = XDb::xFetchArray($rsCreateCachesMonth);

        if ($rm !== false) {
            $x[] = $rm['month'];
            $y[] = $rm['count'];
        } else {
            $x[] = $i;
            $y[] = 0;
        }
    }

    XDb::xFreeResults($rsCreateCachesMonth);
}

if ($tit == 'cfy') {
    $rsCachesFindYear = XDb::xSql(
        "SELECT COUNT(*) `count`, YEAR(`date`) `year` FROM `cache_logs`
        WHERE type=1 AND cache_logs.deleted='0' AND user_id= ?
        GROUP BY YEAR(`date`)
        ORDER BY YEAR(`date`) ASC", $user_id);

    if ($rsCachesFindYear !== false) {
        $descibe = tr('annual_stat_founds_user');
        $xtitle = '';

        while ($rfy = XDb::xFetchArray($rsCachesFindYear)) {
            $y[] = $rfy['count'];
            $x[] = $rfy['year'];
        }
    }
    XDb::xFreeResults($rsCachesFindYear);
}

if ($tit == 'cfm') {
    for ($i = 1; $i < 13; $i++) {
        $month = $i;
        $rsCachesFindMonth = XDb::xSql(
            "SELECT COUNT(*) `count`, YEAR(`date`) `year`, MONTH(`date`) `month` FROM `cache_logs`
            WHERE type=1 AND cache_logs.deleted='0' AND user_id=? AND YEAR(`date`)=? AND MONTH(`date`)=?
            GROUP BY MONTH(`date`), YEAR(`date`)
            ORDER BY YEAR(`date`) ASC, MONTH(`date`) ASC",
            $user_id, $year, $month);

        $descibe = tr('monthly_stat_founds_user');
        $xtitle = $year;

        $rfm = XDb::xFetchArray($rsCachesFindMonth);

        if ($rfm !== false) {
            $x[] = $rfm['month'];
            $y[] = $rfm['count'];
        } else {
            $x[] = $i;
            $y[] = 0;
        }
    }

    XDb::xFreeResults($rsCachesFindMonth);
}

// Create the graph. These two calls are always required
$graph = new Graph(500, 200, 'auto');
$graph->SetScale('textint', 0, max($y) + (max($y) * 0.2), 0, 0);
// Add a drop shadow
$graph->SetShadow();

// Adjust the margin a bit to make more room for titles
$graph->SetMargin(50, 30, 30, 40);

// Create a bar pot
$bplot = new BarPlot($y);

// Adjust fill color
$bplot->SetFillColor('steelblue2');
$graph->Add($bplot);

// Setup the titles
$graph->title->Set($descibe);
$graph->xaxis->title->Set($xtitle);
$graph->xaxis->SetTickLabels($x);

// Some extra margin looks nicer
$nc = tr('number_caches');
$graph->yaxis->title->Set($nc);

$graph->title->SetFont(FF_ARIAL, FS_NORMAL);
$graph->yaxis->title->SetFont(FF_FONT1, FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1, FS_BOLD);

// Setup the values that are displayed on top of each bar
$bplot->value->Show();

// Must use TTF fonts if we want text at an arbitrary angle
$bplot->value->SetFont(FF_FONT1, FS_BOLD);
$bplot->value->SetAngle(0);
$bplot->value->SetFormat('%d');

// Display the graph
$graph->Stroke();
