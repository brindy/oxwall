<?php

//$plugin = OW::getPluginManager()->getPlugin('owauthstat');
//OW::getAutoloader()->addClass('AUTHSTAT_CLASS_Kissmetrics', $plugin->getClassesDir() . 'kissmetrics.php');

define('KISSMETRICS_API_KEY', '8d455d9c77b09f8614a991a41468661fb3535c1f');

function owauthstat_report_admin_auth( OW_Event $e )
{
    $params = $e->getParams();
    $userId = (int) $params['userId'];
    
    $isAdmin = OW::getAuthorization()->isUserAuthorized($userId, BOL_AuthorizationService::ADMIN_GROUP_NAME);
    
    if ( !$isAdmin )
    {
        return;
    }
    
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

    $query = "SELECT * FROM `" . TBL_NETWORK_CACHE . "` WHERE `networkId` = :networkId";

    $result = $dbo->queryForRow($query, array('networkId' => OW_NID));

    if ( $result )
    {
        $sql = "UPDATE `" . TBL_NETWORK_CACHE . "` SET `adminAuthStamp` = :ts WHERE `networkId` = :networkId";
    }
    else 
    {
        $sql = "INSERT INTO `" . TBL_NETWORK_CACHE . "` SET `adminAuthStamp` = :ts, `networkId` = :networkId";
    }
    
    $dbo->query($sql, array('ts' => time(), 'networkId' => OW_NID));
}

OW::getEventManager()->bind(OW_EventManager::ON_USER_LOGIN, 'owauthstat_report_admin_auth');


function owauthstat_report_admin_page_view( OW_Event $e )
{
    if ( !OW::getUser()->isAuthenticated() )
    {
        return;
    }

    $userId = OW::getUser()->getId();
    
    $isAdmin = OW::getAuthorization()->isUserAuthorized($userId, BOL_AuthorizationService::ADMIN_GROUP_NAME);
    
    if ( !$isAdmin )
    {
        return;
    }

    $masterpage = OW::getDocument()->getMasterPage();
    
    if ( $masterpage && ($masterpage instanceof ADMIN_CLASS_MasterPage) )
    {
        $identity = !empty($_COOKIE['km_ai']) ? $_COOKIE['km_ai'] : null;
        
        if ( !strlen($identity) )
        {
            $ref = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
            $identity = md5( $ref. rand(1, date('U')) . date('U') . $_SERVER['HTTP_USER_AGENT']);
        }
        
        OWAUTHSTAT_CLASS_Kissmetrics::init(KISSMETRICS_API_KEY, array('log_dir' => OW_DIR_ROOT . DS . 'ow_logs' . DS . 'kissm', 'to_stderr' => false));
        
        if ( empty($_SESSION['kms_aliased']) )
        {
            OWAUTHSTAT_CLASS_Kissmetrics::alias($identity, OW_NID);
            $_SESSION['kms_aliased'] = true;
        }
        $identity = OW_NID;
        
        OWAUTHSTAT_CLASS_Kissmetrics::identify($identity);

        OWAUTHSTAT_CLASS_Kissmetrics::record('Admin Page View');
    }
}

OW::getEventManager()->bind(OW_EventManager::ON_FINALIZE, 'owauthstat_report_admin_page_view');