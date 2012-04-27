<?php
/**
 * Smarty function
 *
 * @param mixed var
 * @package OW_Smarty $smarty
 */

function smarty_function_user_link( $params, $smarty )
{
    $service = BOL_UserService::getInstance();

    if ( isset($params['username']) )
    {
        $url = $service->getUserUrlForUsername(trim($params['username']));
    }
    else if ( isset($params['id']) )
    {
        $url = $service->getUserUrl((int)$params['id']);
    }
    else
    {
        $url = '_INVALID_URL_';
    }

    if ( isset($params['name']) )
    {
        $name = trim($params['name']);
    }
    else
    {
        $name = '_INVALID_DISPLAY_NAME_';
    }

    $markup = "<a href=\"{$url}\">{$name}</a>";
    
    return $markup;
}