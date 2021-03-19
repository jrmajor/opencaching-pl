<?php

use src\Models\Coordinates\Coordinates;
use src\Models\Coordinates\NutsLocation;
use src\Models\ApplicationContainer;
use src\Models\Coordinates\GeoCode;

$tplname = 'region';

require_once (__DIR__.'/lib/common.inc.php');

if(!ApplicationContainer::GetAuthorizedUser()){
    echo "Not authorized!";
    exit;
}

$lat_float = 0;
if (isset($_REQUEST['lat'])) {
    $lat_float = (float) $_REQUEST['lat'];
    $lat = $_REQUEST['lat'];
}

$lon_float = 0;
if (isset($_REQUEST['lon'])) {
    $lon_float = (float) $_REQUEST['lon'];
    $lon =  $_REQUEST['lon'];
}

$coords = Coordinates::FromCoordsFactory($lat, $lon);
if(!is_null($coords)){
    tpl_set_var('coords_str', $coords->getAsText(Coordinates::COORDINATES_FORMAT_DEG_MIN));
} else {
    tpl_set_var('coords_str', '');
}

// NUTS local data
$nutsData = NutsLocation::fromCoordsFactory($coords);
tpl_set_var('nutsDesc', $nutsData->getDescription(' > '));

// Google geocode
$googleGeocode = GeoCode::fromGoogleApi($coords);
if($googleGeocode){
    tpl_set_var('googleDesc', $googleGeocode->getDescription(' > '));
}else{
    tpl_set_var('googleDesc', '-');
}

$mapQuestGeoCode = GeoCode::fromMapQuestApi($coords);
if($mapQuestGeoCode){
    tpl_set_var('mapQuestDesc', $mapQuestGeoCode->getDescription(' > '));
}else{
    tpl_set_var('mapQuestDesc', '-');
}

//make the template and send it out
tpl_BuildTemplate();
