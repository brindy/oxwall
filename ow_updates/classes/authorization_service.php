<?php

class UPDATE_AuthorizationService
{
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

    private function __construct()
    {
        $this->authorizationService = BOL_AuthorizationService::getInstance();
    }
    private $authorizationService;

//    public function isActionAuthorizedForUser( $userId, $groupName, $actionName = null, $ownerId = null )
//    {
//        return $this->authorizationService->isActionAuthorizedForUser($userId, $groupName, $actionName, $ownerId);
//    }
//
//    public function getModeratorList()
//    {
//        return $this->authorizationService->getModeratorList();
//    }
//
//    public function getGroupList( $excludeModerated = false )
//    {
//        return $this->authorizationService->getGroupList($excludeModerated);
//    }
//
//    public function getModeratorPermissionList()
//    {
//        return $this->authorizationService->getModeratorPermissionList();
//    }
//
//    public function saveModeratorPermissionList( array $perms )
//    {
//        $this->authorizationService->saveModeratorPermissionList($perms);
//    }
//
//    public function addModerator( $userId )
//    {
//        return $this->authorizationService->addModerator($userId);
//    }
//
//    public function giveAllPermissions( $moderatorId )
//    {
//        $this->authorizationService->giveAllPermissions($moderatorId);
//    }
//
//    public function addAdministrator( $userId )
//    {
//        $this->authorizationService->addAdministrator($userId);
//    }
//
//    public function deleteModerator( $moderatorId )
//    {
//        $this->authorizationService->deleteModerator($moderatorId);
//    }
//
//    public function getRoleList()
//    {
//        return $this->authorizationService->getRoleList();
//    }
//
//    public function getActionList()
//    {
//        return $this->authorizationService->getActionList();
//    }
//
//    public function getPermissionList()
//    {
//        return $this->authorizationService->getPermissionList();
//    }
//
//    public function savePermissionList( array $perms )//TODO check action available for guest
//    {
//        $this->authorizationService->savePermissionList($perms);
//    }
//
//    public function getDefaultRole()
//    {
//        return $this->authorizationService->getDefaultRole();
//    }
//
//    public function saveUserRole( $userId, $roleId )
//    {
//        $this->authorizationService->saveUserRole($userId, $roleId);
//    }
//
//    public function assignDefaultRoleToUser( $userId )
//    {
//        $this->authorizationService->assignDefaultRoleToUser($userId);
//    }
//
//    public function getGuestRoleId()
//    {
//        return $this->authorizationService->getGuestRoleId();
//    }
//
//    public function countUserByRoleId( $id )
//    {
//        return $this->authorizationService->countUserByRoleId($id);
//    }
//
//    public function reorderRoles( $list )
//    {
//        $this->authorizationService->reorderRoles($list);
//    }
//
//    public function findNonGuestRoleList()
//    {
//        return $this->authorizationService->findNonGuestRoleList();
//    }
//
//    public function deleteRoleById( $id )
//    {
//        $this->authorizationService->deleteRoleById($id);
//    }
//
//    public function addRole( $label )
//    {
//        $this->authorizationService->addRole($label);
//    }
//
//    public function saveModeratorPermission( $dto )
//    {
//        $this->authorizationService->saveModeratorPermission($dto);
//    }
//
//    public function deleteUserRolesByUserId( $userId )
//    {
//        $this->authorizationService->deleteUserRolesByUserId($userId);
//    }
//
//    public function deleteUserRole( $userId, $roleId )
//    {
//        $this->authorizationService->deleteUserRole($userId, $roleId);
//    }
//
//    public function getModeratorIdByUserId( $userId )
//    {
//        return $this->authorizationService->getModeratorIdByUserId($userId);
//    }
//
//    public function isModerator( $userId=null )
//    {
//        return $this->authorizationService->isModerator($userId);
//    }
//
//    public function findUserRoleList( $userId )
//    {
//        return $this->authorizationService->findUserRoleList($userId);
//    }
//
    public function findGroupIdByName( $name )
    {
        return $this->authorizationService->findGroupIdByName($name);
    }
//
//    public function findActionListByGroupId( $groupId )
//    {
//        return $this->authorizationService->findActionListByGroupId($groupId);
//    }
//
//    public function grantActionListToRole( BOL_AuthorizationRole $role, array $actions )
//    {
//        $this->authorizationService->grantActionListToRole($role, $actions);
//    }
//
//    /**
//     *
//     * @param int $id
//     * @return BOL_AuthorizationRole
//     */
//    public function getRoleById( $id )
//    {
//        return $this->authorizationService->getRoleById($id);
//    }
//
//    /**
//     *
//     * @param BOL_AuthorizationGroup $group
//     * @param array $labels ex.: array('en' => 'Colour', 'en-US' => 'Color')
//     */
//    public function addGroup( BOL_AuthorizationGroup $group, array $labels )
//    {
//        $this->authorizationService->addGroup($group, $labels);
//    }
//
//    public function deleteGroup( $groupName )
//    {
//        $this->authorizationService->deleteGroup($groupName);
//    }
//
//    public function findAdminIdList()
//    {
//        return $this->authorizationService->findAdminIdList();
//    }

    /**
     *
     * @param BOL_AuthorizationAction $action
     * @param array $labels ex.: array('en' => 'Colour', 'en-US' => 'Color')
     */
    public function addAction( BOL_AuthorizationAction $action, array $labels )
    {
        $this->authorizationService->addAction($action, $labels);
    }

    public function deleteAction( $actionId )
    {
        $this->authorizationService->addAction($action, $labels);
    }
}