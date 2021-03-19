<?php

use src\Utils\Uri\Uri;
use src\Models\ChunkModels\DynamicMap\DynamicMapModel;

/**
 * Load local copy of Openlayers scripts
 *
 * https://openlayers.org/
 */
return function (){
    //start of chunk

$url = '/js/libs/openlayers/5.2.0';

?>
  <link rel="stylesheet"
        href="<?=Uri::getLinkWithModificationTime($url.'/ol.css')?>"
        type="text/css">

  <script src="<?=Uri::getLinkWithModificationTime($url.'/ol.js')?>"></script>

  <script>
    // map layer from ocConfig
    function getMapLayersConfig() {
      conf = <?=DynamicMapModel::getMapLayersJsConfig()?>;
      return conf;
    }
  </script>

<?php
}; //end of chunk
