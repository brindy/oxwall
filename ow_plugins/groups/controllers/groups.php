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
 * Groups
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.controllers
 * @since 1.0
 */
class GROUPS_CTRL_Groups extends OW_ActionController
{
    /**
     *
     * @var GROUPS_BOL_Service
     */
    private $service;

    public function __construct()
    {
        $this->service = GROUPS_BOL_Service::getInstance();

        if ( !OW::getRequest()->isAjax() )
        {
            $mainMenuItem = OW::getDocument()->getMasterPage()->getMenu(OW_Navigation::MAIN)->getElement('main_menu_list');
            if ( $mainMenuItem !== null )
            {
                $mainMenuItem->setActive(true);
            }
        }
    }

    public function index()
    {
        $this->mostPopularList();
    }

    public function customize( $params )
    {
        $params['mode'] = 'customize';

        $this->view($params);
    }

    public function view( $params )
    {
        $groupId = (int) $params['groupId'];

        if ( empty($groupId) )
        {
            throw new Redirect404Exception();
        }

        $groupDto = $this->service->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }


        $language = OW::getLanguage();

        if ( !$this->service->isCurrentUserCanView($groupDto->userId) )
        {
            $this->assign('permissionMessage', $language->text('groups', 'view_no_permission'));
            return;
        }

        if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && !OW::getUser()->isAuthorized('groups') )
        {
            if ( !OW::getUser()->isAuthenticated() )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }

            $invite = $this->service->findInvite($groupDto->id, OW::getUser()->getId());
            $user = $this->service->findUser($groupDto->id, OW::getUser()->getId());

            if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && $invite === null && $user === null )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }
        }

        OW::getDocument()->setTitle($language->text('groups', 'view_page_title', array(
            'group_name' => strip_tags($groupDto->title)
        )));

        OW::getDocument()->setDescription($language->text('groups', 'view_page_description', array(
            'description' => UTIL_String::truncate(strip_tags($groupDto->description), 200)
        )));

        $place = 'group';

        $customizeUrls = array(
            'customize' => OW::getRouter()->urlForRoute('groups-customize', array('mode' => 'customize', 'groupId' => $groupId)),
            'normal' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId))
        );

        $componentAdminService = BOL_ComponentAdminService::getInstance();
        $componentEntityService = BOL_ComponentEntityService::getInstance();

        $userCustomizeAllowed = $componentAdminService->findPlace($place)->editableByUser;
        $ownerMode = $groupDto->userId == OW::getUser()->getId();

        $customize = !empty($params['mode']) && $params['mode'] == 'customize';

        if ( !( $userCustomizeAllowed && $ownerMode ) && $customize )
        {
            $this->redirect($customizeUrls['normal']);
        }

        $template = $customize ? 'drag_and_drop_entity_panel_customize' : 'drag_and_drop_entity_panel';

        $schemeList = $componentAdminService->findSchemeList();
        $defaultScheme = $componentAdminService->findSchemeByPlace($place);
        if ( empty($defaultScheme) && !empty($schemeList) )
        {
            $defaultScheme = reset($schemeList);
        }

        if ( !$componentAdminService->isCacheExists($place) )
        {
            $state = array();
            $state['defaultComponents'] = $componentAdminService->findPlaceComponentList($place);
            $state['defaultPositions'] = $componentAdminService->findAllPositionList($place);
            $state['defaultSettings'] = $componentAdminService->findAllSettingList();
            $state['defaultScheme'] = $defaultScheme;

            $componentAdminService->saveCache($place, $state);
        }

        $state = $componentAdminService->findCache($place);

        $defaultComponents = $state['defaultComponents'];
        $defaultPositions = $state['defaultPositions'];
        $defaultSettings = $state['defaultSettings'];
        $defaultScheme = $state['defaultScheme'];

        if ( $userCustomizeAllowed )
        {
            if ( !$componentEntityService->isEntityCacheExists($place, $groupId) )
            {
                $entityCache = array();
                $entityCache['entityComponents'] = $componentEntityService->findPlaceComponentList($place, $groupId);
                $entityCache['entitySettings'] = $componentEntityService->findAllSettingList($groupId);
                $entityCache['entityPositions'] = $componentEntityService->findAllPositionList($place, $groupId);

                $componentEntityService->saveEntityCache($place, $groupId, $entityCache);
            }

            $entityCache = $componentEntityService->findEntityCache($place, $groupId);
            $entityComponents = $entityCache['entityComponents'];
            $entitySettings = $entityCache['entitySettings'];
            $entityPositions = $entityCache['entityPositions'];
        }
        else
        {
            $entityComponents = array();
            $entitySettings = array();
            $entityPositions = array();
        }

        $componentPanel = new BASE_CMP_DragAndDropEntityPanel($place, $groupId, $defaultComponents, $customize, $template);
        $componentPanel->setAdditionalSettingList(array(
            'entityId' => $groupId,
            'entity' => 'groups'
        ));

        if ( $ownerMode )
        {
            $componentPanel->allowCustomize($userCustomizeAllowed);
            $componentPanel->customizeControlCunfigure($customizeUrls['customize'], $customizeUrls['normal']);
        }

        $componentPanel->setSchemeList($schemeList);
        $componentPanel->setPositionList($defaultPositions);
        $componentPanel->setSettingList($defaultSettings);
        $componentPanel->setScheme($defaultScheme);

        /*
         * This feature was disabled for users
         * if ( !empty($userScheme) )
          {
          $componentPanel->setUserScheme($userScheme);
          } */

        if ( !empty($entityComponents) )
        {
            $componentPanel->setEntityComponentList($entityComponents);
        }

        if ( !empty($entityPositions) )
        {
            $componentPanel->setEntityPositionList($entityPositions);
        }

        if ( !empty($entitySettings) )
        {
            $componentPanel->setEntitySettingList($entitySettings);
        }

        $this->assign('componentPanel', $componentPanel->render());
    }

    public function create()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        OW::getDocument()->setHeading(OW::getLanguage()->text('groups', 'create_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_new');

        $language = OW::getLanguage();

        OW::getDocument()->setTitle($language->text('groups', 'create_page_title'));

        if ( !$this->service->isCurrentUserCanCreate() )
        {
            $this->assign('permissionMessage', OW::getLanguage()->text('groups', 'create_no_permission'));
            return;
        }

        $eventParams = array('pluginKey' => 'groups', 'action' => 'add_group');
        $credits = OW::getEventManager()->call('usercredits.check_balance', $eventParams);

        if ( $credits === false )
        {
            $this->assign('permissionMessage', OW::getEventManager()->call('usercredits.error_message', $eventParams));
            return;
        }

        $this->assign('permissionMessage', false);

        $form = new GROUPS_CreateGroupForm();

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $groupDto = $form->process();

            if ( empty($groupDto) )
            {
                $this->redirect();
            }

            $this->service->addUser($groupDto->id, OW::getUser()->getId());

            OW::getFeedback()->info($language->text('groups', 'create_success_msg'));
            $this->redirect($this->service->getGroupUrl($groupDto));
        }

        $this->addForm($form);
    }

    public function delete( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        $this->service->deleteGroup($params['groupId']);
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'delete_complete_msg'));

        $this->redirect(OW::getRouter()->urlForRoute('groups-index'));
    }

    public function edit( $params )
    {
        $groupId = (int) $params['groupId'];

        if ( empty($groupId) )
        {
            throw new Redirect404Exception();
        }

        $groupDto = $this->service->findGroupById($groupId);

        if ( !$this->service->isCurrentUserCanEdit($groupDto) )
        {
            throw new Redirect404Exception();
        }

        if ( $groupId === null )
        {
            throw new Redirect404Exception();
        }

        $form = new GROUPS_EditGroupForm($groupDto);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            if ( $form->process() )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('groups', 'edit_success_msg'));
            }
            $this->redirect();
        }

        $this->addForm($form);

        $this->assign('imageUrl', empty($groupDto->imageHash) ? false : $this->service->getGroupImageUrl($groupDto));

        $deleteUrl = OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'delete', array('groupId' => $groupDto->id));
        $viewUrl = $this->service->getGroupUrl($groupDto);
        $lang = OW::getLanguage()->text('groups', 'delete_confirm_msg');

        $js = UTIL_JsGenerator::newInstance();
        $js->newFunction('window.location.href=url', array('url'), 'redirect');
        $js->jQueryEvent('#groups-delete_btn', 'click', UTIL_JsGenerator::composeJsString(
                'if( confirm({$lang}) ) redirect({$url});', array('url' => $deleteUrl, 'lang' => $lang)));
        $js->jQueryEvent('#groups-back_btn', 'click', UTIL_JsGenerator::composeJsString(
                'redirect({$url});', array('url' => $viewUrl)));

        OW::getDocument()->addOnloadScript($js);
    }

    public function join( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $params['groupId'];
        $userId = OW::getUser()->getId();

        GROUPS_BOL_Service::getInstance()->addUser($groupId, $userId);

        $redirectUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId));
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'join_complete_message'));

        $this->redirect($redirectUrl);
    }

    public function leave( $params )
    {
        if ( empty($params['groupId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $params['groupId'];
        $userId = OW::getUser()->getId();

        GROUPS_BOL_Service::getInstance()->deleteUser($groupId, $userId);

        $redirectUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId));
        OW::getFeedback()->info(OW::getLanguage()->text('groups', 'leave_complete_message'));

        $this->redirect($redirectUrl);
    }

    private function getPaging( $page, $perPage, $onPage )
    {
        $paging['page'] = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $paging['perPage'] = $perPage;

        $paging['first'] = ($paging['perPage'] - 1) * $paging['perPage'];
        $paging['count'] = $paging['perPage'];
    }

    public function mostPopularList()
    {
        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'popular_list_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'popular_list_page_description'));

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findGroupList(GROUPS_BOL_Service::LIST_MOST_POPULAR, $first, $count);
        $listCount = $this->service->findGroupListCount(GROUPS_BOL_Service::LIST_MOST_POPULAR);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('popular')->setActive(true);

        $this->displayGroupList($dtoList, $paging, $menu);
    }

    public function latestList()
    {
        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'latest_list_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'latest_list_page_description'));

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findGroupList(GROUPS_BOL_Service::LIST_LATEST, $first, $count);
        $listCount = $this->service->findGroupListCount(GROUPS_BOL_Service::LIST_LATEST);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('latest')->setActive(true);

        $this->displayGroupList($dtoList, $paging, $menu);
    }

    public function inviteList()
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'invite_list_page_title'));

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findInvitedGroups($userId, $first, $count);
        $listCount = $this->service->findInvitedGroupsCount($userId);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('invite')->setActive(true);

        $this->displayGroupList($dtoList, $paging, $menu);
    }


    public function myGroupList()
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('groups', 'group_list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        OW::getDocument()->setTitle($language->text('groups', 'my_list_page_title'));

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findMyGroups($userId, $first, $count);
        $listCount = $this->service->findMyGroupsCount($userId);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $menu = $this->getGroupListMenu();
        $menu->getElement('my')->setActive(true);

        $this->displayGroupList($dtoList, $paging, $menu);
    }

    public function userGroupList( $params )
    {
        $userDto = BOL_UserService::getInstance()->findByUsername(trim($params['user']));

        if ( empty($userDto) )
        {
            throw new Redirect404Exception();
        }

        // privacy check
        $userId = $userDto->id;
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('groups');

        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => GROUPS_BOL_Service::PRIVACY_ACTION_VIEW_MY_GROUPS, 'ownerId' => $userId, 'viewerId' => $viewerId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);

            OW::getEventManager()->trigger($event);
        }

        $language = OW::getLanguage();
        OW::getDocument()->setTitle($language->text('groups', 'user_groups_page_title'));
        OW::getDocument()->setDescription($language->text('groups', 'user_groups_page_description'));
        OW::getDocument()->setHeading($language->text('groups', 'user_group_list_heading', array(
                'userName' => BOL_UserService::getInstance()->getDisplayName($userDto->id)
            )));

        OW::getDocument()->setHeadingIconClass('ow_ic_files');

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findUserGroupList($userDto->id, $first, $count);
        $listCount = $this->service->findUserGroupListCount($userDto->id);

        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 5);

        $this->assign('hideCreateNew', true);

        $this->displayGroupList($dtoList, $paging);
    }

    private function displayGroupList( $list, $paging, $menu = null, $template = 'groups_list' )
    {
        $templatePath = OW::getPluginManager()->getPlugin('groups')->getCtrlViewDir() . $template . '.html';
        $this->setTemplate($templatePath);

        $out = array();

        foreach ( $list as $item )
        {
            /* @var $item GROUPS_BOL_Group */

            $userCount = GROUPS_BOL_Service::getInstance()->findUserListCount($item->id);
            $out[] = array(
                'url' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $item->id)),
                'title' => strip_tags($item->title),
                'description' => strip_tags($item->description),
                'time' => UTIL_DateTime::formatDate($item->timeStamp),
                'image' => GROUPS_BOL_Service::getInstance()->getGroupImageUrl($item),
                'users' => $userCount,
                'toolbar' => array(array(
                        'label' => $userCount
                ))
            );
        }

        $this->addComponent('paging', $paging);

        if ( !empty($menu) )
        {
            $this->addComponent('menu', $menu);
        }
        else
        {
            $this->assign('menu', '');
        }

        $this->assign('canCreate', $this->service->isCurrentUserCanCreate());

        $this->assign('list', $out);
    }

    public function userList( $params )
    {
        $groupId = (int) $params['groupId'];
        $groupDto = $this->service->findGroupById($groupId);

        if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && !OW::getUser()->isAuthorized('groups') )
        {
            if ( !OW::getUser()->isAuthenticated() )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }

            $invite = $this->service->findInvite($groupDto->id, OW::getUser()->getId());
            $user = $this->service->findUser($groupDto->id, OW::getUser()->getId());

            if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && $invite === null && $user === null )
            {
                $this->redirect(OW::getRouter()->urlForRoute('groups-private-group', array(
                    'groupId' => $groupDto->id
                )));
            }
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findUserList($groupId, $first, $count);
        $listCount = $this->service->findUserListCount($groupId);

        $listCmp = new GROUPS_UserList($dtoList, $listCount, 20);
        $this->addComponent('listCmp', $listCmp);
        $this->addComponent('groupBriefInfo', new GROUPS_CMP_BriefInfo($groupId));
    }

    private function getGroupListMenu()
    {

        $language = OW::getLanguage();

        $items = array();

        $items[0] = new BASE_MenuItem();
        $items[0]->setLabel($language->text('groups', 'group_list_menu_item_popular'))
            ->setKey('popular')
            ->setUrl(OW::getRouter()->urlForRoute('groups-most-popular'))
            ->setOrder(1)
            ->setIconClass('ow_ic_comment');

        $items[1] = new BASE_MenuItem();
        $items[1]->setLabel($language->text('groups', 'group_list_menu_item_latest'))
            ->setKey('latest')
            ->setUrl(OW::getRouter()->urlForRoute('groups-latest'))
            ->setOrder(2)
            ->setIconClass('ow_ic_clock');


        if ( OW::getUser()->isAuthenticated() )
        {
            $items[2] = new BASE_MenuItem();
            $items[2]->setLabel($language->text('groups', 'group_list_menu_item_my'))
                ->setKey('my')
                ->setUrl(OW::getRouter()->urlForRoute('groups-my-list'))
                ->setOrder(3)
                ->setIconClass('ow_ic_files');

            $items[3] = new BASE_MenuItem();
            $items[3]->setLabel($language->text('groups', 'group_list_menu_item_invite'))
                ->setKey('invite')
                ->setUrl(OW::getRouter()->urlForRoute('groups-invite-list'))
                ->setOrder(4)
                ->setIconClass('ow_ic_bookmark');
        }

        return new BASE_CMP_ContentMenu($items);
    }

    public function follow()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupId = (int) $_GET['groupId'];

        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }

        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => GROUPS_BOL_Service::ENTITY_TYPE_GROUP,
            'feedId' => $groupId
        );

        $title = UTIL_String::truncate(strip_tags($groupDto->title), 100, '...');

        switch ( $_GET['command'] )
        {
            case 'follow':
                OW::getEventManager()->call('feed.add_follow', $eventParams);
                OW::getFeedback()->info(OW::getLanguage()->text('groups', 'feed_follow_complete_msg', array('groupTitle' => $title)));
                break;

            case 'unfollow':
                OW::getEventManager()->call('feed.remove_follow', $eventParams);
                OW::getFeedback()->info(OW::getLanguage()->text('groups', 'feed_unfollow_complete_msg', array('groupTitle' => $title)));
                break;
        }

        $this->redirect(OW_URL_HOME . $_GET['backUri']);
    }

    public function invite()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            throw new AuthenticateException();
        }

        $respoce = array();

        $userIds = json_decode($_POST['userIdList']);
        $groupId = $_POST['groupId'];
        $allIdList = json_decode($_POST['allIdList']);

        $group = $this->service->findGroupById($groupId);

        $count = 0;
        foreach ( $userIds as $uid )
        {
            $this->service->inviteUser($group->id, $uid, $userId);
            $event = new OW_Event('groups.invite_user', array(
                'userId' => $uid,
                'inviterId' => $userId,
                'groupId' => $group->id
            ));

            OW::getEventManager()->trigger($event);

            $count++;
        }

        $respoce['messageType'] = 'info';
        $respoce['message'] = OW::getLanguage()->text('groups', 'users_invite_success_message', array('count' => $count));
        $respoce['allIdList'] = array_diff($allIdList, $userIds);

        exit(json_encode($respoce));
    }

    public function privateGroup( $params )
    {
        $language = OW::getLanguage();

        $this->setPageTitle($language->text('groups', 'private_page_title'));
        $this->setPageHeading($language->text('groups', 'private_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_lock');

        $groupId = $params['groupId'];
        $group = $this->service->findGroupById($groupId);

        $avatarList = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($group->userId));
        $displayName = BOL_UserService::getInstance()->getDisplayName($group->userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($group->userId);

        $this->assign('group', $group);
        $this->assign('avatar', $avatarList[$group->userId]);
        $this->assign('displayName', $displayName);
        $this->assign('userUrl', $userUrl);
        $this->assign('creator', $language->text('groups', 'creator'));
    }
}

// Additional calsses

class GROUPS_UserList extends BASE_CMP_Users
{
    public function getFields( $userIdList )
    {
        $fields = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate');

        if ( $qBdate->onView )
            $qs[] = 'birthdate';

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex->onView )
            $qs[] = 'sex';

        $questionList = BOL_QuestionService::getInstance()->getQuestionData($userIdList, $qs);

        foreach ( $questionList as $uid => $question )
        {

            $fields[$uid] = array();

            $age = '';

            if ( !empty($question['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($question['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            }

            $sexValue = '';
            if ( !empty($question['sex']) )
            {
                $sex = $question['sex'];

                for ( $i = 0; $i < 31; $i++ )
                {
                    $val = pow(2, $i);
                    if ( (int) $sex & $val )
                    {
                        $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                    }
                }

                if ( !empty($sexValue) )
                {
                    $sexValue = substr($sexValue, 0, -2);
                }
            }

            if ( !empty($sexValue) && !empty($age) )
            {
                $fields[$uid][] = array(
                    'label' => '',
                    'value' => $sexValue . ' ' . $age
                );
            }
        }

        return $fields;
    }
}

class GROUPS_GroupForm extends Form
{

    public function __construct( $formName )
    {
        parent::__construct($formName);

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $language = OW::getLanguage();

        $field = new TextField('title');
        $field->setRequired(true);
        $field->setLabel($language->text('groups', 'create_field_title_label'));
        $this->addElement($field);

        $field = new WysiwygTextarea('description');
        $field->setLabel($language->text('groups', 'create_field_description_label'));
        $field->setRequired(true);
        $this->addElement($field);

        $field = new GROUPS_Image('image');
        $field->setLabel($language->text('groups', 'create_field_image_label'));
        $field->addValidator(new GROUPS_ImageValidator());
        $this->addElement($field);

        $whoCanView = new RadioField('whoCanView');
        $whoCanView->setRequired();
        $whoCanView->addOptions(
            array(
                GROUPS_BOL_Service::WCV_ANYONE => $language->text('groups', 'form_who_can_view_anybody'),
                GROUPS_BOL_Service::WCV_INVITE => $language->text('groups', 'form_who_can_view_invite')
            )
        );
        $whoCanView->setLabel($language->text('groups', 'form_who_can_view_label'));
        $this->addElement($whoCanView);

        $whoCanInvite = new RadioField('whoCanInvite');
        $whoCanInvite->setRequired();
        $whoCanInvite->addOptions(
            array(
                GROUPS_BOL_Service::WCI_PARTICIPANT => $language->text('groups', 'form_who_can_invite_participants'),
                GROUPS_BOL_Service::WCI_CREATOR => $language->text('groups', 'form_who_can_invite_creator')
            )
        );
        $whoCanInvite->setLabel($language->text('groups', 'form_who_can_invite_label'));
        $this->addElement($whoCanInvite);
    }

    /**
     *
     * @param GROUPS_BOL_Group $group
     * @return GROUPS_BOL_Group
     */
    public function processGroup( GROUPS_BOL_Group $group )
    {
        $values = $this->getValues();
        $service = GROUPS_BOL_Service::getInstance();

        if ( $values['image'] )
        {
            if ( !empty($group->imageHash) )
            {
                OW::getStorage()->removeFile($service->getGroupImagePath($group));
            }

            $group->imageHash = uniqid();
        }

        $group->title = strip_tags($values['title']);
        $values['description'] = UTIL_HtmlTag::stripJs($values['description']);
        $values['description'] = UTIL_HtmlTag::stripTags($values['description'], array('frame'), array(), true);

        $group->description = $values['description'];
        $group->whoCanInvite = $values['whoCanInvite'];
        $group->whoCanView = $values['whoCanView'];

        $service->saveGroup($group);

        $fileName = $service->getGroupImagePath($group);

        if ( !empty($values['image']) )
        {
            $this->saveImage($values['image'], $fileName);
        }

        $eventParams = array('pluginKey' => 'groups', 'action' => 'add_group');
        if ( OW::getEventManager()->call('usercredits.check_balance', $eventParams) === true )
        {
            OW::getEventManager()->call('usercredits.track_action', $eventParams);
        }

        $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');
        // Add forum group
        if ( $is_forum_connected )
        {
            $event = new OW_Event('forum.create_group', array('entity' => 'groups', 'name' => $group->title, 'description' => $group->description, 'entityId' => $group->getId()));
            OW::getEventManager()->trigger($event);
        }

        return $group;
    }

    protected function saveImage( $postFile, $resultPath )
    {
        $tmpDir = OW::getPluginManager()->getPlugin('groups')->getPluginFilesDir();
        $tmpFile = $tmpDir . uniqid('tmp_') . '.jpg';

        $image = new UTIL_Image($postFile['tmp_name']);
        $image->resizeImage(100, 100, true)
            ->saveImage($tmpFile);

        $realDir = dirname($resultPath);
        if ( !OW::getStorage()->isWritable($realDir) )
        {
            OW::getStorage()->mkdir($realDir);
        }

        try
        {
            OW::getStorage()->copyFile($tmpFile, $resultPath);
        }
        catch ( Exception $e )
        {

        }

        unlink($tmpFile);
    }

    public function process()
    {

    }
}

class GROUPS_CreateGroupForm extends GROUPS_GroupForm
{

    public function __construct()
    {
        parent::__construct('GROUPS_CreateGroupForm');

        $this->getElement('title')->addValidator(new GROUPS_UniqueValidator());

        $field = new Submit('save');
        $field->setValue(OW::getLanguage()->text('groups', 'create_submit_btn_label'));
        $this->addElement($field);
    }

    /**
     * (non-PHPdoc)
     * @see ow_plugins/groups/controllers/GROUPS_GroupForm#process()
     */
    public function process()
    {
        $groupDto = new GROUPS_BOL_Group();
        $groupDto->timeStamp = time();
        $groupDto->userId = OW::getUser()->getId();

        $event = new OW_Event(GROUPS_BOL_Service::EVENT_BEFORE_CREATE, array('groupId' => $groupDto->id), (array) $groupDto);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        foreach ( $data as $k => $v )
        {
            $groupDto->$k = $v;
        }

        $result = $this->processGroup($groupDto);
        if ( $result )
        {
            $event = new OW_Event(GROUPS_BOL_Service::EVENT_CREATE, array('groupId' => $groupDto->id));
            OW::getEventManager()->trigger($event);
        }

        return $result;
    }
}

class GROUPS_EditGroupForm extends GROUPS_GroupForm
{
    /**
     *
     * @var GROUPS_BOL_Group
     */
    private $groupDto;

    public function __construct( GROUPS_BOL_Group $group )
    {
        parent::__construct('GROUPS_EditGroupForm');

        $this->groupDto = $group;

        $this->getElement('title')->setValue($group->title);
        $this->getElement('title')->addValidator(new GROUPS_UniqueValidator($group->title));
        $this->getElement('description')->setValue($group->description);
        $this->getElement('whoCanView')->setValue($group->whoCanView);
        $this->getElement('whoCanInvite')->setValue($group->whoCanInvite);

        $field = new Submit('save');
        $field->setValue(OW::getLanguage()->text('groups', 'edit_submit_btn_label'));
        $this->addElement($field);
    }

    /**
     * (non-PHPdoc)
     * @see ow_plugins/groups/controllers/GROUPS_GroupForm#process()
     */
    public function process()
    {
        $result = $this->processGroup($this->groupDto);

        if ( $result )
        {
            $event = new OW_Event(GROUPS_BOL_Service::EVENT_EDIT, array('groupId' => $this->groupDto->id));
            OW::getEventManager()->trigger($event);
        }

        return $result;
    }
}

class GROUPS_ImageValidator extends OW_Validator
{

    public function __construct()
    {

    }

    /**
     * @see OW_Validator::isValid()
     *
     * @param mixed $value
     */
    public function isValid( $value )
    {
        if ( empty($value) )
        {
            return true;
        }

        $realName = $value['name'];
        $tmpName = $value['tmp_name'];

        switch ( false )
        {
            case is_uploaded_file($tmpName):
                $this->setErrorMessage(OW::getLanguage()->text('groups', 'errors_image_upload'));
                return false;

            case UTIL_File::validateImage($realName):
                $this->setErrorMessage(OW::getLanguage()->text('groups', 'errors_image_invalid'));
                return false;
        }

        return true;
    }
}

class GROUPS_Image extends FileField
{

    public function getValue()
    {
        return empty($_FILES[$this->getName()]['tmp_name']) ? null : $_FILES[$this->getName()];
    }
}

class GROUPS_UniqueValidator extends OW_Validator
{
    private $exception;

    public function __construct( $exception = null )
    {
        $this->setErrorMessage(OW::getLanguage()->text('groups', 'group_already_exists'));

        $this->exception = $exception;
    }

    public function isValid( $value )
    {
        if ( !empty($this->exception) && trim($this->exception) == trim($value) )
        {
            return true;
        }

        $dto = GROUPS_BOL_Service::getInstance()->findByTitle($value);

        if ( $dto === null )
        {
            return true;
        }

        return false;
    }
}
