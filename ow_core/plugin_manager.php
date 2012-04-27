<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * The class is responsible for plugin management.
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow.ow_core
 * @since 1.0
 */
final class OW_PluginManager
{
    /**
     * @var BOL_PluginService
     */
    private $pluginService;
    /**
     * List of active plugins.
     *
     * @var array
     */
    private $activePlugins;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->pluginService = BOL_PluginService::getInstance();
        $this->readPluginsList();
    }
    /**
     * Singleton instance.
     *
     * @var OW_PluginManager
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return OW_PluginManager
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Returns active plugin object.
     *
     * @param string $key
     * @return OW_Plugin
     */
    public function getPlugin( $key )
    {
        if ( !array_key_exists(mb_strtolower(trim($key)), $this->activePlugins) )
        {
            throw new InvalidArgumentException("There is no active plugin with key `" . $key . "`");
        }

        return $this->activePlugins[mb_strtolower(trim($key))];
    }

    public function initPlugins()
    {
        /* @var $value OW_Plugin */
        foreach ( $this->activePlugins as $value )
        {
            $upperedKey = mb_strtoupper($value->getKey());

            OW::getAutoloader()->addPackagePointer($upperedKey . '_CMP', $value->getCmpDir());
            OW::getAutoloader()->addPackagePointer($upperedKey . '_CTRL', $value->getCtrlDir());
            OW::getAutoloader()->addPackagePointer($upperedKey . '_BOL', $value->getBolDir());
            OW::getAutoloader()->addPackagePointer($upperedKey . '_CLASS', $value->getClassesDir());

            include $value->getRootDir() . 'init.php';
        }
    }

    /**
     * Update active plugins list for manager.
     */
    public function readPluginsList()
    {
        $this->activePlugins = array();

        /* read all plugins from DB */
        $plugins = $this->pluginService->findActivePlugins();

        /* @var $value BOL_Plugin */
        foreach ( $plugins as $value )
        {
            $this->activePlugins[$value->getKey()] =
                ( $value->isSystem ?
                    new OW_SystemPlugin(array('dir_name' => $value->getModule(), 'key' => $value->getKey(), 'active' => $value->isActive(), 'dto' => $value)) :
                    new OW_Plugin(array('dir_name' => $value->getModule(), 'key' => $value->getKey(), 'active' => $value->isActive(), 'dto' => $value))
                );
        }
    }

    /**
     * Returns plugin key for provided module name.
     *
     * @param string $moduleName
     * @return string
     * @throws InvalidArgumentException
     */
    public function getPluginKey( $moduleName )
    {
        foreach ( $this->activePlugins as $key => $value )
        {
            if ( $moduleName === $value->getModuleName() )
            {
                return $key;
            }
        }

        throw new InvalidArgumentException('There is no plugin with module name `' . $moduleName . '` !');
    }

    /**
     * Returns module name for provided plugin key.
     *
     * @param string $pluginKey
     * @return string
     * @throws InvalidArgumentException
     */
    public function getModuleName( $pluginKey )
    {
        if ( !array_key_exists($pluginKey, $this->activePlugins) )
        {
            throw new InvalidArgumentException("There is no active plugin with key `" . $key . "`");
        }

        return $this->activePlugins[$pluginKey]->getModuleName();
    }

    /**
     * Checks if plugin is active.
     *
     * @param string $pluginKey
     * @return boolean
     */
    public function isPluginActive( $pluginKey )
    {
        return array_key_exists($pluginKey, $this->activePlugins);
    }

    /**
     * Adds admin settings page route.
     *
     * @param string $pluginKey
     * @param string $routeName
     */
    public function addPluginSettingsRouteName( $pluginKey, $routeName )
    {
        $plugin = $this->pluginService->findPluginByKey(trim($pluginKey));

        if ( $plugin !== null )
        {
            $plugin->setAdminSettingsRoute($routeName);
            $this->pluginService->savePlugin($plugin);
        }
    }

    /**
     * Adds spec. uninstall page route name.
     *
     * @param string $key
     * @param string $routName
     */
    public function addUninstallRouteName( $key, $routName )
    {
        $plugin = $this->pluginService->findPluginByKey(trim($key));

        if ( $plugin !== null )
        {
            $plugin->setUninstallRoute($routName);
            $this->pluginService->savePlugin($plugin);
        }
    }
}