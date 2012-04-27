<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Video service providers class
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.classes
 * @since 1.0
 */
class VideoProviders
{
    private $code;

    const PROVIDER_YOUTUBE = 'youtube';
    const PROVIDER_GOOGLEVIDEO = 'googlevideo';
    const PROVIDER_METACAFE = 'metacafe';
    const PROVIDER_DAILYMOTION = 'dailymotion';
    const PROVIDER_PORNHUB = 'pornhub';
    const PROVIDER_MYSPACE = 'myspace';
    const PROVIDER_VIMEO = 'vimeo';
    const PROVIDER_BLIPTV = 'bliptv';
    const PROVIDER_GUBA = 'guba';
    const PROVIDER_BIGTUBE = 'bigtube';
    const PROVIDER_TNAFLIX = 'tnaflix';

    const PROVIDER_UNDEFINED = 'undefined';

    private static $provArr;

    public function __construct( $code )
    {
        $this->code = $code;

        $this->init();
    }

    private function init()
    {
        if ( !isset(self::$provArr) )
        {
            self::$provArr = array(
                self::PROVIDER_YOUTUBE => 'http://www.youtube.com/',
                self::PROVIDER_GOOGLEVIDEO => 'http://video.google.com/',
                self::PROVIDER_METACAFE => 'http://www.metacafe.com/',
                self::PROVIDER_DAILYMOTION => 'http://www.dailymotion.com/',
                self::PROVIDER_PORNHUB => 'http://www.pornhub.com/',
                self::PROVIDER_MYSPACE => 'http://mediaservices.myspace.com/',
                self::PROVIDER_VIMEO => 'http://vimeo.com/',
                self::PROVIDER_BLIPTV => 'http://blip.tv/',
                self::PROVIDER_GUBA => 'http://www.guba.com/',
                self::PROVIDER_BIGTUBE => 'http://www.bigtube.com/',
                self::PROVIDER_TNAFLIX => 'http://www.tnaflix.com/'
            );
        }
    }

    public function detectProvider()
    {
        foreach ( self::$provArr as $name => $url )
        {
            if ( preg_match("~$url~", $this->code) )
            {
                return $name;
            }
        }
        return self::PROVIDER_UNDEFINED;
    }

    public function getProviderThumbUrl()
    {
        $provider = $this->detectProvider();

        $className = 'VideoProvider' . ucfirst($provider);

        $class = new $className;

        $thumb = $class->getThumbUrl($this->code);

        return $thumb;
    }
}

class VideoProviderYoutube
{
    const clipUidPattern = 'http:\/\/www\.youtube\.com\/(v|embed)\/([^?&"]+)[?&"]';
    const thumbUrlPattern = 'http://img.youtube.com/vi/()/default.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[2] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderGooglevideo
{
    const clipUidPattern = 'http:\/\/video\.google\.com\/googleplayer\.swf\?docid=([^\"][a-zA-Z0-9-_]+)[&\"]';
    const thumbXmlPattern = 'http://video.google.com/videofeed?docid=()';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $xmlUrl = str_replace('()', $uid, self::thumbXmlPattern);

            $fileCont = @file_get_contents($xmlUrl);

            if ( strlen($fileCont) )
            {
                preg_match("/media:thumbnail url=\"([^\"]\S*)\"/siU", $fileCont, $match);

                $url = isset($match[1]) ? $match[1] : VideoProviders::PROVIDER_UNDEFINED;
            }

            return !empty($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderMetacafe
{
    const clipUidPattern = 'http://www.metacafe.com/fplayer/([^/]+)/';
    const thumbUrlPattern = 'http://www.metacafe.com/thumb/().jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderDailymotion
{
    const clipUidPattern = 'http://www.dailymotion.com/(swf|embed)/video/([^"]+)"';
    const thumbUrlPattern = 'http://www.dailymotion.com/thumbnail/video/()';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[2] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderPornhub
{
    const clipUidPattern = 'http://www.pornhub.com/embed_player.php\?id\=([\d]+)';
    const thumbUrlPattern = 'http://pics1.pornhub.com/thumbs/()//small.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        $uid = preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;

        return $uid;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $uid = sprintf("%'09s", $uid);

            $res = '';
            for ( $i = 0; $i < strlen($uid); $i += 3 )
            {
                if ( isset($uid[$i]) )
                    $res .= $uid[$i]; else
                    break;
                if ( isset($uid[$i + 1]) )
                    $res .= $uid[$i + 1]; else
                    break;
                if ( isset($uid[$i + 2]) )
                    $res .= $uid[$i + 2] . '/'; else
                    break;
            }

            $res = substr($res, 0, -1);

            $url = str_replace('()', $res, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderMyspace
{
    const clipUidPattern = 'http:\/\/mediaservices\.myspace\.com.*embed.aspx\/m=([0-9]*)';
    const thumbXmlPattern = 'http://mediaservices.myspace.com/services/rss.ashx?type=video&videoID=()';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $xmlUrl = str_replace('()', $uid, self::thumbXmlPattern);

            $xml = simplexml_load_string(str_replace('media:thumbnail', 'mediathumbnail', @file_get_contents($xmlUrl)));

            $url = $xml->channel->item->mediathumbnail['url'];

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderVimeo
{
    const clipUidPattern = 'http:\/\/vimeo\.com\/([0-9]*)["]';
    const thumbXmlPattern = 'http://vimeo.com/api/v2/video/().xml';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $xmlUrl = str_replace('()', $uid, self::thumbXmlPattern);

            $xml = simplexml_load_string(@file_get_contents($xmlUrl));

            $url = (string)$xml->video->thumbnail_small;

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderBliptv
{
    const clipUidPattern = 'http:\/\/blip\.tv\/play\/([^"]+)\"';
    const thumbJsonPattern = 'http://blip.tv/players/episode/()?skin=json&version=2&callback=meta';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $jsonUrl = str_replace('()', $uid, self::thumbJsonPattern);

            $fileCont = @file_get_contents($jsonUrl);

            if ( strlen($fileCont) )
            {
                $fileCont = trim($fileCont);
                $fileCont = substr($fileCont, 6, strlen($fileCont) - 9);
                $metaObj = @json_decode($fileCont);

                if ( $metaObj )
                {
                    $url = @$metaObj->thumbnailUrl;
                }
            }

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderGuba
{
    const clipUidPattern = 'http:\/\/www\.guba\.com\/static\/.*bid=([^\']+)\'';
    const thumbUrlPattern = 'http://img.guba.com/public/video/1/01/()-b.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderBigtube
{
    const clipUidPattern = 'http:\/\/www\.bigtube\.com\/embedplayer\/.*video_id=([^\&]+)\&';
    const thumbUrlPattern = 'http://static.ss.bigtube.com/()/160x120_1030.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderTnaflix
{
    const clipUidPattern = 'embedding_feed\.php\?viewkey=([^\"]+)\"';
    const thumbXmlPattern = 'http://www.tnaflix.com/embedding_player/embedding_feed.php?viewkey=()';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $xmlUrl = str_replace('()', $uid, self::thumbXmlPattern);

            $fileCont = @file_get_contents($xmlUrl);

            if ( strlen($fileCont) )
            {
                preg_match("/\<start_thumb\>(.*?)\<\/start_thumb\>/siU", $fileCont, $match);

                $url = isset($match[1]) ? $match[1] : VideoProviders::PROVIDER_UNDEFINED;
            }

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderUndefined
{

    public static function getThumbUrl( $code )
    {
        return VideoProviders::PROVIDER_UNDEFINED;
    }
}
