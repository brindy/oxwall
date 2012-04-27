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
 * Clip Service Class.  
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.bol
 * @since 1.0
 */
final class VIDEO_BOL_ClipService
{
    /**
     * @var VIDEO_BOL_ClipDao
     */
    private $clipDao;
    /**
     * @var VIDEO_BOL_ClipFeaturedDao
     */
    private $clipFeaturedDao;
    /**
     * Class instance
     *
     * @var VIDEO_BOL_ClipService
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->clipDao = VIDEO_BOL_ClipDao::getInstance();
        $this->clipFeaturedDao = VIDEO_BOL_ClipFeaturedDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return VIDEO_BOL_ClipService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Adds video clip
     *
     * @param VIDEO_BOL_Clip $clip
     * @return int
     */
    public function addClip( VIDEO_BOL_Clip $clip )
    {
        $this->clipDao->save($clip);

        return $clip->id;
    }

    /**
     * Updates video clip
     *
     * @param VIDEO_BOL_Clip $clip
     * @return int
     */
    public function updateClip( VIDEO_BOL_Clip $clip )
    {
        $this->clipDao->save($clip);

        return $clip->id;
    }

    /**
     * Finds clip by id
     *
     * @param int $id
     * @return VIDEO_BOL_Clip
     */
    public function findClipById( $id )
    {
        return $this->clipDao->findById($id);
    }

    /**
     * Finds clip owner
     *
     * @param int $id
     * @return int
     */
    public function findClipOwner( $id )
    {
        $clip = $this->clipDao->findById($id);

        /* @var $clip VIDEO_BOL_Clip */

        return $clip ? $clip->getUserId() : null;
    }

    /**
     * Finds video clips list of specified type 
     *
     * @param string $type
     * @param int $page
     * @param int $limit
     * @return array of VIDEO_BOL_Clip
     */
    public function findClipsList( $type, $page, $limit )
    {
        if ( $type == 'toprated' )
        {
            $first = ( $page - 1 ) * $limit;
            $topRatedList = BOL_RateService::getInstance()->findMostRatedEntityList('video_rates', $first, $limit);

            $clipArr = $this->clipDao->findByIdList(array_keys($topRatedList));

            $clips = array();

            foreach ( $clipArr as $key => $clip )
            {
                $clipArrItem = (array) $clip;
                $clips[$key] = $clipArrItem;
                $clips[$key]['score'] = $topRatedList[$clipArrItem['id']]['avgScore'];
            }

            usort($clips, array('VIDEO_BOL_ClipService', 'sortArrayItemByDesc'));
        }
        else
        {
            $clips = $this->clipDao->getClipsList($type, $page, $limit);
        }

        $list = array();
        if ( is_array($clips) )
        {
            foreach ( $clips as $key => $clip )
            {
                $clip = (array) $clip;
                $list[$key] = $clip;
                $list[$key]['thumb'] = $this->getClipThumbUrl($clip['id']);
            }
        }

        return $list;
    }

    /**
     * Deletes user all clips
     * 
     * @param int $userId
     * @return boolean
     */
    public function deleteUserClips( $userId )
    {
        if ( !$userId )
        {
            return false;
        }

        $clipsCount = $this->findUserClipsCount($userId);

        if ( !$clipsCount )
        {
            return true;
        }

        $clips = $this->findUserClipsList($userId, 1, $clipsCount);

        foreach ( $clips as $clip )
        {
            $this->deleteClip($clip['id']);
        }

        return true;
    }

    public static function sortArrayItemByDesc( $el1, $el2 )
    {
        if ( $el1['score'] === $el2['score'] )
        {
            return 0;
        }

        return $el1['score'] < $el2['score'] ? 1 : -1;
    }

    /**
     * Finds user other video list
     *
     * @param int $exclude
     * @param int $itemsNum
     * @return array of VIDEO_BOL_Clip
     */
    public function findUserClipsList( $userId, $page, $itemsNum, $exclude = null )
    {
        $clips = $this->clipDao->getUserClipsList($userId, $page, $itemsNum, $exclude);

        if ( is_array($clips) )
        {
            $list = array();
            foreach ( $clips as $key => $clip )
            {
                $clip = (array) $clip;
                $list[$key] = $clip;
                $list[$key]['thumb'] = $this->getClipThumbUrl($clip['id']);
            }

            return $list;
        }

        return null;
    }

    /**
     * Finds list of tagged clips
     *
     * @param string $tag
     * @param int $page
     * @param int $limit
     * @return array of VIDEO_BOL_Clip
     */
    public function findTaggedClipsList( $tag, $page, $limit )
    {
        $first = ($page - 1 ) * $limit;

        $clipIdList = BOL_TagService::getInstance()->findEntityListByTag('video', $tag, $first, $limit);

        $clips = $this->clipDao->findByIdList($clipIdList);

        if ( is_array($clips) )
        {
            $list = array();
            foreach ( $clips as $key => $clip )
            {
                $clip = (array) $clip;
                $list[$key] = $clip;
                $list[$key]['thumb'] = $this->getClipThumbUrl($clip['id']);
            }
        }

        return $list;
    }

    public function getClipThumbUrl( $clipId )
    {
        $clip = $this->findClipById($clipId);

        if ( $clip )
        {
            $providers = new VideoProviders($clip->code);
            return $providers->getProviderThumbUrl();
        }

        return false;
    }
    
    public function getClipDefaultThumbUrl()
    {
        return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'video-no-video.png';
    }
    

    /**
     * Counts clips
     *
     * @param string $type
     * @return int
     */
    public function findClipsCount( $type )
    {
        if ( $type == 'toprated' )
        {
            return BOL_RateService::getInstance()->findMostRatedEntityCount('video');
        }

        return $this->clipDao->countClips($type);
    }

    /**
     * Counts user added clips
     *
     * @param int $userId
     * @return int
     */
    public function findUserClipsCount( $userId )
    {
        return $this->clipDao->countUserClips($userId);
    }

    /**
     * Counts clips with specified tag
     *
     * @param string $tag
     * @return array of VIDEO_BOL_Clip
     */
    public function findTaggedClipsCount( $tag )
    {
        return BOL_TagService::getInstance()->findEntityCountByTag('video', $tag);
    }

    /**
     * Gets number of clips to display per page
     *
     * @return int
     */
    public function getClipPerPageConfig()
    {
        return (int) OW::getConfig()->getValue('video', 'videos_per_page');
    }

    /**
     * Gets user clips quota
     *
     * @return int
     */
    public function getUserQuotaConfig()
    {
        return (int) OW::getConfig()->getValue('video', 'user_quota');
    }

    /**
     * Updates the 'status' field of the clip object 
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updateClipStatus( $id, $status )
    {
        $clip = $this->clipDao->findById($id);

        $newStatus = $status == 'approve' ? 'approved' : 'blocked';

        $clip->status = $newStatus;

        $this->clipDao->save($clip);

        return $clip->id ? true : false;
    }

    /**
     * Changes clip's 'featured' status
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updateClipFeaturedStatus( $id, $status )
    {
        $clip = $this->clipDao->findById($id);

        if ( $clip )
        {
            $clipFeaturedService = VIDEO_BOL_ClipFeaturedService::getInstance();

            if ( $status == 'mark_featured' )
            {
                return $clipFeaturedService->markFeatured($id);
            }
            else
            {
                return $clipFeaturedService->markUnfeatured($id);
            }
        }

        return false;
    }

    /**
     * Deletes video clip
     *
     * @param int $id
     * @return int
     */
    public function deleteClip( $id )
    {
        $this->clipDao->deleteById($id);

        BOL_CommentService::getInstance()->deleteEntityComments('video_comments', $id);
        BOL_RateService::getInstance()->deleteEntityRates($id, 'video_rates');
        BOL_TagService::getInstance()->deleteEntityTags($id, 'video');

        $this->clipFeaturedDao->markUnfeatured($id);

        BOL_FlagService::getInstance()->deleteByTypeAndEntityId('video_clip', $id);
        
        OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
            'entityType' => 'video_comments',
            'entityId' => $id
        )));

        return true;
    }
    
    public function cleanupPluginContent( )
    {
        BOL_CommentService::getInstance()->deleteEntityTypeComments('video_comments');
        BOL_RateService::getInstance()->deleteEntityTypeRates('video_rates');
        BOL_TagService::getInstance()->deleteEntityTypeTags('video');
        
        BOL_FlagService::getInstance()->deleteByType('video_clip');
    }

    /**
     * Adjust clip width and height
     *
     * @param string $code
     * @param int $width
     * @param int $height
     * @return string
     */
    public function formatClipDimensions( $code, $width, $height )
    {
        if ( !strlen($code) )
            return '';

        //adjust width and height
        $code = preg_replace("/width=(\"|')?[\d]+(px)?(\"|')?/i", 'width=${1}' . $width . '${3}', $code);
        $code = preg_replace("/height=(\"|')?[\d]+(px)?(\"|')?/i", 'height=${1}' . $height . '${3}', $code);

        $code = preg_replace("/width:( )?[\d]+(px)?/i", 'width:' . $width . 'px', $code);
        $code = preg_replace("/height:( )?[\d]+(px)?/i", 'height:' . $height . 'px', $code);

        return $code;
    }

    /**
     * Validate clip code integrity
     *
     * @param string $code
     * @return string
     */
    public function validateClipCode( $code, $provider = null )
    {
        $tags = array('object', 'embed', 'param');

        $objStart = '<object';
        $objEnd = '</object>';
        $objEndS = '/>';

        $posObjStart = stripos($code, $objStart);
        $posObjEnd = stripos($code, $objEnd);

        $posObjEnd = $posObjEnd ? $posObjEnd : stripos($code, $objEndS);

        if ( $posObjStart !== false && $posObjEnd !== false )
        {
            $posObjEnd += strlen($objEnd);
            return substr($code, $posObjStart, $posObjEnd - $posObjStart);
        }
        else
        {
            $embStart = '<embed';
            $embEnd = '</embed>';
            $embEndS = '/>';

            $posEmbStart = stripos($code, $embStart);
            $posEmbEnd = stripos($code, $embEnd) ? stripos($code, $embEnd) : stripos($code, $embEndS);

            if ( $posEmbStart !== false && $posEmbEnd !== false )
            {
                $posEmbEnd += strlen($embEnd);
                return substr($code, $posEmbStart, $posEmbEnd - $posEmbStart);
            }
            else
            {
                $frmStart = '<iframe ';
                $frmEnd = '</iframe>';
                $posFrmStart = stripos($code, $frmStart);
                $posFrmEnd = stripos($code, $frmEnd);
                if ( $posFrmStart !== false && $posFrmEnd !== false )
                {
                    $posFrmEnd += strlen($frmEnd);
                    return substr($code, $posFrmStart, $posFrmEnd - $posFrmStart);
                }
                else
                {
                    return '';
                }
            }
        }
    }

    /**
     * Adds parameter to embed code 
     *
     * @param string $code
     * @param string $name
     * @param string $value
     * @return string
     */
    public function addCodeParam( $code, $name = 'wmode', $value = 'transparent' )
    {
        $repl = $code;

        if ( preg_match("/<object/i", $code) )
        {
            $searchPattern = '<param';
            $pos = stripos($code, $searchPattern);
            if ( $pos )
            {
                $addParam = '<param name="' . $name . '" value="' . $value . '"></param><param';
                $repl = substr_replace($code, $addParam, $pos, strlen($searchPattern));
            }
        }

        if ( preg_match("/<embed/i", isset($repl) ? $repl : $code) )
        {
            $repl = preg_replace("/<embed/i", '<embed ' . $name . '="' . $value . '"', isset($repl) ? $repl : $code);
        }

        return $repl;
    }
    
    public function updateUserClipsPrivacy( $userId, $privacy )
    {
        if ( !$userId || !mb_strlen($privacy) )
        {
            return false;
        }
        
        $clips = $this->clipDao->findByUserId($userId);
        
        if ( !$clips )
        {
            return true;
        }
        
        $this->clipDao->updatePrivacyByUserId($userId, $privacy);
        
        $event = new OW_Event(
            'base.update_entity_items_status', 
            array('entityType' => 'video_comments', 'entityIds' => $clips, 'status' => $privacy == 'everybody')
        );
        OW::getEventManager()->trigger($event);
        
        return true;
    }
}