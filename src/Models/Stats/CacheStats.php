<?php
namespace src\Models\Stats;

use src\Models\BaseObject;
use src\Models\GeoCache\GeoCache;
use src\Models\GeoCache\GeoCacheLog;
use src\Utils\Cache\OcMemCache;

class CacheStats extends BaseObject
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns JSON data for chart "Data Cache Types" on main stats page
     * Data is stored in APCu
     *
     * @return string
     */
    public static function getChartDataCacheTypes()
    {
        return OcMemCache::getOrCreate(__METHOD__, 24*60*60, function() {
            return self::generateChartDataCacheTypes();
        });
    }

    private static function generateChartDataCacheTypes()
    {
        $query =  'SELECT COUNT(`type`) `count`, `type`
            FROM `caches`
            WHERE `status` = :1
            GROUP BY `type`
            ORDER BY `count` DESC';
        $stmt = self::db()->multiVariableQuery($query, GeoCache::STATUS_READY);

        $rows = [];
        $table = [];
        $table['cols'] =  [
            ['label' => tr('cache_type'), 'type' => 'string'],
            ['label' => tr('number_of_caches'), 'type' => 'number'],
        ];

        while ($row = self::db()->dbResultFetch($stmt)) {
            $temp = [];
            $temp[] = ['v' => (string) tr(GeoCache::CacheTypeTranslationKey($row['type']))];
            $temp[] = ['v' => (int) $row['count']];
            $rows[] = ['c' => $temp];
        }

        $table['rows'] = $rows;

        return json_encode($table);
    }

    /**
     * Returns JSON data for chart "Data Caches Found" on main stats page
     * Data is stored in APCu
     *
     * @return string
     */
    public static function getChartDataCachesFound()
    {
        return OcMemCache::getOrCreate(__METHOD__, 24*60*60, function() {
            return self::generateChartDataCachesFound();
        });
    }

    private static function generateChartDataCachesFound()
    {
        // Get data
        $query = 'SELECT c.caches, l.founds, c.month, c.year FROM
            (SELECT COUNT(*) `caches`, MONTH(`date_created`) `month`, YEAR(`date_created`) `year` FROM `caches`
                WHERE caches.status = :1
                GROUP BY MONTH(`date_created`), YEAR(`date_created`)) c
        LEFT JOIN
            (SELECT COUNT(*) `founds`, MONTH(`date_created`) `month`, YEAR(`date_created`) `year` FROM `cache_logs`
                WHERE (`type` = :2 OR `type` = :3) AND `deleted` = 0
                GROUP BY MONTH(`date_created`), YEAR(`date_created`)) l
            USING(year, month)
        ORDER BY year ASC, month ASC';
        $stmt = self::db()->multiVariableQuery($query, GeoCache::STATUS_READY, GeoCacheLog::LOGTYPE_FOUNDIT, GeoCacheLog::LOGTYPE_ATTENDED);

        $caches = 0;
        $finds = 0;
        $rows = [];
        $table = [];
        $table['cols'] =  [
            ['label' => tr('graph_statistics_04'), 'type' => 'date'],
            ['label' => tr('graph_statistics_02'), 'type' => 'number'],
            ['label' => tr('graph_statistics_03'), 'type' => 'number'],
        ];

        while ($row = self::db()->dbResultFetch($stmt)) {
            $temp = [];
            $caches += (int) $row['caches'];
            $finds += (int) $row['founds'];
            $temp[] = ['v' => 'Date(' . (string) $row['year'] . ', ' . (string) ($row['month'] - 1) . ')'];
            $temp[] = ['v' => $caches];
            $temp[] = ['v' => $finds];
            $rows[] = ['c' => $temp];
        }

        $table['rows'] = $rows;

        return json_encode($table);
    }
}
