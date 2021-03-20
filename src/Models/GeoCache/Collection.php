<?php

namespace src\Models\GeoCache;

use ArrayObject;

class Collection extends ArrayObject
{
    private $isReady = false;
    private $geocachesIdArray = [];

    public function append($geoCache)
    {
        if ($geoCache instanceof GeoCache) {
            parent::append($geoCache);
            $this->isReady = true;
        }
    }

    public function isReady()
    {
        return $this->isReady;
    }

    /**
     * Set array contain geocaches identifiers (equivalent to database caches.cache_id)
     * @param array $geocachesIdArray
     * @return Collection
     */
    public function setGeocachesIdArray(array $geocachesIdArray)
    {
        $this->geocachesIdArray = $geocachesIdArray;

        return $this;
    }

    /**
     * @return array
     */
    public function getGeocachesIdArray()
    {
        return $this->geocachesIdArray;
    }

    public function setIsReady($isReady)
    {
        $this->isReady = $isReady;

        return $this;
    }
}
