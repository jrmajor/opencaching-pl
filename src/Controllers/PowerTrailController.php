<?php

namespace src\Controllers;

use DateTime;
use sendEmail;
use src\Models\PowerTrail\Log;
use src\Models\PowerTrail\PowerTrail;
use src\Models\User\User;
use src\Utils\Database\OcDb;

class PowerTrailController
{
    const MINIMUM_PERCENT_REQUIRED = 67;

    private $config;
    private $serverUrl;

    public function __construct()
    {
        include __DIR__ . '/../../lib/settingsGlue.inc.php';
        $this->config = $powerTrailMinimumCacheCount;
        $this->serverUrl = $absolute_server_URI;

        foreach ($this->config['old'] as &$date) {
            $date['dateFrom'] = strtotime($date['dateFrom']);
            $date['dateTo'] = strtotime($date['dateTo']);
        }
    }

    public static function getEntryTypes(){
        return  [
            Log::TYPE_COMMENT => [ //comment
                'translate' => 'pt056',
                'color' => '#000000',
            ],
            Log::TYPE_CONQUESTED => [ // conquested
                'translate' => 'cs_gainedCount',
                'color' => '#00CC00',
            ],
            Log::TYPE_OPENING => [ // geoPath Publishing
                'translate' => 'pt214',
                'color' => '#0000CC',
            ],
            Log::TYPE_DISABLING => [ // geoPath temp. closed
                'translate' => 'pt216',
                'color' => '#CC0000',
            ],
            Log::TYPE_CLOSING => [ // geoPath Closure (permanent)
                'translate' => 'pt213',
                'color' => '#CC0000',
            ],
            Log::TYPE_ADD_WARNING => [ // oc team comment (permanent)
                'translate' => 'pt237',
                'color' => '#CC0000',
            ],
        ];
    }

    /**
     * Adds comment to specified PowerTrail
     *
     * @param PowerTrail $powerTrail
     * @param User $user
     * @param DateTime $dateTime
     * @param type $type
     * @param type $text
     * @return boolean
     */
    public function addComment(PowerTrail $powerTrail, User $user, DateTime $dateTime, $type, $text)
    {
        $log = new Log();
        $result = $log->setPowerTrail($powerTrail)
            ->setDateTime($dateTime)
            ->setUser($user)
            ->setType($type)
            ->setText($text)
            ->storeInDb();

        if($result){
            sendEmail::emailOwners($powerTrail->getId(), $log->getType(), $dateTime->format('Y-m-d H:i'), $text, 'newComment');
        }

        return $result;
    }

    /**
     * used to set geoPath status to inactive, when has too small amount of caches,
     * etc.
     */
    public function cleanPowerTrailsCronjob()
    {
        /* disabled until full automated geopaths-calening machine works finished
        $getPtQuery = 'SELECT * FROM `PowerTrail` WHERE `status` =1';
        $db = OcDb::instance();
        $s = $db->simpleQuery($getPtQuery);
        $ptToClean = $db->dbResultFetchAll($s);
        foreach ($ptToClean as $dbRow) {
            $powerTrail = new PowerTrail(array('dbRow' => $dbRow));
            $powerTrail->setPowerTrailConfiguration($this->config)->checkCacheCount();
            if (!$powerTrail->disableUncompletablePt($this->serverUrl)) {
                $powerTrail->disablePowerTrailBecauseCacheCountTooLow();
            }
        }
        */
        $this->archiveAbandonPowerTrails();
        $this->freeCacheCandidates();
    }

    private function archiveAbandonPowerTrails()
    {
        $db = OcDb::instance();
        $archiveAbandonQuery = 'SELECT `id` FROM `PowerTrail` WHERE `id` NOT IN (SELECT PowerTrailId FROM `PowerTrail_owners` WHERE 1 GROUP BY PowerTrailId)';
        $s = $db->simpleQuery($archiveAbandonQuery);

        if ($db->rowCount($s) > 0) { // close all abandon geoPaths
            $ptToClose = $db->dbResultFetchAll($s);
            $updateArr = [];

            foreach ($ptToClose as $pt) {
                array_push($updateArr, $pt['id']);
            }
            $updateArr = implode(',', $updateArr);
            $updQuery = 'UPDATE `PowerTrail` SET `status` =3 WHERE `id` IN ( :1 )';
            $db->multiVariableQuery($updQuery, $updateArr);
        }
    }

    private function freeCacheCandidates()
    {
        $db = OcDb::instance();
        $query = 'DELETE FROM `PowerTrail_cacheCandidate` WHERE `date` < DATE_SUB(curdate(), INTERVAL 2 WEEK)';
        $db->simpleQuery($query);
    }

    /**
     * here power Trail status
     */
    public static function getPowerTrailStatus(){
        return  [
            1 => [ // public
                'translate' => 'cs_statusPublic',
            ],
            2 => [ // not yet available
                'translate' => 'cs_statusNotYetAvailable',
            ],
            4 => [ // service
                'translate' => 'cs_statusInService',
            ],
            3 => [ // archived
                'translate' => 'cs_statusClosed',
            ],
        ];
    }
}
