<?php

use src\Controllers\LogEntryController;
use src\Controllers\PictureController;
use src\Models\ApplicationContainer;
use src\Models\Coordinates\Coordinates;
use src\Models\OcConfig\OcConfig;
use src\Utils\Database\OcDb;
use src\Utils\DateTime\Year;
use src\Utils\Text\SmilesInText;
use src\Utils\Text\TextConverter;
use src\Utils\Text\UserInputFilter;
use src\Utils\Uri\SimpleRouter;
use src\Utils\Uri\Uri;

require_once __DIR__ . '/lib/common.inc.php';

// Set the template to process
$view = tpl_getView();
$view->loadFancyBox();
$view->setTemplate('viewlogs');

tpl_set_var('viewcache_js', Uri::getLinkWithModificationTime('/views/viewcache/viewcache.js'));

require __DIR__ . '/src/Views/lib/icons.inc.php';

require __DIR__ . '/src/Views/viewcache.inc.php';

require __DIR__ . '/src/Views/viewlogs.inc.php';

$loggedUser = ApplicationContainer::GetAuthorizedUser();

$view->setVar('isUserAuthorized', (bool) $loggedUser);

if (isset($_REQUEST['cacheid'])) {
    $cache_id = (int) $_REQUEST['cacheid'];
}

$logid = isset($_REQUEST['logid']) ? (int) $_REQUEST['logid'] : false;

if (! isset($cache_id) && $logid == false) {
    exit;
}

$view->setVar('logId', $logid);

$start = (int) ($_REQUEST['start'] ?? 0);

$count = $_REQUEST['count'] ?? 99999;

if (! is_numeric($count)) {
    $count = 999999;
                                                                            }

// Show spoilers only to authenticated users
$disable_spoiler_view = ! $loggedUser && $hide_coords;

$dbc = OcDb::instance();

if (isset($cache_id)) {
    $statement = $dbc->multiVariableQuery(
        'SELECT `user_id`, `name`, `founds`, `notfounds`, `notes`, `status`, `type`
            FROM `caches`
            WHERE `caches`.`cache_id`=:1 LIMIT 1',
        $cache_id
    );
} else {
    $statement = $dbc->multiVariableQuery(
        'SELECT `cache_logs`.`cache_id`, `caches`.`user_id`, `caches`.`name`, `caches`.`founds`,
                `caches`.`notfounds`, `caches`.`notes`, `caches`.`status`, `caches`.`type`
            FROM `caches`, `cache_logs`
            WHERE `cache_logs`.`id`=:1
                AND `caches`.`cache_id`=`cache_logs`.`cache_id`
            LIMIT 1',
        $logid
    );
}

if ($dbc->rowCount($statement) === 0) {
    exit;
}

$cache_record = $dbc->dbResultFetchOneRowOnly($statement);

$cache_id = $cache_id ?? $cache_record['cache_id'];

// If the cache is not published, only the owner can view the log.
if (
    ($cache_record['status'] == 4 || $cache_record['status'] == 5 || $cache_record['status'] == 6)
    && ($loggedUser && $cache_record['user_id'] != $loggedUser->getUserId() && ! $loggedUser->hasOcTeamRole())
) {
    exit;
}

// Detailed cache access logging
if ($enable_cache_access_logs ?? null) {
    $user_id = $loggedUser ? $loggedUser->getUserId() : null;

    $access_log = $_SESSION["CACHE_ACCESS_LOG_VL_{$user_id}"] ?? [];

    if (($access_log[$cache_id] ?? false) !== true) {
        $dbc->multiVariableQuery(
            'INSERT INTO CACHE_ACCESS_LOGS
                    (event_date, cache_id, user_id, source, event, ip_addr, user_agent, forwarded_for)
                 VALUES
                    (NOW(), :1, :2, \'B\', \'view_logs\', :3, :4, :5)',
            $cache_id,
            $user_id,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''
        );

        $access_log[$cache_id] = true;

        $_SESSION["CACHE_ACCESS_LOG_VL_{$user_id}"] = $access_log;
    }
}

// The cache is here, let's process
$owner_id = $cache_record['user_id'];

// Cache data
$cache_name = htmlspecialchars($cache_record['name'], ENT_COMPAT);
$view->setSubtitle($cache_name . ' - ');
tpl_set_var('cachename', $cache_name);
tpl_set_var('cacheid', $cache_id);
$view->setVar('cacheType', $cache_record['type']);

if ($cache_record['type'] == 6) {
    tpl_set_var('found_icon', $exist_icon);
    tpl_set_var('notfound_icon', $wattend_icon);
} else {
    tpl_set_var('found_icon', $found_icon);
    tpl_set_var('notfound_icon', $notfound_icon);
}
tpl_set_var('note_icon', $note_icon);

tpl_set_var('founds', htmlspecialchars($cache_record['founds'], ENT_COMPAT));
tpl_set_var('notfounds', htmlspecialchars($cache_record['notfounds'], ENT_COMPAT));
tpl_set_var('notes', htmlspecialchars($cache_record['notes'], ENT_COMPAT));
tpl_set_var('total_number_of_logs', htmlspecialchars($cache_record['notes'] + $cache_record['notfounds'] + $cache_record['founds'], ENT_COMPAT));

// Check number of pictures in logs
$rspiclogs = $dbc->multiVariableQueryValue('SELECT COUNT(*) FROM `pictures`,`cache_logs` WHERE `pictures`.`object_id`=`cache_logs`.`id` AND `pictures`.`object_type`=1 AND `cache_logs`.`cache_id`= :1', 0, $cache_id);

if ($rspiclogs != 0) {
    tpl_set_var('gallery', $gallery_icon . '&nbsp;' . $rspiclogs . 'x&nbsp;' . mb_ereg_replace('{cacheid}', htmlspecialchars(urlencode($cache_id), ENT_COMPAT), $gallery_link));
} else {
    tpl_set_var('gallery', '');
}

$showDel = ($_REQUEST['showdel'] ?? $_SESSION['showdel'] ?? null) === 'y';

if (($loggedUser && $loggedUser->hasOcTeamRole()) || $logid) {
    // No need to hide/show deletion icon for COG (they always see deletions) or this is a single log call.
    $showhidedel_link = '';
} else {
    $del_count = $dbc->multiVariableQueryValue(
        'SELECT count(*) number FROM `cache_logs` WHERE `deleted`=1 and `cache_id`=:1',
        0,
        $cache_id
    );

    if ($del_count == 0) {
        // Don't show deletion link
        $showhidedel_link = '';
    } else {
        /** @var string $hide_del_link Defined in viewcache.inc.php */
        /** @var string $show_del_link Defined in viewcache.inc.php */

        $showhidedel_link = $showDel ? $hide_del_link : $show_del_link;

        $showhidedel_link = str_replace('{thispage}', 'viewlogs.php', $showhidedel_link);
    }
}

tpl_set_var(
    'showhidedel_link',
    mb_ereg_replace('{cacheid}', htmlspecialchars(urlencode($cache_id), ENT_COMPAT), $showhidedel_link)
);

// Hide deletions if $showDel is false and this is single_log call and user is not COG.
$excludeDeletedLogs = ! $showDel && ! $logid && ! ($loggedUser && $loggedUser->hasOcTeamRole());

$logs = '';

$logEntryController = new LogEntryController();

if ($logid) {
    // Display one log only
    $logEneries = $logEntryController->loadLogsFromDb($cache_id, ! $excludeDeletedLogs, 0, 1, $logid);
} else {
    $logEneries = $logEntryController->loadLogsFromDb($cache_id, ! $excludeDeletedLogs, 0, 9999);
}

$logfilterConfig = OcConfig::instance()->getLogfilterConfig();

$view->setVar(
    'enableLogsFiltering',
    ! empty($logfilterConfig['enable_logs_filtering'])
);

$tmpSrcLog = file_get_contents(__DIR__ . '/src/Views/viewcache_log.tpl.php');

foreach ($logEneries as $record) {
    // Add text_listing attribute based on translation (instead of query as before)
    $record['text_listing'] = ucfirst(tr('logType' . $record['type']));

    $show_deleted = '';
    $processed_text = '';

    if ($record['deleted'] ?? false) {
        if ($loggedUser && $loggedUser->hasOcTeamRole()) {
            $show_deleted = 'show_deleted';
            $processed_text = $record['text'];
            $processed_text .= '[' . tr('vl_Record_deleted');

            if ($record['del_by_username'] ?? false) {
                $processed_text .= ' ' . tr('vl_by_user') . ' ' . $record['del_by_username'];
            }

            if (isset($record['last_deleted'])) {
                $processed_text .= ' ' . tr('vl_on_date') . ' ';

                $processed_text .= TextConverter::fixPlMonth(htmlspecialchars(strftime(
                    $GLOBALS['config']['dateformat'],
                    strtotime($record['last_deleted'])
                ), ENT_COMPAT));
            }

            $processed_text .= ']';
        } else {
            // 'Needs maintenance', 'Ready to search' and 'Temporarily unavailable'
            if (in_array($record['type'], [5, 10, 11], true)) {
                if (! $loggedUser) {
                    continue;
                }

                // hide if user is neither a geocache owner nor log author
                if ($owner_id != $loggedUser->getUserId() && $record['userid'] != $loggedUser->getUserId()) {
                    continue;
                }
            }

            // Replace the record icon with trash icon
            $record['icon_small'] = 'log/16x16-trash.png';
            $comm_replace = tr('vl_Record_of_type') . ' [' . $record['text_listing'] . '] ' . tr('vl_deleted');
            // Replace type of record
            $record['text_listing'] = tr('vl_Record_deleted');

            if ($record['del_by_username'] ?? false) {
                if ($record['del_by_admin'] == 1) { //if deleted by Admin
                    // Show username in case maker and deleter are same and entry is not COG comment
                    if ($record['del_by_username'] == $record['username'] && $record['type'] != 12) {
                        $delByCOG = false;
                    } else {
                        $comm_replace .= ' ' . tr('vl_by_COG');
                        $delByCOG = true;
                    }
                }

                if (! isset($delByCOG) || $delByCOG == false) {
                    $comm_replace .= ' ' . tr('vl_by_user') . ' ' . $record['del_by_username'];
                }
            }

            if (isset($record['last_deleted'])) {
                $comm_replace .= ' ' . tr('vl_on_date') . ' ';

                $comm_replace .= TextConverter::fixPlMonth(htmlspecialchars(strftime(
                    $GLOBALS['config']['dateformat'],
                    strtotime($record['last_deleted'])
                ), ENT_COMPAT));
            }

            $comm_replace .= '.';
            $processed_text = $comm_replace;
        }
    } else {
        $processed_text = $record['text'];
    }

    // add edit footer if record has been modified
    $record_date_create = date_create($record['date_created']);

    if ($record['edit_count'] > 0) {
        // check if edited at all
        $edit_footer = '<div><small>' . tr('vl_Recently_modified_on') . ' ';

        $edit_footer .= TextConverter::fixPlMonth(
            htmlspecialchars(strftime(
                $GLOBALS['config']['datetimeformat'],
                strtotime($record['last_modified'])
            ), ENT_COMPAT)
        ) . ' ';

        if (
            ! ($loggedUser && $loggedUser->hasOcTeamRole())
            && $record['edit_by_admin'] == true && $record['type'] == 12
        ) {
            $edit_footer .= tr('vl_by_COG');
        } else {
            $edit_footer .= tr('vl_by_user') . ' ' . $record['edit_by_username'];
        }

        // Check if record was created after implementation date (to avoid false readings
        // for records changed earlier) - actually nor in use
        if ($record_date_create > new DateTime('2005-01-01')) {
            $edit_footer .= ' - ' . tr('vl_totally_modified') . ' ' . $record['edit_count'] . ' ';

            $edit_footer .= $record['edit_count'] > 1 ? tr('vl_count_plural') : tr('vl_count_singular');
        }

        $edit_footer .= '.</small></div>';
    } else {
        $edit_footer = '';
    }

    $tmplog = $tmpSrcLog;

    // END: same code ->viewlogs.php / viewcache.php
    $tmplog_username = htmlspecialchars($record['username'], ENT_COMPAT);

    $tmplog_date = TextConverter::fixPlMonth(
        htmlspecialchars(strftime(
            $GLOBALS['config']['dateformat'],
            strtotime($record['date'])
        ), ENT_COMPAT)
    );

    $dateTimeTmpArray = explode(' ', $record['date']);
    $tmplog = mb_ereg_replace('{time}', substr($dateTimeTmpArray[1], 0, -3), $tmplog);

    // Display user activity
    if (Year::isPrimaAprilisToday() && OcConfig::isPAUserStatsRandEnabled()) {
        $tmplog_username_aktywnosc = ' (<img src="/images/blue/thunder_ico.png" alt="user activity" width="13" height="13" border="0" title="' . tr('viewlog_aktywnosc') . '"/>' . rand(1, 9) . ') ';
    } else {
        $tmplog_username_aktywnosc = ' (<img src="/images/blue/thunder_ico.png" alt="user activity" width="13" height="13" border="0" title="' . tr('viewlog_aktywnosc') . ' [' . $record['znalezione'] . '+' . $record['nieznalezione'] . '+' . $record['ukryte'] . ']"/>' . ($record['ukryte'] + $record['znalezione'] + $record['nieznalezione']) . ') ';
    }

    // Hide real authors of OC Team comments from users.
    if ($record['type'] == 12 && ! ($loggedUser && $loggedUser->hasOcTeamRole())) {
        $record['userid'] = '0';
        $tmplog_username_aktywnosc = '';
        $tmplog_username = tr('cog_user_name');
    }

    $tmplog = mb_ereg_replace('{username_aktywnosc}', $tmplog_username_aktywnosc, $tmplog);

    // Mobile caches
    if ($record['type'] == 4 && $record['mobile_latitude'] != 0 && ! $disable_spoiler_view) {
        $tmplog_kordy_mobilnej = mb_ereg_replace(' ', '&nbsp;', htmlspecialchars(Coordinates::donNotUse_latToDegreeStr($record['mobile_latitude']), ENT_COMPAT)) . '&nbsp;' . mb_ereg_replace(' ', '&nbsp;', htmlspecialchars(Coordinates::donNotUse_lonToDegreeStr($record['mobile_longitude']), ENT_COMPAT));
        $tmplog = mb_ereg_replace('{kordy_mobilniaka}', $record['km'] . ' km [<img src="/images/blue/arrow_mobile.png" title="' . tr('viewlog_kordy') . '" />' . $tmplog_kordy_mobilnej . ']', $tmplog);
    } else {
        $tmplog = mb_ereg_replace('{kordy_mobilniaka}', ' ', $tmplog);
    }

    if ($record['text_html'] == 0) {
        $processed_text = htmlspecialchars($processed_text, ENT_COMPAT);
        $processed_text = TextConverter::addHyperlinkToURL($processed_text);
    } else {
        $processed_text = UserInputFilter::purifyHtmlStringAndDecodeHtmlSpecialChars($processed_text, $record['text_html']);
    }

    $processed_text = SmilesInText::process($processed_text);

    $tmplog_text = $processed_text . $edit_footer;

    $logClasses = '';

    if (
        ! empty($logfilterConfig['mark_currentuser_logs'])
        && $loggedUser
        && $record['userid'] == $loggedUser->getUserId()
    ) {
        $logClasses = ' currentuser-log';
    }

    $tmplog = mb_ereg_replace('{log_classes}', $logClasses, $tmplog);

    $filterable = '';

    if (! empty($logfilterConfig['enable_logs_filtering'])) {
        $filterable = ':' . $record['type'] . ':';

        if ($record['userid'] == 0) {
            $filterable .= 'octeam';
        } elseif ($loggedUser && $record['userid'] == $loggedUser->getUserId()) {
            $filterable .= 'current';
        } elseif ($record['userid'] == $owner_id) {
            $filterable .= 'owner';
        }

        $filterable .= ':';
    }

    $tmplog = mb_ereg_replace('{filterable}', $filterable, $tmplog);

    $tmplog = mb_ereg_replace('{show_deleted}', $show_deleted, $tmplog);
    $tmplog = mb_ereg_replace('{username}', $tmplog_username, $tmplog);
    $tmplog = mb_ereg_replace('{userid}', $record['userid'], $tmplog);
    $tmplog = mb_ereg_replace('{date}', $tmplog_date, $tmplog);
    $tmplog = mb_ereg_replace('{type}', $record['text_listing'], $tmplog);
    $tmplog = mb_ereg_replace('{logtext}', $tmplog_text, $tmplog);
    $tmplog = mb_ereg_replace('{logimage}', '<a href="viewlogs.php?logid=' . $record['logid'] . '">' . icon_log_type($record['icon_small'], $record['logid']) . '</a>', $tmplog);
    $tmplog = mb_ereg_replace('{log_id}', $record['logid'], $tmplog);

    // $rating_picture
    if ($record['recommended'] == 1 && $record['type'] == 1) {
        $tmplog = mb_ereg_replace('{ratingimage}', '<img src="images/rating-star.png" alt="' . tr('recommendation') . '" />', $tmplog);
    } else {
        $tmplog = mb_ereg_replace('{ratingimage}', '', $tmplog);
    }

    // user der owner
    $logfunctions = '';
    $tmpedit = mb_ereg_replace('{logid}', $record['logid'], $edit_log);
    $tmpremove = mb_ereg_replace('{logid}', $record['logid'], $remove_log);
    $tmpRevert = mb_ereg_replace('{logid}', $record['logid'], $revertLog);
    $tmpnewpic = mb_ereg_replace('{logid}', $record['logid'], $upload_picture);

    $record['deleted'] = $record['deleted'] ?? false;

    if ($record['deleted'] != 1 && $loggedUser) {
        if (
            $record['user_id'] == $loggedUser->getUserId()
            && ($record['type'] != 12 || $loggedUser->hasOcTeamRole())
        ) {
            // Current user is the author of this log entry and can edit it, remove or add pictures.
            // If it is OC Team log, user must be active admin and author of this entry.
            $logfunctions = $functions_start . $tmpedit . $functions_middle . $tmpremove . $functions_middle . $tmpnewpic . $functions_end;
        } elseif ($owner_id == $loggedUser->getUserId() && $record['type'] != 12) {
            // Cache owner can delete log entries, except for OC Team logs.
            $logfunctions = $functions_start . $tmpremove . $functions_end;
        } elseif ($loggedUser->hasOcTeamRole()) {
            // Active admin can remove any log, but can not edit or add photos.
            $logfunctions = $functions_start . $tmpremove . $functions_end;
        }
    } elseif ($loggedUser && $loggedUser->hasOcTeamRole()) {
        $logfunctions = $functions_start . $tmpRevert . $functions_end;
    }

    $tmplog = mb_ereg_replace('{logfunctions}', $logfunctions, $tmplog);

    // Pictures
    if (
        $record['picturescount'] > 0
        // Pictures are hidden from deleted logs for non-admins
        && ($record['deleted'] == false || ($loggedUser && $loggedUser->hasOcTeamRole()))
    ) {
        $logpicturelines = '';

        if (! isset($dbc)) {
            $dbc = OcDb::instance();
        }
        $thatquery = 'SELECT `url`, `title`, `uuid`, `user_id`, `spoiler` FROM `pictures` WHERE `object_id`=:1 AND `object_type`=1';
        $statement = $dbc->multiVariableQuery($thatquery, $record['logid']);
        $pic_count = $dbc->rowCount($statement);

        if (! isset($showspoiler)) {
            $showspoiler = '';
        }

        $pictures = 0;

        while ($pic_record = $dbc->dbResultFetch($statement)) {
            if (++$pictures > 4) {
                $logpicturelines .= '<div style="clear:both"></div>';
                $pictures -= 4;
            }

            $thisline = $logpictureline;

            if ($disable_spoiler_view && intval($pic_record['spoiler']) == 1) {
                $thisline = mb_ereg_replace('{link}', 'index.php', $thisline);
                $thisline = mb_ereg_replace('{longdesc}', 'index.php', $thisline);
            } else {
                $thisline = mb_ereg_replace('{link}', $pic_record['url'], $thisline);
                $thisline = mb_ereg_replace('{longdesc}', str_replace('images/uploads', 'upload', $pic_record['url']), $thisline);
            }

            $thisline = mb_ereg_replace(
                '{imgsrc}',
                SimpleRouter::getLink(PictureController::class, 'thumbSizeSmall', [$pic_record['uuid']]),
                $thisline
            );

            $thisline = mb_ereg_replace('{title}', htmlspecialchars($pic_record['title'], ENT_COMPAT), $thisline);

            if ($loggedUser && ($pic_record['user_id'] == $loggedUser->getUserId() || $loggedUser->hasOcTeamRole())) {
                $thisfunctions
                    = '<span class="removepic">
                            <img src="/images/log/16x16-trash.png" class="icon16" alt="Trash icon">
                            &nbsp;
                            <a class="links" href="' . SimpleRouter::getLink(PictureController::class, 'remove', [$pic_record['uuid']]) . '">'
                    . tr('delete')
                    . '</a>
                          </span>';

                $thisfunctions = mb_ereg_replace('{uuid}', urlencode($pic_record['uuid']), $thisfunctions);
                $thisline = mb_ereg_replace('{functions}', $thisfunctions, $thisline);
            } else {
                $thisline = mb_ereg_replace('{functions}', '', $thisline);
            }

            $logpicturelines .= $thisline;
        }

        $logpicturelines = mb_ereg_replace('{lines}', $logpicturelines, $logpictures);

        $tmplog = mb_ereg_replace('{logpictures}', $logpicturelines, $tmplog);
    } else {
        $tmplog = mb_ereg_replace('{logpictures}', '', $tmplog);
    }

    $logs .= $tmplog . "\n";
}

tpl_set_var('logs', $logs);

unset($dbc);

$view->buildView();
