<?php

namespace src\Models\ChunkModels\DynamicMap;

use src\Models\GeoCache\GeoCacheLog;
use src\Models\User\User;
use src\Utils\Text\Formatter;

/**
 * This is map marker which has log information in infowindow
 * Cache properties are inherited from CacheMarkerModel
 */
class LogMarkerModel extends CacheMarkerModel
{
    public $log_link = null; // if there is no link there is no log :)
    public $log_text;
    public $log_icon;
    public $log_typeName;
    public $log_username;
    public $log_date;

    public static function fromGeoCacheLogFactory(GeoCacheLog $log, User $user = null)
    {
        $marker = new self();
        $marker->importDataFromGeoCacheLog($log, $user);

        return $marker;
    }

    protected function importDataFromGeoCacheLog(GeoCacheLog $log, User $user = null)
    {
        parent::importDataFromGeoCache($log->getGeoCache(), $user);

        $this->log_link = $log->getLogUrl();
        $text = strip_tags($log->getText(),'<br><p>');
        $textLen = mb_strlen($text);

        if ($textLen > 200) {
            $text = mb_strcut($text, 0, 200);
            $text .= '...';
        }
        $this->log_text = $text;
        $this->log_icon = $log->getLogIcon();
        $this->log_typeName = tr(GeoCacheLog::typeTranslationKey($log->getType()));
        $this->log_username = $log->getUser()->getUserName();
        $this->log_date = Formatter::date($log->getDateCreated());
    }

    /**
     * Check if all necessary data is set in this marker class
     * @return boolean
     */
    public function checkMarkerData()
    {
        return parent::checkMarkerData() &&
        isset($this->log_link, $this->log_text, $this->log_icon, $this->log_typeName, $this->log_username, $this->log_date);
    }
}
