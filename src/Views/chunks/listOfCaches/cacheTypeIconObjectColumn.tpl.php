<?php

use src\Models\GeoCache\GeoCache;

/**
 * This is column with cache icon.
 *
 * @param GeoCache $cache
 */

return function (GeoCache $cache) {

    $statusTitle = tr($cache->getCacheTypeTranslationKey());
    $statusTitle .= ', ' . tr($cache->getStatusTranslationKey());

    ?>
    <img src="<?= $cache->getCacheIcon(app()->getUser()) ?>" class="icon16"
         alt="<?= $statusTitle ?>" title="<?= $statusTitle ?>">
    <?php
};