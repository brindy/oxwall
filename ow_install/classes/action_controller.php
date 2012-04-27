<?php

class INSTALL_ActionController extends INSTALL_Renderable
{
    private $title = 'OW Install';
    
    private $navigation;
    
    public function __construct()
    {
        
    }
    
    public function setPageTitle( $title )
    {
        $this->title = $title;
    }
    
    public function getPageTitle()
    {
        return $this->title;
    }

    /**
     * Makes permanent redirect to provided URL or URI.
     *
     * @param string $redirectTo
     */
    public function redirect( $redirectTo = null )
    {
        OW::getApplication()->redirect($redirectTo);
    }

    /**
     * Optional method for override.
     * Called before action is called.
     */
    public function init()
    {
    }
}