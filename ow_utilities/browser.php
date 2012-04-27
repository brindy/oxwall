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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_utilities
 * @since 1.0
 */
require_once OW_DIR_LIB . 'browser' . DS . 'browser.php';

class UTIL_Browser
{
    const BROWSER_UNKNOWN = 'unknown';
    const VERSION_UNKNOWN = 'unknown';

    const BROWSER_OPERA = 'Opera';
    const BROWSER_OPERA_MINI = 'Opera Mini';
    const BROWSER_WEBTV = 'WebTV';
    const BROWSER_IE = 'Internet Explorer';
    const BROWSER_POCKET_IE = 'Pocket Internet Explorer';
    const BROWSER_KONQUEROR = 'Konqueror';
    const BROWSER_ICAB = 'iCab';
    const BROWSER_OMNIWEB = 'OmniWeb';
    const BROWSER_FIREBIRD = 'Firebird';
    const BROWSER_FIREFOX = 'Firefox';
    const BROWSER_ICEWEASEL = 'Iceweasel';
    const BROWSER_SHIRETOKO = 'Shiretoko';
    const BROWSER_MOZILLA = 'Mozilla';
    const BROWSER_AMAYA = 'Amaya';
    const BROWSER_LYNX = 'Lynx';
    const BROWSER_SAFARI = 'Safari';
    const BROWSER_IPHONE = 'iPhone';
    const BROWSER_IPOD = 'iPod';
    const BROWSER_IPAD = 'iPad';
    const BROWSER_CHROME = 'Chrome';
    const BROWSER_ANDROID = 'Android';
    const BROWSER_GOOGLEBOT = 'GoogleBot';
    const BROWSER_SLURP = 'Yahoo! Slurp';
    const BROWSER_W3CVALIDATOR = 'W3C Validator';
    const BROWSER_BLACKBERRY = 'BlackBerry';
    const BROWSER_ICECAT = 'IceCat';
    const BROWSER_NOKIA_S60 = 'Nokia S60 OSS Browser';
    const BROWSER_NOKIA = 'Nokia Browser';
    const BROWSER_MSN = 'MSN Browser';
    const BROWSER_MSNBOT = 'MSN Bot';

    const PLATFORM_UNKNOWN = 'unknown';
    const PLATFORM_WINDOWS = 'Windows';
    const PLATFORM_WINDOWS_CE = 'Windows CE';
    const PLATFORM_APPLE = 'Apple';
    const PLATFORM_LINUX = 'Linux';
    const PLATFORM_OS2 = 'OS/2';
    const PLATFORM_BEOS = 'BeOS';
    const PLATFORM_IPHONE = 'iPhone';
    const PLATFORM_IPOD = 'iPod';
    const PLATFORM_IPAD = 'iPad';
    const PLATFORM_BLACKBERRY = 'BlackBerry';
    const PLATFORM_NOKIA = 'Nokia';
    const PLATFORM_FREEBSD = 'FreeBSD';
    const PLATFORM_OPENBSD = 'OpenBSD';
    const PLATFORM_NETBSD = 'NetBSD';
    const PLATFORM_SUNOS = 'SunOS';
    const PLATFORM_OPENSOLARIS = 'OpenSolaris';
    const PLATFORM_ANDROID = 'Android';

    /**
     * @param string $agentString
     * @return boolean
     */
    public static function isMobile( $agentString )
    {
        return self::getBrowserObj($agentString)->isMobile();
    }

    /**
     * @param string $agentString
     * @return string
     */
    public static function getBrowser( $agentString )
    {
        return self::getBrowserObj($agentString)->getBrowser();
    }

    /**
     * @param string $agentString
     * @return string
     */
    public static function getVersion( $agentString )
    {
        return self::getBrowserObj($agentString)->getVersion();
    }

    /**
     * @param string $agentString
     * @return string
     */
    public static function getPlatform( $agentString )
    {
        return self::getBrowserObj($agentString)->getPlatform();
    }

    /**
     * @param string $agentString
     * @return string
     */
    public static function isRobot( $agentString )
    {
        return self::getBrowserObj($agentString)->isRobot();
    }

    /**
     * @param string $agentString
     * @return CSBrowser
     */
    private static function getBrowserObj( $agentString )
    {
        return new CSBrowser($agentString);
    }
}