<?php

function owjoinreport_report_registration( OW_Event $e )
{
    $params = $e->getParams();
    
    $userCount = BOL_UserService::getInstance()->count();
    
    if ( $userCount != 5 )
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

    $query = "SELECT * FROM `" . TBL_NETWORK . "` WHERE `id` = :networkId";
    $nw = $dbo->queryForRow($query, array('networkId' => OW_NID));

    if ( $nw['userId'] )
    {
        if ( (int) $nw['services'] > 1 ) // i.e. not default plan
        {
            return;
        }
        
        $query = "SELECT * FROM `" . TBL_USER . "` WHERE `id` = :id";
        $user = $dbo->queryForRow($query, array('id' => $nw['userId']));
        
        if ( $user )
        {
            $domain = strtolower($_site['domain']);
            
            $subject = $nw['networkName'].' reached 5 users, advertisement is on';
            $template_html = 
'<p>Your '.$_site['domain'].'-powered site, '.$nw['networkName'].' (http://'.$nw['domain'].'.'.$domain.'/) just reached 5 users. 
Congratulations with this modest milestone, you are off to a good start here!</p>
<p>According to our rules we turned on '.$_site['domain'].' ads on your site from now on. 
This helps us support free websites and avoid making '.$_site['domain'].' a paid-only tool. 
If you would like to keep your site ad-free consider subscribing for one of our premium plans. Learn more here: http://'.$domain.'/pricing.php</p>

<p>Thanks for creating with us!</p>
<p>'.$_site['domain'].' team</p>';
            
            $mail = OW::getMailer()->createMail();
            $mail->addRecipientEmail($user['email']);
            $mail->setSubject($subject);
            $mail->setHtmlContent($template_html);
    
            OW::getMailer()->send($mail);
        }
    }
}

OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, 'owjoinreport_report_registration');
