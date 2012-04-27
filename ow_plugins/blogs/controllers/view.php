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
 * @package ow_plugins.blogs.controllers
 * @since 1.0
 */
class BLOGS_CTRL_View extends OW_ActionController
{

    public function index( $params )
    {

        $username = !empty($params['user']) ? $params['user'] : '';

        $id = $params['id'];

        $plugin = OW::getPluginManager()->getPlugin('blogs');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'blogs', 'main_menu_item');

        $service = PostService::getInstance();

        $userService = BOL_UserService::getInstance();

        $this->assign('user', ((OW::getUser()->getId() !== null) ? $userService->findUserById(OW::getUser()->getId()) : null));

        $post = $service->findById($id);

        if ( $post === null )
        {
            throw new Redirect404Exception();
        }

        $post->post = BASE_CMP_TextFormatter::fromBBtoHtml($post->post);
        $post->setTitle(strip_tags($post->getTitle()));

        if ( !OW::getUser()->isAuthorized('blogs', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');

            return;
        }

        if ( ( OW::getUser()->isAuthenticated() && OW::getUser()->getId() != $post->getAuthorId() ) && !OW::getUser()->isAuthorized('blogs', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');

            return;
        }

        /* Check privacy permissions */
        if ( $post->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('blogs') )
        {
            $eventParams = array(
                'action' => 'blogs_view_blog_posts',
                'ownerId' => $post->authorId,
                'viewerId' => OW::getUser()->getId()
            );

            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        /* */

        $parts = explode('<!--page-->', $post->getPost());

        $page = !empty($_GET['page']) ? $_GET['page'] : 1;

        $count = count($parts);

        if ( strlen($username) > 0 )
        {
            $author = $userService->findByUsername($username);
        }
        else
        {
            $author = $userService->findUserById($post->getAuthorId());
            $isAuthorExists = !empty($author);
            if ( $isAuthorExists )
            {
                $username = $author->getUsername();
            }
        }

        $this->assign('isAuthorExists', $isAuthorExists);

        if ( $isAuthorExists )
        {
            $displayName = $userService->getDisplayName($author->getId());

            $this->assign('username', $userService->getUserName($author->getId()));
            $this->assign('displayname', $displayName);

            $url = OW::getRouter()->urlForRoute('user-blog', array('user' => $username));

            $this->setPageHeading(OW::getLanguage()->text('blogs', 'view_page_heading', array('url' => $url, 'name' => $displayName, 'postTitle' => htmlspecialchars($post->getTitle()))));
            $this->setPageHeadingIconClass('ow_ic_write');

            OW::getDocument()->setTitle(OW::getLanguage()->text('blogs', 'blog_post_title', array('post_title' => htmlspecialchars($post->getTitle()), 'display_name' => $displayName)));

            $post_body = UTIL_String::truncate($post->getPost(), 200, '...');
            $postTagsArray = BOL_TagService::getInstance()->findEntityTags($post->getId(), 'blog-post');
            $postTags = "";

            foreach ( $postTagsArray as $tag )
            {
                $postTags .= $tag->label . ", ";
            }
            $postTags = substr($postTags, 0, -2);
            OW::getDocument()->setDescription(OW::getLanguage()->text('blogs', 'blog_post_description', array('post_body' => htmlspecialchars(strip_tags($post_body)), 'tags' => htmlspecialchars($postTags))));
        }



        $info = array(
            'dto' => $post,
            'text' => $parts[$page - 1]
        );

        $this->assign('info', $info);

        if ( $isAuthorExists )
        {
            //blog navigation
            $prev = $service->findAdjacentUserPost($author->getId(), $post->getId(), 'prev');
            $next = $service->findAdjacentUserPost($author->getId(), $post->getId(), 'next');

            if ( !empty($prev) )
            {
                $prevUser = $userService->findUserById($prev->getAuthorId());
            }

            if ( !empty($next) )
            {
                $nextUser = $userService->findUserById($next->getAuthorId());
            }

            $this->assign('adjasentUrl',
                array(
                    'next' => (!empty($nextUser) ) ? OW::getRouter()->urlForRoute('user-post', array('id' => $next->getId(), 'user' => $nextUser->getUsername())) : '',
                    'prev' => (!empty($prevUser) ) ? OW::getRouter()->urlForRoute('user-post', array('id' => $prev->getId(), 'user' => $prevUser->getUsername())) : '',
                    'index' => OW::getRouter()->urlForRoute('user-blog', array('user' => $author->getUsername()))
                )
            );
        }
        //~blog navigation
        //toolbar

        $tb = array();
        if ( OW::getUser()->isAuthenticated() )
        {
            $tb[] = array(
                'href' => 'javascript://',
                'click' => "document.flag('blog_post', " . $post->getId() . ", " . htmlspecialchars(json_encode($post->getTitle())) . " , location.href, 'blogs+flags')",
                'label' => OW::getLanguage()->text('base', 'flag')
            );
        }
        if ( OW::getUser()->isAuthenticated() && ( OW::getUser()->getId() == $post->getAuthorId() || BOL_AuthorizationService::getInstance()->isModerator(OW::getUser()->getId())) )
        {
            $tb[] = array(
                'href' => OW::getRouter()->urlForRoute('post-save-edit', array('id' => $post->getId())),
                'label' => OW::getLanguage()->text('blogs', 'toolbar_edit')
            );

            $tb[] = array(
                'href' => OW::getRouter()->urlFor('BLOGS_CTRL_Save', 'delete', array('id' => $post->getId())),
                'click' => "return confirm('" . OW::getLanguage()->text('base', 'are_you_sure') . "');",
                'label' => OW::getLanguage()->text('blogs', 'toolbar_delete')
            );
        }

        $this->assign('tb', $tb);
        //~toolbar

        $paging = new BASE_CMP_Paging($page, $count, $count);

        //<ARCHIVE-NAVIGATOR>


        $this->assign('paging', $paging->render());
        if ( $isAuthorExists )
        {
            $rows = $service->findUserArchiveData($author->getId());
            $archive = array();
            foreach ( $rows as $row )
            {
                if ( !array_key_exists($row['y'], $archive) )
                {
                    $archive[$row['y']] = array();
                }
                $archive[$row['y']][] = $row['m'];
            }
            $this->assign('archive', $archive);
        }

        //</ARCHIVE-NAVIGATOR>
        if ( $isAuthorExists )
        {
            $this->assign('author', $author);
        }

        $this->assign('isModerator', OW::getUser()->isAuthorized('blogs'));
        if ( $isAuthorExists )
        {
            $this->assign('userBlogUrl', OW::getRouter()->urlForRoute('user-blog', array('user' => $author->getUsername())));
        }

        /* Check comments privacy permissions */
        $allow_comments = true;
        if ( $post->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('blogs') )
        {
            $eventParams = array(
                'action' => 'blogs_comment_blog_posts',
                'ownerId' => $post->authorId,
                'viewerId' => OW::getUser()->getId()
            );

            try
            {
                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
            }
            catch ( RedirectException $ex )
            {
                $allow_comments = false;
            }
        }
        /* */

        // additional components
        $cmpParams = new BASE_CommentsParams('blogs', 'blog-post');
        $cmpParams->setEntityId($post->getId())
            ->setOwnerId($post->getAuthorId())
            ->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST)
            ->setAddComment($allow_comments);

        $this->addComponent('comments', new BASE_CMP_Comments($cmpParams));

        $this->assign('avatarUrl', '');

        $tagCloud = new BASE_CMP_EntityTagCloud('blog-post', OW::getRouter()->urlForRoute('blogs.list', array('list'=>'browse-by-tag')));

        $tagCloud->setEntityId($post->getId());

        $rateInfo = new BASE_CMP_Rate('blogs', 'blog-post', $post->getId(), $post->getAuthorId());

        $this->addComponent('rate', $rateInfo);

        $this->addComponent('tagCloud', $tagCloud);
        //~ additional components
    }
}