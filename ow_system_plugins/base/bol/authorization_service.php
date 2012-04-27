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
 * Authorization Service
 *
 * @author Nurlan Dzhumakaliev <nurlanj@live.com>, Aybat Duyshokov <duyshokov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_AuthorizationService
{
    const ADMIN_GROUP_NAME = 'admin';
    const ON_BEFORE_ROLE_DELETE = 'base.on_before_role_delete';
    const ON_AFTER_ROLE_DELETE = 'base.on_after_role_delete';

    /**
     * @var BOL_AuthorizationRoleDao
     */
    private $roleDao;
    /**
     * @var BOL_AuthorizationUserRoleDao
     */
    private $userRoleDao;
    /**
     * @var BOL_AuthorizationActionDao
     */
    private $actionDao;
    /**
     * @var BOL_AuthorizationGroupDao
     */
    private $groupDao;
    /**
     * @var BOL_AuthorizationPermissionDao
     */
    private $permissionDao;
    /**
     * @var BOL_AuthorizationModeratorDao
     */
    private $moderatorDao;
    /**
     * @var BOL_AuthorizationModeratorPermissionDao
     */
    private $moderatorPermissionDao;

    private function __construct()
    {
        $this->roleDao = BOL_AuthorizationRoleDao::getInstance();
        $this->userRoleDao = BOL_AuthorizationUserRoleDao::getInstance();
        $this->actionDao = BOL_AuthorizationActionDao::getInstance();
        $this->groupDao = BOL_AuthorizationGroupDao::getInstance();
        $this->permissionDao = BOL_AuthorizationPermissionDao::getInstance();
        $this->moderatorDao = BOL_AuthorizationModeratorDao::getInstance();
        $this->moderatorPermissionDao = BOL_AuthorizationModeratorPermissionDao::getInstance();

        $this->readCachedItems();
    }
    /**
     * Singleton instance.
     *
     * @var BOL_AuthorizationService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_AuthorizationService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    private $groupCache;
    private $moderatorCache;
    private $moderatorPermissionCache;
    private $actionCache;
    private $permissionCache;
    private $guestRoleId;
    private $userRolesCache;
    private $superModeratorUserId;

    private function readCachedItems()
    {
        if ( $this->groupCache === null )
        {
            $this->groupCache = array();
            $groups = $this->groupDao->findAll();
            foreach ( $groups as $group )
            {
                /* @var $group BOL_AuthorizationGroup */
                $this->groupCache[$group->name] = $group->id;
            }
        }

        if ( $this->moderatorCache === null )
        {
            $this->moderatorCache = array();
            $moderators = $this->moderatorDao->findAll();
            $this->superModeratorUserId = 0;

            foreach ( $moderators as $moderator )
            {
                /* @var $moderator BOL_AuthorizationModerator */
                $this->moderatorCache[$moderator->userId] = $moderator->id;

                if ( $this->superModeratorUserId === 0
                    || (int)$this->moderatorCache[$moderator->userId] < (int)$this->moderatorCache[$this->superModeratorUserId] )
                {
                    $this->superModeratorUserId = (int)$moderator->userId;
                }
            }
        }

        if ( $this->moderatorPermissionCache === null )
        {
            $this->moderatorPermissionCache = array();
            $modPerms = $this->moderatorPermissionDao->findAll();
            foreach ( $modPerms as $perm )
            {
                /* @var $perm BOL_AuthorizationModeratorPermission */
                $this->moderatorPermissionCache[$perm->moderatorId][$perm->groupId] = $perm->id;
            }
        }

        if ( $this->guestRoleId === null )
        {
            $this->guestRoleId = $this->roleDao->getGuestRoleId();
        }

        if ( $this->actionCache === null )
        {
            $this->actionCache = array();
            $actions = $this->actionDao->findAll();
            foreach ( $actions as $action )
            {
                /* @var $action BOL_AuthorizationAction */
                $this->actionCache[$action->name][$action->groupId] = $action->id;
            }
        }

        if ( $this->userRolesCache === null )
        {
            $this->userRolesCache = array();
            if ( OW::getUser()->isAuthenticated() )
            {
                $userId = OW::getUser()->getId();
                $this->userRolesCache[$userId] = $this->userRoleDao->getRoleIdList($userId);
            }
        }

        if ( $this->permissionCache === null )
        {
            $this->permissionCache = array();
            $permissions = $this->permissionDao->findAll();
            foreach ( $permissions as $permission )
            {
                /* @var $permission BOL_AuthorizationPermission */
                $this->permissionCache[$permission->actionId][$permission->roleId] = $permission->id;
            }
        }
    }

    /**
     * @param $groupName
     * @param $action
     * @param int $ownerId
     *
     * @return boolean
     */
    public function isActionAuthorized( $groupName, $actionName = null, $ownerId = null )
    {
        return $this->isActionAuthorizedForUser(0, $groupName, $actionName, $ownerId);
    }

    public function isActionAuthorizedForUser( $userId, $groupName, $actionName = null, $ownerId = null )
    {
        $userId = (int) $userId;

        if ( isset($this->groupCache[$groupName]) )
        {
            $groupId = $this->groupCache[$groupName];
        }
        else
        {
            return false;
        }

        // contains user's role ids
        $roles = array();

        if ( $userId > 0 || OW::getUser()->isAuthenticated() )
        {
            $userId = ( $userId > 0 ) ? $userId : OW::getUser()->getId();

            if ( $actionName === null )
            {
                if ( isset($this->moderatorCache[$userId]) )
                {
                    $moderatorId = $this->moderatorCache[$userId];
                    $adminGroupId = $this->groupCache[self::ADMIN_GROUP_NAME];

                    return isset($this->moderatorPermissionCache[$moderatorId][$groupId])
//                        || isset($this->moderatorPermissionCache[$moderatorId][$adminGroupId])
                        || $this->isSuperModerator($userId);
                }
                else
                {
                    return false;
                }
            }

            if ( $ownerId !== null && (int) $ownerId !== $userId )
            {
                return false;
            }

            if ( !array_key_exists($userId, $this->userRolesCache) )
            {
                $this->userRolesCache[$userId] = $this->userRoleDao->getRoleIdList($userId);
            }
            $roles = $this->userRolesCache[$userId];
        }
        else
        {
            $roles[] = $this->guestRoleId;
        }

        if ( isset($this->actionCache[$actionName][$groupId]) )
        {
            $actionId = $this->actionCache[$actionName][$groupId];
        }
        else
        {
            return false;
        }

        $permissionId = null;

        foreach ( $roles as $role )
        {
            if ( isset($this->permissionCache[$actionId][$role]) )
            {
                $permissionId = $this->permissionCache[$actionId][$role];
                break;
            }
        }

        return ( $permissionId !== null /* && (int)$permissionId > 0 */ );
    }

    public function getModeratorList()
    {
        return $this->moderatorDao->findAll();
    }

    public function getGroupList( $excludeNonModerated = false )
    {
        $groups = $this->groupDao->findAll();
        
        foreach ( $groups as $key => $value )
        {
            /* @var $value BOL_AuthorizationGroup */
            if ( $excludeNonModerated && !$value->isModerated() )
            {
                unset($groups[$key]);
            }
        }

        return $groups;
    }

    public function getModeratorPermissionList()
    {
        return $this->moderatorPermissionDao->findAll();
    }

    public function saveModeratorPermissionList( array $perms, $userId )
    {
        $moderatorId = $this->getModeratorIdByUserId($userId);

        if ( $moderatorId === null )
        {
            return;
        }

        $isSuperAdmin = $this->isSuperModerator($userId);

        $superModeratorId = (int)$this->getModeratorIdByUserId($this->getSuperModeratorUserId());

        $adminGroupId = $this->getAdminGroupId();

        // delete old
        $oldPerms = $this->getModeratorPermissionList();
        foreach ( $oldPerms as $perm )
        {
            /* @var $perm BOL_AuthorizationModeratorPermission */

            if ( (int)$perm->getGroupId() === $adminGroupId && !$isSuperAdmin )
            {
                continue;
            }

            if ( (int)$perm->getModeratorId() === $superModeratorId )
            {
                continue;
            }

            $match = false;
            foreach ( $perms as $value )
            {
                /* @var $value BOL_AuthorizationModeratorPermission */
                if ( (int)$value->groupId === (int)$perm->groupId && (int)$value->moderatorId === (int)$perm->moderatorId )
                {
                    $match = true;
                    break;
                }
            }

            if ( !$match )
            {
                $this->moderatorPermissionDao->delete($perm);
            }
        }

        // add new
        foreach ( $perms as $perm )
        {
            /* @var $perm BOL_AuthorizationModeratorPermission */

            if ( (int)$perm->getGroupId() === $adminGroupId && !$isSuperAdmin )
            {
                continue;
            }

            if ( (int)$perm->getModeratorId() === $superModeratorId )
            {
                continue;
            }
            
            $oldPermId = $this->moderatorPermissionDao->getId($perm->getModeratorId(), $perm->getGroupId());
            if ( $oldPermId !== null )
            {
                $perm->setId($oldPermId);
            }

            $this->moderatorPermissionDao->save($perm);
        }
    }

    public function addModerator( $userId )
    {
        $userId = (int) $userId;

        if ( $this->moderatorDao->getIdByUserId($userId) !== null )
        {
            return false;
        }

        $moder = new BOL_AuthorizationModerator();
        $moder->userId = $userId;
        $this->moderatorDao->save($moder);

        $this->giveAllPermissions($moder->getId());

        return true;
    }

    public function giveAllPermissions( $moderatorId )
    {
        $this->moderatorPermissionDao->deleteByModeratorId($moderatorId);

        $groups = $this->getGroupList(true);

        $adminGroupId = $this->getAdminGroupId();

        foreach ( $groups as $group )
        {
            if ( (int)$group->getId() === (int)$adminGroupId )
            {
                continue;
            }
            
            $permisson = new BOL_AuthorizationModeratorPermission();
            $permisson->setGroupId($group->getId())->setModeratorId($moderatorId);

            $this->saveModeratorPermission($permisson);
        }
    }

    public function addAdministrator( $userId )
    {
        $this->addModerator($userId);

        $moderatorId = $this->moderatorDao->getIdByUserId($userId);

        $groupId = $this->getAdminGroupId();

        $permisson = new BOL_AuthorizationModeratorPermission();
        $permisson->setGroupId($groupId)
            ->setModeratorId($moderatorId);

        $this->saveModeratorPermission($permisson);
    }

    public function deleteModerator( $moderatorId )
    {
        $moderatorId = (int) $moderatorId;

        $adminGroupId = $this->getAdminGroupId();
        if ( $this->moderatorPermissionDao->getId($moderatorId, $adminGroupId) !== null )
        {
            return false;
        }

        $removed = ( $this->moderatorDao->deleteById($moderatorId) > 0 ); // ? true : false;

        if ( $removed )
        {
            $this->moderatorPermissionDao->deleteByModeratorId($moderatorId);
        }

        return $removed;
    }

    public function getRoleList()
    {
        return $this->roleDao->findAll();
    }

    public function getActionList()
    {
        return $this->actionDao->findAll();
    }

    public function getPermissionList()
    {
        return $this->permissionDao->findAll();
    }

    public function savePermissionList( array $perms )//TODO check action available for guest
    {
        $this->permissionDao->deleteAll();
        foreach ( $perms as $perm )
        {
            $this->permissionDao->save($perm);
        }
    }

    public function getDefaultRole()
    {
        return $this->roleDao->findDefault();
    }

    public function saveUserRole( $userId, $roleId )
    {
        $userRole = new BOL_AuthorizationUserRole();
        $userRole->userId = (int) $userId;
        $userRole->roleId = (int) $roleId;

        $this->userRoleDao->save($userRole);
    }

    public function assignDefaultRoleToUser( $userId )
    {
        $role = $this->getDefaultRole();
        $this->saveUserRole((int) $userId, $role->id);
    }

    public function getGuestRoleId()
    {
        return $this->roleDao->getGuestRoleId();
    }

    public function countUserByRoleId( $id )
    {
        return $this->userRoleDao->countByRoleId($id);
    }

    public function reorderRoles( $list )
    {
        $idList = array_keys($list);

        $roles = $this->roleDao->findByIdList($idList);

        foreach ( $roles as $role )
        {
            /* @var $role BOL_AuthorizationRole */
            $this->roleDao->save($role->setSortOrder($list[$role->getId()]));
        }
    }

    public function findNonGuestRoleList()
    {
        return $this->roleDao->findNonGuestRoleList();
    }

    public function deleteRoleById( $id )
    {
        $role = $this->roleDao->findById($id);

        $eventBefore = new OW_Event(self::ON_BEFORE_ROLE_DELETE, array('roleId' => $role->getId()));
        OW::getEventManager()->trigger($eventBefore);

        $languageService = BOL_LanguageService::getInstance();

        $key = $languageService->findKey('base', "authorization_role_{$role->getName()}");

        if ( !empty($key) )
        {
            $languageService->deleteKey($key->getId());
        }

        $this->userRoleDao->onDeleteRole($role->getId(), $this->getDefaultRole()->getId());

        $this->roleDao->deleteById($role->getId());
        //TODO delete from Permission

        $eventAfter = new OW_Event(self::ON_AFTER_ROLE_DELETE, array('roleId' => $role->getId()));
        OW::getEventManager()->trigger($eventAfter);
    }

    public function addRole( $label )
    {
        $languageService = BOL_LanguageService::getInstance();

        $i = 0;
        $name = $languageService->generateCustomKey($label);

        $unique = "authorization_role_{$name}";

        while ( !$languageService->isKeyUnique('base', $unique) )
        {
            $i++;

            $unique = "authorization_role_{$name}" . $i;
        }
        if ( $i > 0 )
        {
            $name .= $i;
        }

        $key = $unique;

        $role = new BOL_AuthorizationRole();
        $role->setName($name);
        $role->setSortOrder($this->roleDao->findMaxOrder() + 1);

        $this->roleDao->save($role);

        $languageService->addValue($languageService->getCurrent()->getId(), 'base', $key, $label, true);
    }

    public function saveRole( BOL_AuthorizationRole $role )
    {
        $this->roleDao->save($role);
    }

    public function saveModeratorPermission( $dto )
    {
        $this->moderatorPermissionDao->save($dto);
    }

    public function deleteUserRolesByUserId( $userId )
    {
        $userId = (int) $userId;
        if ( $userId > 0 )
        {
            $this->userRoleDao->deleteByUserId($userId);
        }
    }

    public function deleteUserRole( $userId, $roleId )
    {
        $userId = (int) $userId;
        $roleId = (int) $roleId;

        if ( $userId > 0 && $roleId > 0 )
        {
            $this->userRoleDao->deleteUserRole($userId, $roleId);
        }
    }

    public function getModeratorIdByUserId( $userId )
    {
        $userId = (int) $userId;
        if ( $userId > 0 )
        {
            return $this->moderatorDao->getIdByUserId($userId);
        }

        return null;
    }

    public function isModerator( $userId=null )//TODO rewrite this method
    {
        if ( $userId == null && ( $userId = OW::getUser()->getId() ) === null )
        {
            return false;
        }

        return (bool) $this->getModeratorIdByUserId($userId);
    }

    public function findUserRoleList( $userId )
    {
        return $this->roleDao->findUserRoleList($userId);
    }

    public function findGroupIdByName( $name )
    {
        $id = $this->groupDao->getIdByName($name);

        return ( $id === null ) ? 0 : (int) $id;
    }

    public function findActionListByGroupId( $groupId )
    {
        if ( $groupId === null || $groupId < 1 )
        {
            return array();
        }

        return $this->actionDao->findActionListByGroupId($groupId);
    }

    public function grantActionListToRole( BOL_AuthorizationRole $role, array $actions )
    {
        if ( $role === null || empty($actions) )
        {
            return;
        }

        /* @var $action BOL_AuthorizationAction */
        foreach ( $actions as $action )
        {
            if ( !$action->isAvailableForGuest() && ( (int) $role->id === (int) $this->guestRoleId ) )
            {
                continue;
            }

            $perm = $this->permissionDao->findByRoleIdAndActionId($role->id, $action->id);
            if ( $perm === null )
            {
                $perm = new BOL_AuthorizationPermission();
                $perm->actionId = $action->id;
                $perm->roleId = $role->id;
                $this->permissionDao->save($perm);
            }
        }
    }

    /**
     *
     * @param int $id
     * @return BOL_AuthorizationRole
     */
    public function getRoleById( $id )
    {
        $id = (int) $id;
        if ( $id < 1 )
        {
            throw new InvalidArgumentException('invalid role id');
        }

        return $this->roleDao->findById($id);
    }

    /**
     *
     * @param BOL_AuthorizationGroup $group
     * @param array $labels ex.: array('en' => 'Colour', 'en-US' => 'Color')
     */
    public function addGroup( BOL_AuthorizationGroup $group, array $labels )
    {
        if ( $group === null )
        {
            throw new InvalidArgumentException('$group cannot be null');
        }

        $this->groupDao->save($group);

        foreach ( $labels as $tag => $label )
        {
            $lang = BOL_LanguageService::getInstance()->findByTag($tag);
            if ( $lang !== null )
            {
                BOL_LanguageService::getInstance()
                    ->addValue($lang->id, 'base', 'authorization_group_' . strtolower($group->name), $label);
            }
        }

        if ( $group->isModerated() )
        {
            $adminIds = $this->findAdminIdList();
            if ( count($adminIds) > 0 )
            {
                foreach ( $adminIds as $adminId )
                {
                    $perm = new BOL_AuthorizationModeratorPermission();
                    $perm->groupId = $group->id;
                    $perm->moderatorId = $adminId;
                    $this->moderatorPermissionDao->save($perm);
                }
            }
        }
    }

    public function deleteGroup( $groupName )
    {
        $group = $this->groupDao->findByName($groupName);

        if ( $group !== null )
        {
            if ( $group->isModerated() )
            {
                $this->moderatorPermissionDao->deleteByGroupId($group->id);
            }
            $actions = $this->actionDao->findActionListByGroupId($group->id);

            if ( !empty($actions) )
            {
                foreach ( $actions as $action )
                {
                    $this->deleteAction($action->id);
                }
            }

//            $key = BOL_LanguageService::getInstance()->findKey('base', 'authorization_group_' . strtolower($group->name));
//
//            if ( $key !== null )
//            {
//                BOL_LanguageService::getInstance()->deleteKey($key->getId(), true);
//            }

            $this->groupDao->deleteById($group->id);
        }
    }

    public function findAdminIdList()
    {
        $adminGroupId = $this->getAdminGroupId();
        $moderPerms = $this->moderatorPermissionDao->findListByGroupId($adminGroupId);
        $adminIds = array();
        foreach ( $moderPerms as $perm )
        {
            /* @var $perm BOL_AuthorizationModeratorPermission */
            $adminIds[] = $perm->moderatorId;
        }

        return $adminIds;
    }

    /**
     *
     * @param BOL_AuthorizationAction $action
     * @param array $labels ex.: array('en' => 'Colour', 'en-US' => 'Color')
     */
    public function addAction( BOL_AuthorizationAction $action, array $labels )
    {
        if ( $action === null )
        {
            throw new InvalidArgumentException('action cannot be null');
        }
        $this->actionDao->save($action);

        $group = $this->groupDao->findById($action->groupId);
        foreach ( $labels as $tag => $label )
        {
            $lang = BOL_LanguageService::getInstance()->findByTag($tag);
            if ( $lang !== null )
            {
                $key = 'authorization_action_' . strtolower($group->name) . '_' . strtolower($action->name);
                try
                {
                    BOL_LanguageService::getInstance()->addValue($lang->id, 'base', $key, $label);
                }
                catch ( Exception $e )
                {
                    
                }
            }
        }

        $roles = $this->getRoleList();
        foreach ( $roles as $role )
        {
            $this->grantActionListToRole($role, array($action));
        }
    }

    /**
     * 
     * @param $groupName
     * @param $actionName
     * @return BOL_AuthorizationAction
     */
    public function findAction( $groupName, $actionName )
    {
        $groupDto = $this->groupDao->findByName($groupName);

        if ( $groupDto === null )
        {
            return null;
        }

        return $this->actionDao->findAction($actionName, $groupDto->id);
    }

    public function deleteAction( $actionId )
    {
        $actionId = (int) $actionId;
        if ( $actionId > 0 )
        {
            $this->permissionDao->deleteByActionId($actionId);

            //begin delete lang
//            $action = $this->actionDao->findById($actionId);
//            $group = $this->groupDao->findById($action->groupId);
//            $key = 'authorization_action_' . strtolower($group->name) . '_' . strtolower($action->name);
//            $keyObj = BOL_LanguageService::getInstance()->findKey('base', $key);
//
//            if ( $keyObj !== null )
//            {
//                BOL_LanguageService::getInstance()->deleteKey($keyObj->id, true);
//            }
            //end delete lang

            $this->actionDao->deleteById($actionId);
        }
    }

    public function getRoleListOfUsers( array $userIds )
    {
        $roles = $this->userRoleDao->getRoleListOfUsers($userIds);
        $keyRoles = array();
        foreach ( $roles as $key => &$role )
        {
            $keyRoles[$role['userId']] = &$role;
            $role['label'] = $this->getRoleLabel($role['name']);
        }

        return $keyRoles;
    }

    public function getRoleLabel( $roleName )
    {
        return OW::getLanguage()->text('base', "authorization_role_{$roleName}");
    }

    public function saveGroup( BOL_AuthorizationGroup $group )
    {
        $this->groupDao->save($group);
    }

    public function saveAction( BOL_AuthorizationAction $action )
    {
        $this->actionDao->save($action);
    }

    public function deleteGroupByName( $groupName )
    {
        $group = $this->groupDao->findByName($groupName);

        if ( $group !== null )
        {
            if ( $group->isModerated() )
            {
                $this->moderatorPermissionDao->deleteByGroupId($group->id);
            }
            $actions = $this->actionDao->findActionListByGroupId($group->id);

            if ( !empty($actions) )
            {
                foreach ( $actions as $action )
                {
                    $this->deleteActionById($action->id);
                }
            }

            $this->groupDao->deleteById($group->id);
        }
    }

    public function deleteActionById( $actionId )
    {
        $actionId = (int) $actionId;
        
        if ( $actionId > 0 )
        {
            $this->permissionDao->deleteByActionId($actionId);
            $this->actionDao->deleteById($actionId);
        }
    }

    /**
     * @param string
     * @return BOL_AuthorizationGroup $groupName
     */
    public function findGroupByName( $groupName )
    {
        return $this->groupDao->findByName($groupName);
    }

    public function isSuperModerator( $userId )
    {
        return (int)$userId === (int)$this->superModeratorUserId;
    }

    public function getSuperModeratorUserId()
    {
        return $this->superModeratorUserId;
    }

    public function getAdminGroupId()
    {
        return (int)$this->findGroupIdByName(self::ADMIN_GROUP_NAME);
    }
}
