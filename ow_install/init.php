<?php

OW::getRouter()->addRoute(new OW_Route('requirements', 'install/requirements', 'INSTALL_CTRL_Install', 'requirements'));
OW::getRouter()->addRoute(new OW_Route('main', 'install', 'INSTALL_CTRL_Install', 'main'));
OW::getRouter()->addRoute(new OW_Route('db', 'install/data-base', 'INSTALL_CTRL_Install', 'db'));
OW::getRouter()->addRoute(new OW_Route('install', 'install/installation', 'INSTALL_CTRL_Install', 'install'));
OW::getRouter()->addRoute(new OW_Route('plugins', 'install/plugins', 'INSTALL_CTRL_Install', 'plugins'));

function install_tpl_feedback_flag($flag, $class = 'error')
{
    if ( INSTALL::getFeedback()->getFlag($flag) )
    {
        return $class;
    }
    
    return '';
}

function install_tpl_feedback()
{
    $feedBack = new INSTALL_CMP_FeedBack();
    
    return $feedBack->render();
}
