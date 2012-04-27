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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.links.bol.service
 * @since 1.0
 */
class LinkService
{
    const PRIVACY_ACTION_VIEW_LINKS = 'links_view_links';
    const PRIVACY_ACTION_COMMENT_LINKS = 'links_comment_links';

    /*
     * @var LinkService 
     */
    private static $classInstance;

    /*
     * @var LinkDao
     */
    private $dao;

    const RESULT_MODE_SUM = 'sum',
    RESULT_MODE_DETAILED = 'detailed';

    private function __construct()
    {
        $this->dao = LinkDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return LinkService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function save( $dto )
    {
        return $this->dao->save($dto);
    }

    /**
     * @return Link
     */
    public function findById( $id )
    {
        return $this->dao->findById($id);
    }

    public function delete( Link $dto )
    {
        BOL_CommentService::getInstance()->deleteEntityComments('link', $dto->getId());

        BOL_TagService::getInstance()->deleteEntityTags($dto->getId(), 'link');

        BOL_VoteService::getInstance()->deleteEntityItemVotes($dto->getId(), 'link');

        $this->dao->delete($dto);
    }

    public function findList( $first, $count )
    {
        return $this->dao->findList($first, $count);
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function countLinks()
    {
        return $this->dao->countLinks();
    }


    public function findMostCommentedList( $first, $count )
    {
        return $this->dao->findMostCommentedList($first, $count);
    }

    public function findTopRatedList( $first, $count )
    {
        return $this->dao->findTopRatedList($first, $count);
    }

    public function findListByTag( $tag, $first, $count )
    {
        return $this->dao->findListByTag($tag, $first, $count);
    }

    public function countByTag( $tag )
    {
        return $this->dao->countByTag($tag);
    }

    public function findListByIdList( $list )
    {
        return $this->dao->findListByIdList($list);
    }

    public function countUserLinks( $userId )
    {
        return $this->dao->countUserLinks($userId);
    }

    public function findUserLinkList( $userId, $first, $count )
    {
        return $this->dao->findUserLinkList($userId, $first, $count);
    }

    /**
     * Get set of allowed tags for links
     *
     * @return array
     */
    public function getAllowedHtmlTags()
    {
        return array();
    }

    public function updateLinksPrivacy( $userId, $privacy )
    {
        $count = $this->countUserLinks($userId);
        $entities = $this->findUserLinkList($userId, 0, $count);
        $entityIds = array();

        foreach ($entities as $post)
        {
            $entityIds[] = $post->getId();
        }

        $status = ( $privacy == 'everybody' ) ? true : false;

        $event = new OW_Event('base.update_entity_items_status', array(
            'entityType' => 'link',
            'entityIds' => $entityIds,
            'status' => $status,
        ));
        OW::getEventManager()->trigger($event);

        $this->dao->updateLinksPrivacy( $userId, $privacy );
    }
}
