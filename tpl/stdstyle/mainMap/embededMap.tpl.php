

<div class="content2-container">

    <div id="mainMapTopBar">
        <div id="mainMapControls">
            <!--
            // search temporary disabled
            <input id="searchControlInput" type="text" size="10">
            <input id="searchControlButton" value="<?=tr('search')?>" type="button">
            -->
            <img id="fullscreenToggle" src="/images/fullscreen-off.png"
               title="<?=tr('disable_fullscreen')?>" alt="<?=tr('disable_fullscreen')?>">

            <img id="refreshButton" src="/images/refresh.png"
               title="<?=tr('refresh_map')?>" alt="<?=tr('refresh_map')?>">

            <img id="filtersToggle" src="/okapi/static/tilemap/legend_other.png"
               title="<?=tr('toggle_filters')?>" alt="<?=tr('toggle_filters')?>">
        </div>
    </div>

    <div id="mapCanvasEmbeded"></div>

    <div>
        <div id="mapFilters" class="mapFiltersEmbeded">
          <?=$view->callSubTpl("/mainMap/mainMapFilters")?>
        </div>
    </div>
</div>



<!-- map-chunk start -->
  <?php $view->callChunk('dynamicMap/dynamicMap', $view->mapModel, "mapCanvasEmbeded");?>
<!-- map-chunk end -->

<script id="mainMapPopupTpl" type="text/x-handlebars-template">
  <?=$view->callSubTpl("/mainMap/mainMapPopup")?>
</script>

<script>
var params = {
    mapId: "mapCanvasEmbeded",
    isFullScreenMap: false,
    openPopupAtCenter: <?=isset($view->openPopup)?"true":"false"?>,
    circle150m: <?=isset($view->circle150m)?"true":"false"?>,
    userId: "<?=$view->mapUserId?>",
    userName: "<?=$view->mapUserName?>",
    searchdata: "<?=isset($view->searchData)?$view->searchData:"null"?>",
    cacheSetId: <?=isset($view->cacheSetId)?$view->cacheSetId:"null"?>,
    initUserPrefs: <?=$view->savedUserPrefs?>,
  };

  $(function() {
    mainMapEntryPoint(params);
  });

</script>