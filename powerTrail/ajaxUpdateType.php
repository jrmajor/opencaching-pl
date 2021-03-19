<?php
use src\Utils\Database\OcDb;
session_start();
if(!isset($_SESSION['user_id'])){
    echo 'no hacking please!';
    exit;
}
require_once __DIR__.'/../lib/ClassPathDictionary.php';
$ptAPI = new powerTrailBase;

$powerTrailId = $_REQUEST['projectId'];
$newType = $_REQUEST['newType'];

// check if user is owner of selected power Trail
if($ptAPI::checkIfUserIsPowerTrailOwner($_SESSION['user_id'], $powerTrailId) == 1) {
    $query = 'UPDATE `PowerTrail` SET `type`= :1 WHERE `id` = :2';
    $db = OcDb::instance();
    $db->multiVariableQuery($query, $newType, $powerTrailId);
}
