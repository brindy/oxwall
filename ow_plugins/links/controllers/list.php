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
 * @package ow_plugins.links.controllers
 * @since 1.0
 */
class LINKS_CTRL_List extends OW_ActionController
{

    public function index()
    {

        if ( !OW::getUser()->isAuthorized('links', 'view') )
        {
            $this->setTemplate(OW::getPluginManager()->getPlugin('base')->getCtrlViewDir() . 'authorization_failed.html');

            return;
        }

        $this->assign('addNew_isAuthorized', OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('links', 'add'));

        switch ( OW::getConfig()->getValue('links', 'result_mode') )
        {
            case LinkService::RESULT_MODE_SUM:
                $this->assign('mode', 'sum');
                break;
            case LinkService::RESULT_MODE_DETAILED:
                $this->assign('mode', 'detailed');
                break;
        }


        $this->setPageHeading(OW::getLanguage()->text('links', 'list_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_link');

        $plugin = OW::getPluginManager()->getPlugin('links');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $plugin->getKey(), 'main_menu_item');

        if ( ( false === strstr($_SERVER['REQUEST_URI'], 'browse-by-tag') ) )
        {
            $isBrowseByTagCase = false;
        }
        else
        {
            $this->assign('tag', empty($_GET['tag']) ? '' : strip_tags($_GET['tag']));
            $isBrowseByTagCase = true;
        }

        $this->assign('isBrowseByTagCase', $isBrowseByTagCase);

        $tagCloud = new BASE_CMP_EntityTagCloud('link', OW::getRouter()->urlForRoute('links-by-tag'));

        if ( $isBrowseByTagCase )
        {
            $tagCloud->setTemplate(OW::getPluginManager()->getPlugin('base')->getCmpViewDir() . 'big_tag_cloud.html');
        }

        $this->addComponent('tagCloud', $tagCloud);

        $tagSearch = new BASE_CMP_TagSearch(OW::getRouter()->urlForRoute('links-by-tag'));
        $this->addComponent('tagSearch', $tagSearch);

//~~

        $menu = new BASE_CMP_ContentMenu($this->getMenuItems());

        $this->assign('menu', $menu->render());

        $service = LinkService::getInstance();

        $rpp = (int) OW::getConfig()->getValue('links', 'results_per_page');


        $page = !empty($_GET['page']) && (int) $_GET['page'] ? (int) $_GET['page'] : 1;
                
        $first = ($page - 1) * $rpp;

        $count = $rpp;

        $list = array();
        $itemsCount = 0;

        list($list, $itemsCount) = $this->getData($first, $count);

        $descLength = 120;

        //$this->assign('descLength', $descLength);

        $titleLength = 50;

        $this->assign('titleLength', $titleLength);


        $voteService = BOL_VoteService::getInstance();

        $idList = array();
        $links = array();

        $voteTotall = array();

        $authorIdList = array();

        $commentTotall = array();

        foreach ( $list as $item )
        {
            $link = $item['dto'];
            
            $link->setUrl(strip_tags($link->getUrl()));
            $link->setTitle(strip_tags($link->getTitle()));
            $description = BASE_CMP_TextFormatter::fromBBtoHtml($link->getDescription());
            $description = strip_tags($description);
            if (strlen($description) > $descLength)
            {
                $description = UTIL_String::truncate($description, $descLength);
                $description .= '<a href="'. OW::getRouter()->urlForRoute('link', array('id'=>$link->getId())).'" class="ow_lbutton">'.OW::getLanguage()->text('base', 'more').'</a>';
            }
			                   		
            $link->setDescription($description);

            $links[$link->getId()] = array(
                'dto' => $link
            );

            $idList[] = $link->getId();

            $authorIdList[] = $link->getUserId();
        }

        $ulist = BOL_UserService::getInstance()->getUserNamesForList($authorIdList);
        $nlist = BOL_UserService::getInstance()->getDisplayNamesForList($authorIdList);

        $this->assign('usernameList', $ulist);
        $this->assign('nameList', $nlist);

        if ( !empty($idList) )
        {
            $commentTotall = BOL_CommentService::getInstance()->findCommentCountForEntityList('link', $idList);

            $voteTotall = $voteService->findTotalVotesResultForList($idList, 'link');

            $tagsInfo = BOL_TagService::getInstance()->findTagListByEntityIdList('link', $idList);

            $this->assign('tagsInfo', $tagsInfo);

            $toolbars = array();

            foreach ( $list as $item )
            {
                $dto = $item['dto'];
                $toolbars[$dto->id] = array();

                $userId = $dto->userId;

                $toolbars[$dto->id][] = array(
                    'class' => ' ow_icon_control ow_ic_user',
                    'label' => !empty($nlist[$userId]) ? $nlist[$userId] : 'deleted user',
                    'href' => !empty($ulist[$userId]) ? BOL_UserService::getInstance()->getUserUrlForUsername($ulist[$userId]) : '#'
                );

                $toolbars[$dto->id][] = array(
                    'label' => UTIL_DateTime::formatDate($dto->timestamp),
                );

                $toolbars[$dto->id][] = array(
                    'href' => OW::getRouter()->urlForRoute('link', array('id' => $dto->id)),
                    'label' => '<span class="ow_txt_value">' . $commentTotall[$dto->id] . '</span> ' . OW::getLanguage()->text('links', 'toolbar_comments')
                );

                if ( empty($tagsInfo[$dto->id]) )
                {
                    continue;
                }

                $value = OW::getLanguage()->text('links', 'tags') . ' ';

                $c = 0;
                foreach ( $tagsInfo[$dto->id] as $tag )
                {
                    if ( $c == 3 )
                        break;

                    $value .='<a href="' . OW::getRouter()->urlForRoute('links-by-tag') . "?tag={$tag}" . "\">{$tag}</a>, ";

                    $c++;
                }

                $value = mb_substr($value, 0, mb_strlen($value) - 2);

                $toolbars[$dto->id][] = array(
                    'label' => $value,
                );
            }

            if ( OW::getUser()->isAuthenticated() )
            {
                $userVotes = $voteService->findUserVoteForList($idList, 'link', OW::getUser()->getId());
                $this->assign('userVotes', $userVotes);
            }

            $this->assign('tb', $toolbars);
        }

        foreach ( $voteTotall as $val )
        {
            $links[$val['id']]['isVoted'] = true;
            $links[$val['id']]['voteTotal'] = $val['sum'];
            $links[$val['id']]['up'] = $val['up'];
            $links[$val['id']]['down'] = $val['down'];
        }

        $this->assign('commentTotall', $commentTotall);

        $this->assign('list', $links);

        $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);

        $this->assign('paging', $paging->render());
        $this->assign('isAuthenticated', OW::getUser()->isAuthenticated());
    }

    public function vote()
    {
        if ( !($userId = OW::getUser()->getId()) )
        {
            echo json_encode(array('isAuthenticated' => false, 'msg' => OW::getLanguage()->text('links', 'sign_in_request')));
            exit;
        }

        $id = $_POST['itemId'];

        $voteService = BOL_VoteService::getInstance();

        $vote = $voteService->findUserVote($id, 'link', $userId);
        $voteType = 0;

        switch ( $_POST['vote'] )
        {
            case 'UP':
                if ( !$vote )
                {
                    $vote = new BOL_Vote();
                    $vote->setEntityType('link')->setEntityId($id)->setUserId($userId);
                }
                $voteType = 1;
                $vote->setVote($voteType);
                $voteType = "+1";
                $voteService->saveVote($vote->setTimeStamp(time()));
                break;

            case 'DOWN':
                if ( !$vote )
                {
                    $vote = new BOL_Vote();
                    $vote->setEntityType('link')->setEntityId($id)->setUserId($userId);
                }
                $voteType = -1;
                $vote->setVote($voteType);
                $voteType = "-1";
                $voteService->saveVote($vote->setTimeStamp(time()));

                break;

            case 'CANCEL':

                if ( $vote !== null )
                {
                    $voteService->delete($vote);
                }

                break;

            default:
                echo '{}';
                exit;
        }


        $total = $voteService->findTotalVotesResult($id, 'link');

        echo json_encode((object) array('type' => 'link', 'id' => $id, 'total' => $total, 'voteType' => $voteType));

        exit;
    }

    private function getMenuItems()
    {

        $item = array();

        $item[0] = new BASE_MenuItem();

        $item[0]->setLabel(OW::getLanguage()->text('links', 'menuItemLatest'))
            ->setKey('1')
            ->setUrl(OW::getRouter()->urlForRoute('links-latest'))
            ->setIconClass('ow_ic_clock');

        if ( OW::getRequest()->getRequestUri() == OW::getRouter()->uriForRoute('links') )
            $item[0]->setActive(true);



        $item[0]->setOrder(1);

        $item[1] = new BASE_MenuItem(array());

        $item[1]->setLabel(OW::getLanguage()->text('links', 'menuItemMostDiscussed'));

        $item[1]->setKey('2');
        $item[1]->setUrl(OW::getRouter()->urlForRoute('links-most-discussed'))->setIconClass('ow_ic_comment');
        $item[1]->setOrder(3);

        $item[2] = new BASE_MenuItem();

        $item[2]->setLabel(OW::getLanguage()->text('links', 'menuItemTopRated'));
        $item[2]->setKey('3');
        $item[2]->setUrl(OW::getRouter()->urlForRoute('links-top-rated'))->setIconClass('ow_ic_star');
        $item[2]->setOrder(2);

//--
        $item[3] = new BASE_MenuItem();

        $item[3]->setKey('4')
            ->setLabel(OW::getLanguage()->text('links', 'menuItemBrowseByTag'))
            ->setOrder(4)
            ->setIconClass('ow_ic_tag');

        $item[3]->setUrl(
            OW::getRouter()->urlForRoute('links-by-tag')
        );

        if ( ( false !== strstr($_SERVER['REQUEST_URI'], 'browse-by-tag') ) )
            $item[3]->setActive(true);

        return $item;
    }

    function getCase()
    {
        switch ( true )
        {
            case ( false !== strstr(OW::getRequest()->getRequestUri(), 'most-discussed') ):
                return 'most-discussed';

            case ( false !== strstr(OW::getRequest()->getRequestUri(), 'top-rated') ):
                return 'top-rated';

            case ( false !== strstr(OW::getRequest()->getRequestUri(), 'browse-by-tag') ):
                return 'browse-by-tag';

            case ( false !== strstr($_SERVER['REQUEST_URI'], 'latest') ):
            default:
                return 'latest';
        }
    }

    private function getData( $first, $count )
    {

        $service = LinkService::getInstance();

        $list = array();
        $itemCount = 0;

        $case = $this->getCase();
        switch ( $case )
        {
            case 'most-discussed':

                $commentService = BOL_CommentService::getInstance();

                $info = array();
                $info = $commentService->findMostCommentedEntityList('link', $first, $count);

                $idList = array();

                foreach ( $info as $item )
                {
                    $idList[] = $item['id'];
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $service->findListByIdList($idList);

                foreach ( $dtoList as $dto )
                {
                    $list[] = array(
                        'dto' => $dto,
                        'commentCount' => $info[$dto->id] ['commentCount'],
                    );
                }

                function sortMostCommented( $e, $e2 )
                {

                    return $e['commentCount'] < $e2['commentCount'];
                }
                usort($list, 'sortMostCommented');

                $itemsCount = $commentService->findCommentedEntityCount('link');

                OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'most_discussed_title'));
                OW::getDocument()->setDescription(OW::getLanguage()->text('links', 'most_discussed_description' ));

                break;

            case 'top-rated':

                $info = array();
                $info = BOL_VoteService::getInstance()->findMostVotedEntityList('link', $first, $count);

                $idList = array();

                foreach ( $info as $item )
                {
                    $idList[] = $item['id'];
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $service->findListByIdList($idList);

                foreach ( $dtoList as $dto )
                {
                    $list[] = array(
                        'dto' => $dto,
                        'sum' => $info[$dto->id] ['sum'],
                    );
                }

                function sortTopRated( $e, $e2 )
                {
                    return $e['sum'] < $e2['sum'];
                }
                usort($list, 'sortTopRated');

                $itemCount = BOL_VoteService::getInstance()->findMostVotedEntityCount('link');

                OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'top_rated_title'));
                OW::getDocument()->setDescription(OW::getLanguage()->text('links', 'top_rated_description' ));

                break;

            case 'browse-by-tag':

                if ( empty($_GET['tag']) )
                {
                    $mostPopularTagsArray = BOL_TagService::getInstance()->findMostPopularTags('link', 20);
                    $mostPopularTags = "";

                    foreach ( $mostPopularTagsArray as $tag )
                    {
                        $mostPopularTags .= $tag['label'] . ", ";
                    }

                    OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'browse_by_tag_title'));
                    OW::getDocument()->setDescription(OW::getLanguage()->text('links', 'browse_by_tag_description', array('tags' => $mostPopularTags)));

                    break;
                }

                $info = BOL_TagService::getInstance()->findEntityListByTag('link', UTIL_HtmlTag::stripTags($_GET['tag']), $first, $count);

                $itemsCount = BOL_TagService::getInstance()->findEntityCountByTag('link', UTIL_HtmlTag::stripTags($_GET['tag']));

                foreach ( $info as $item )
                {
                    $idList[] = $item;
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $service->findListByIdList($idList);

                foreach ( $dtoList as $dto )
                {
                    $dto->setUrl(strip_tags($dto->getUrl()));
                    $dto->setTitle(strip_tags($dto->getTitle()));
                    $dto->setDescription(BASE_CMP_TextFormatter::fromBBtoHtml($dto->getDescription()));
                    $list[] = array('dto' => $dto);
                }

                OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'browse_by_tag_item_title', array( 'tag'=>  htmlspecialchars(UTIL_HtmlTag::stripTags($_GET['tag'])) )));
                OW::getDocument()->setDescription(OW::getLanguage()->text('links', 'browse_by_tag_item_description', array( 'tag'=>htmlspecialchars(UTIL_HtmlTag::stripTags($_GET['tag'])) )));

                break;

            case ( false !== strstr($_SERVER['REQUEST_URI'], 'latest') ):
            default:
                $dtoList = $service->findList($first, $count);
                $itemCount = $service->countLinks();

                foreach ( $dtoList as $dto )
                {
                    $list[] = array('dto' => $dto);
                }

                OW::getDocument()->setTitle(OW::getLanguage()->text('links', 'latest_title'));
                OW::getDocument()->setDescription(OW::getLanguage()->text('links', 'latest_description' ));

                break;
        }

        return array($list, $itemCount);
    }
}

?>