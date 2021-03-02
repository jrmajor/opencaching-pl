<?php

use src\Utils\Database\OcDb;
use src\Utils\Uri\Uri;
use src\Controllers\PictureController;
use src\Models\GeoCache\GeoCache;
use src\Models\Pictures\Thumbnail;
use src\Utils\Uri\SimpleRouter;

require_once (__DIR__.'/lib/common.inc.php');

global $hide_coords;

$view = tpl_getView();
$app = app();

if (!isset($_REQUEST['cacheid']) || ($cache = GeoCache::fromCacheIdFactory($_REQUEST['cacheid'])) === null) {
    $view->redirect('/');
    exit();
}

// Chceck if gallery should be visible
if (($cache->getStatus() == GeoCache::STATUS_WAITAPPROVERS
        || $cache->getStatus() == GeoCache::STATUS_NOTYETAVAILABLE
        || $cache->getStatus() == GeoCache::STATUS_BLOCKED)
    && ($app->getUser() === null
        || ($app->getUser()->getUserId() != $cache->getOwnerId()
            && ! $app->getUser()->hasOcTeamRole()))) {
    $view->redirect('/');
    exit();
}

// Prepare array of log pictures
$params = [];
$query = 'SELECT `pictures`.`url`, `pictures`.`title`, `pictures`.`uuid`, `pictures`.`spoiler`, `pictures`.`object_id`
                  FROM `pictures`,`cache_logs`
                  WHERE `pictures`.`object_id`=`cache_logs`.`id` AND `cache_logs`.`deleted` = 0
                      AND `pictures`.`object_type`= 1
                      AND `cache_logs`.`cache_id`= :cacheid
                  ORDER BY `pictures`.`date_created` DESC';
$params['cacheid']['value'] = $cache->getCacheId();
$params['cacheid']['data_type'] = 'integer';
$stmt = OcDb::instance()->paramQuery($query, $params);
$logpictures = [];
while ($row = OcDb::instance()->dbResultFetch($stmt)) {
    $row['url'] = str_replace("images/uploads", "upload", $row['url']);
    if ($row['spoiler'] == '1') {
        $row['thumbUrl'] = Thumbnail::placeholderUri(Thumbnail::PHD_SPOILER);
    } else {
        $row['thumbUrl'] = SimpleRouter::getLink(PictureController::class, 'thumbSizeMedium', [$row['uuid']]);
    }
    $logpictures[] = $row;
}
$view->setVar('logpictures', $logpictures);

$view->setVar('cachepictures', $cache->getPicturesList(false));
$view->setVar('hidespoilers', ($app->getUser() === null && $hide_coords));
$view->setVar('cache', $cache);
$view->setVar('cacheicon', $cache->getCacheIcon($app->getUser()));
$view->addLocalCss(Uri::getLinkWithModificationTime('/views/viewcache/viewcache.css'));
$view->loadFancyBox();
$view->setTemplate('gallery_cache');
$view->buildView();
