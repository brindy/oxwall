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
 * @package ow_plugins.blogs.bol.service
 * @since 1.0
 */
class PostService
{
    const PRIVACY_ACTION_VIEW_BLOG_POSTS = 'blogs_view_blog_posts';
    const PRIVACY_ACTION_COMMENT_BLOG_POSTS = 'blogs_comment_blog_posts';

    /*
     * @var BLOG_BOL_BlogService 
     */
    private static $classInstance;

    /**
     * @var array
     */
    private $config = array();

    /*
      @var PostDao
     */
    private $dao;

    private function __construct()
    {
        $this->dao = PostDao::getInstance();
        
        $this->config['allowedMPElements'] = array();
    }

    public function getConfig()
    {
        return $this->config;
    }

        /**
     * Returns class instance
     *
     * @return PostService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function save( $dto )
    {
        $dao = $this->dao;

        return $dao->save($dto);
    }

    /**
     * @return Post
     */
    public function findById( $id )
    {
        $dao = $this->dao;

        return $dao->findById($id);
    }

    //<USER-BLOG>

    private function deleteByAuthorId( $userId ) // do not use it!!
    {
        //$this->dao->deleteByAuthorId($userId);
    }
    /*
     * $which can take on of two following 'next', 'prev' values
     */

    public function findAdjacentUserPost( $id, $postId, $which )
    {
        return $this->dao->findAdjacentUserPost($id, $postId, $which);
    }

    public function findUserPostList( $userId, $first, $count )
    {
        return $this->dao->findUserPostList($userId, $first, $count);
    }

    public function findUserDraftList( $userId, $first, $count )
    {
        return $this->dao->findUserDraftList($userId, $first, $count);
    }

    public function countUserPost( $userId )
    {
        return $this->dao->countUserPost($userId);
    }

    public function countUserPostComment( $userId )
    {
        return $this->dao->countUserPostComment($userId);
    }

    public function countUserDraft( $userId )
    {
        return $this->dao->countUserDraft($userId);
    }

    public function findUserPostCommentList( $userId, $first, $count )
    {
        return $this->dao->findUserPostCommentList($userId, $first, $count);
    }

    public function findUserLastPost( $userId )
    {
        return $this->dao->findUserLastPost($userId);
    }

    public function findUserArchiveData( $id )
    {
        return $this->dao->findUserArchiveData($id);
    }

    public function findUserPostListByPeriod( $id, $lb, $ub, $first, $count )
    {
        return $this->dao->findUserPostListByPeriod($id, $lb, $ub, $first, $count);
    }

    public function countUserPostByPeriod( $id, $lb, $ub )
    {
        return $this->dao->countUserPostByPeriod($id, $lb, $ub);
    }

    //</USER-BLOG>
    //<SITE-BLOG>
    public function findList( $first, $count )
    {
        return $this->dao->findList($first, $count);
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function countPosts()
    {
        return $this->dao->countPosts();
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

    public function delete( Post $dto )
    {
        BOL_CommentService::getInstance()->deleteEntityComments('blog-post', $dto->getId());
        BOL_RateService::getInstance()->deleteEntityRates($dto->getId(), 'blog-post');
        BOL_TagService::getInstance()->deleteEntityTags($dto->getId(), 'blog-post');

        $this->dao->delete($dto);
    }

    //</SITE-BLOG>

    public function findListByIdList( $list )
    {
        return $this->dao->findListByIdList($list);
    }

    public function onAuthorSuspend( OW_Event $event )
    {
        $params = $event->getParams();
    }

    /**
     * Get set of allowed tags for blogs
     *
     * @return array
     */
    public function getAllowedHtmlTags()
    {
        return array("object", "embed", "param", "strong", "i", "u", "a", "!--more--", "img", "blockquote", "span", "pre", "iframe");
    }

    public function updateBlogsPrivacy( $userId, $privacy )
    {
        $count = $this->countUserPost($userId);
        $entities = PostService::getInstance()->findUserPostList($userId, 0, $count);
        $entityIds = array();

        foreach ($entities as $post)
        {
            $entityIds[] = $post->getId();
        }

        $status = ( $privacy == 'everybody' ) ? true : false;

        $event = new OW_Event('base.update_entity_items_status', array(
            'entityType' => 'blog-post',
            'entityIds' => $entityIds,
            'status' => $status,
        ));
        OW::getEventManager()->trigger($event);

        $this->dao->updateBlogsPrivacy( $userId, $privacy );
    }
}
