<?php

function owphotocheck_report_photo( OW_Event $e )
{
    require_once OW_SERVICE_DIR_ROOT . 'inc/header.inc.php';
    require_once OW_SERVICE_DIR_ROOT . 'inc/tbl.inc.php';

    $params = array(
        'host' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PASSWORD,
        'dbname' => DB_NAME
    );

    $dbo = OW_Database::getInstance($params);

    if ( !$dbo )
    {
        return;
    }

    $query = "SELECT `photoStatus` FROM `" . TBL_NETWORK_CACHE . "` WHERE `networkId` = :networkId";

    $result = (int) $dbo->queryForColumn($query, array('networkId' => OW_NID));

    if ( $result === 1 )
    {
        return;
    }

    $params = $e->getParams();

    foreach ( $params as $item )
    {
        $query = "INSERT INTO `" . TBL_NETWORK_PHOTO_APPROVE_ITEM . "` (`photoId`, `networkId`, `timeStamp`) VALUES (:photoId, :networkId, :timeStamp)";
        $dbo->query($query, array('photoId' => $item['photoId'], 'networkId' => OW_NID, 'timeStamp' => $item['addTimestamp']));
    }

    $query = "SELECT * FROM `" . TBL_NETWORK_PHOTO_APPROVE . "` WHERE `networkId` = :networkId";

    $result = $dbo->queryForRow($query, array('networkId' => OW_NID));

    if ( $result )
    {
        $dbo->query("UPDATE `" . TBL_NETWORK_PHOTO_APPROVE . "` SET `photoCount` = ( `photoCount` + :phCount ), `timeStamp` = :timeStamp WHERE `networkId` = :nid",
            array('phCount' => count($params), 'timeStamp' => $params[0]['addTimestamp'], 'nid' => OW_NID));
    }
    else
    {
        $dbo->query("INSERT INTO `" . TBL_NETWORK_PHOTO_APPROVE . "` ( `networkId`, `photoCount`, `timeStamp` ) VALUES ( :nid, :photoCount, :timeStamp )",
            array('nid' => OW_NID, 'photoCount' => count($params), 'timeStamp' => $params[0]['addTimestamp']));
    }
}
OW::getEventManager()->bind('plugin.photos.add_photo', 'owphotocheck_report_photo');

/* 
// Newsfeed spy
function newsfeed_spy_ajax_load_list(OW_Event $e)
{
    require_once OW_DIR_ROOT . 'wackwall/inc/header.inc.php';
    require_once OW_DIR_ROOT . 'wackwall/inc/tbl.inc.php';

    $params = array(
        'host' => DB_HOST,
        'username' => DB_USER,
        'password' => DB_PASSWORD,
        'dbname' => DB_NAME
    );

    $dbo = OW_Database::getInstance($params);

    if ( !$dbo )
    {
        return;
    }

    $query = "SELECT value FROM `xt_newsfeed_spy` WHERE `key`='view_more_usage'";
    $value = $dbo->queryForColumn($query);

    if ( $value === null )
    {
        $query = "INSERT INTO `xt_newsfeed_spy` SET `key`='view_more_usage', value=1, startTime=:st";
        $dbo->query($query, array(
            'st' => time()
        ));
    }
    else
    {
        $query = "UPDATE `xt_newsfeed_spy` SET value=:v, endTime=:et WHERE `key`='view_more_usage'";
        $dbo->query($query, array(
            'v' => $value + 1,
            'et' => time()
        ));
    }
}
OW::getEventManager()->bind('feed.on_ajax_load_list', 'newsfeed_spy_ajax_load_list'); */