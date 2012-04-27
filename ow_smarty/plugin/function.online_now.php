<?php

function smarty_function_online_now( $params, $smarty )
{
    $buttonMarkup = '<span class="ow_lbutton ow_green" style="cursor: default;">' . OW::getLanguage()->text('base', 'user_list_online') . '</span>';
    
    if ( OW::getUser()->isAuthenticated() && isset($params['userId']) )
    {
        $allowClick = OW::getEventManager()->call('base.online_now_click', array('userId'=>OW::getUser()->getId()));
        
        if ($allowClick)
        {
            $buttonMarkup = '<a class="ow_lbutton ow_green" href="#" onclick="OW.trigger(\'base.online_now_click\', [ \'' . $params['userId'] . '\' ] );">' . OW::getLanguage()->text('base', 'user_list_online') . '</a>';
        }
        
    }

    return $buttonMarkup;
}
?>
