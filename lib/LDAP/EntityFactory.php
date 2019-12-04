<?php
namespace OCA\LdapContacts\LDAP;

use OCP\ILogger;
use OCA\LdapContacts\Controller\SettingsController;
use OCA\LdapContacts\Exceptions\LdapEntityNotFoundException;
use OCA\LdapContacts\Exceptions\LdapEntityUnknownException;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Mapping\GroupMapping;
use OC\ServerNotAvailableException;

class EntityFactory {
  /** @var Helper **/
  protected $ldapHelper;
  /** @var ILDAPWrapper **/
  protected $ldapWrapper;
  /** @var AccessFactory **/
  protected $accessFactory;
  /** @var UserMapping **/
  protected $ldapUserMapping;
  /** @var GroupMapping **/
  protected $ldapGroupMapping;
  /** @var SettingsController **/
  protected $settings;
  /** @var ILogger **/
  protected $log;
  /** @var Access[] **/
  private $establishedConnections = [];
	/** @var GroupPluginManager */
	protected $groupPluginManager;
  /** @var LdapWrite **/
  protected $ldap;

  const UserEntity = 'user';
  const GroupEntity = 'group';

  /**
  * @param Helper $ldapHelper
  * @param ILDAPWrapper $ldapWrapper
  * @param AccessFactory $accessFactory
  * @param UserMapping $ldapUserMapping
  * @param GroupMapping $ldapGroupMapping
  * @param SettingsController $settings
  * @param GroupPluginManager $groupPluginManager
  * @param LdapWrite $ldap
  */
  public function __construct(Helper $ldapHelper,
															ILDAPWrapper $ldapWrapper,
															AccessFactory $accessFactory,
															UserMapping $ldapUserMapping,
															GroupMapping $ldapGroupMapping,
                              SettingsController $settings,
                              ILogger $logger,
                              GroupPluginManager $groupPluginManager,
                              LdapWrite $ldap) {
    $this->ldapHelper = $ldapHelper;
    $this->ldapWrapper = $ldapWrapper;
    $this->accessFactory = $accessFactory;
    $this->ldapUserMapping = $ldapUserMapping;
    $this->ldapGroupMapping = $ldapGroupMapping;
    $this->settings = $settings;
    $this->log = $logger;
    $this->groupPluginManager = $groupPluginManager;
    $this->ldap = $ldap;
  }

  /**
   * @param string $uuid
   * @return LdapUser
   * @throws LdapEntityNotFoundException
   */
  public function getUserByUuid(string $uuid) {
    try { return $this->getEntityByUuid($uuid, EntityFactory::UserEntity); }
    catch (LdapEntityNotFoundException $e) { throw $e; }
  }

  /**
   * @param string $userId
   * @return LdapUser
   * @throws LdapEntityNotFoundException
   */
  public function getUserByNcId(string $userId) {
    try { return $this->getEntityByNcId($userId, EntityFactory::UserEntity); }
    catch (LdapEntityNotFoundException $e) { throw $e; }
  }

  /**
   * @param string $userDn
   * @return LdapUser
   * @throws LdapEntityNotFoundException
   */
  public function getUserByDn(string $userDn) {
    try { return $this->getEntityByDn($userDn, EntityFactory::UserEntity); }
    catch (LdapEntityNotFoundException $e) { throw $e; }
  }

  /**
   * @param string $uuid
   * @return LdapGroup
   * @throws LdapEntityNotFoundException
   */
  public function getGroupByUuid(string $uuid) {
    try { return $this->getEntityByUuid($uuid, EntityFactory::GroupEntity); }
    catch (LdapEntityNotFoundException $e) { throw $e; }
  }

  /**
   * @param string $groupId
   * @return LdapGroup
   * @throws LdapEntityNotFoundException
   */
  public function getGroupByNcId(string $groupId) {
    try { return $this->getEntityByNcId($groupId, EntityFactory::GroupEntity); }
    catch (LdapEntityNotFoundException $e) { throw $e; }
  }

  /**
   * @param string $groupDn
   * @return LdapGroup
   * @throws LdapEntityNotFoundException
   */
  public function getGroupByDn(string $groupDn) {
    try { return $this->getEntityByDn($groupDn, EntityFactory::GroupEntity); }
    catch (LdapEntityNotFoundException $e) { throw $e; }
  }

  /**
   * @param string $entityUuid
   * @param string $entityType
   * @throws LdapEntityNotFoundException
   */
  private function getEntityByUuid(string $entityUuid, string $entityType) {
    // try to find the entity
    foreach ($this->getAvailableServerList() as $server) {
      $entityMapper = $entityType === EntityFactory::UserEntity ? $server->getUserMapper() : $server->getGroupMapper();

      $entityNcId = $entityMapper->getNameByUUID($entityUuid);
      // if the user wasn't found on this server, check the next one
      if ($entityNcId === false) continue;

      $entityDn = EntityFactory::getEntityDnByNcId($entityNcId, $entityType, $server);
      // if the dn couldn't be determined, try the next server
      if ($entityDn === false) continue;

      return $this->createLdapEntity($entityNcId, $entityDn, $entityUuid, $server, $entityType);
    }

    // the entity couldn't be found
    switch ($entityType) {
      case EntityFactory::UserEntity:
        $e = new LdapEntityNotFoundException("The user with the following UUID couldn't be found: " . $entityUuid);
        break;
      case EntityFactory::GroupEntity:
        $e = new LdapEntityNotFoundException("The group with the following UUID couldn't be found: " . $entityUuid);
        break;
      default:
        $e = new LdapEntityNotFoundException("The entity with the following UUID couldn't be found: " . $entityUuid);
        break;
    }
    $this->log->logException($e);
    throw $e;
  }

  /**
   * @param string $entityId
   * @param string $entityType
   * @return LdapEntity
   */
  private function getEntityByNcId(string $entityNcId, string $entityType) {
    // try to find the entity
    foreach ($this->getAvailableServerList() as $server) {
      $entityDn = EntityFactory::getEntityDnByNcId($entityNcId, $entityType, $server);
      // if the user wasn't found on this server, check the next one
      if ($entityDn === false) continue;

      $entityUuid = EntityFactory::getEntityUuidByDn($entityDn, $entityType, $server);
      // if the uuid couldn't be determined, try the next server
      if ($entityUuid === false) continue;

      return $this->createLdapEntity($entityNcId, $entityDn, $entityUuid, $server, $entityType);
    }

    // the entity couldn't be found
    switch ($entityType) {
      case EntityFactory::UserEntity:
        $e = new LdapEntityNotFoundException("The user with the following Nextcloud ID couldn't be found: " . $entityNcId);
      case EntityFactory::GroupEntity:
        $e = new LdapEntityNotFoundException("The group with the following Nextcloud ID couldn't be found: " . $entityNcId);
      default:
        $e = new LdapEntityNotFoundException("The entity with the following Nextcloud ID couldn't be found: " . $entityNcId);
    }
    $this->log->logException($e);
    $e = $e;
  }

  /**
   * @param string $entityDn
   * @param string $entityType
   * @return LdapEntity
   */
  private function getEntityByDn(string $entityDn, string $entityType) {
    // try to find the entity
    foreach ($this->getAvailableServerList() as $server) {
      $entityNcId = EntityFactory::getEntityNcIdByDn($entityDn, $entityType, $server);
      // if the user wasn't found on this server, check the next one
      if ($entityDn === false) continue;

      $entityUuid = EntityFactory::getEntityUuidByDn($entityDn, $entityType, $server);
      // if the uuid couldn't be determined, try the next server
      if ($entityUuid === false) continue;

      return $this->createLdapEntity($entityNcId, $entityDn, $entityUuid, $server, $entityType);
    }

    // the entity couldn't be found
    switch ($entityType) {
      case EntityFactory::UserEntity:
        $e = new LdapEntityNotFoundException("The user with the following DN couldn't be found: " . $entityDn);
      case EntityFactory::GroupEntity:
        $e = new LdapEntityNotFoundException("The group with the following DN couldn't be found: " . $entityDn);
      default:
        $e = new LdapEntityNotFoundException("The entity with the following DN couldn't be found: " . $entityDn);
    }
  }

  /**
   * returns a list of all available ldap users
   *
   * @return LdapUser[]
   */
  public function getAllUsers() {
    $userList = [];

    // get all users from all servers
    foreach ($this->getAvailableServerList() as $server) {
      $serverUserList = $server->fetchListOfUsers($server->connection->ldapUserFilter, 'dn');

      // loop through all users
      foreach ($serverUserList as $dn) {
        try {
          $user = $this->getUserByDn($dn);
        }
        catch (LdapEntityNotFoundException $e) {
          // the user couldn't be found, so skip him
          continue;
        }

        $userList[] = $user;
      }
    }

    return $userList;
  }

  /**
   * returns a list of all available ldap groups
   *
   * @return LdapGroup[]
   */
  public function getAllGroups() {
    $groupList = [];

    // get all groups from all servers
    foreach ($this->getAvailableServerList() as $server) {
      $serverGroupList = $server->fetchListOfGroups($server->connection->ldapGroupFilter, 'dn');
      // loop through all groups
      foreach ($serverGroupList as $dn) {
        try {
          $group = $this->getGroupByDn($dn);
        }
        catch (LdapEntityNotFoundException $e) {
          // the group couldn't be found, so skip it
          continue;
        }

        $groupList[] = $group;
      }
    }

    return $groupList;
  }

  /**
   * @return AccessFactory[]
   */
  protected function getAvailableServerList() {
    $serverList = [];

    /** establish ldap connections for each defined server **/
		$ldapServerPrefixList = $this->ldapHelper->getServerConfigurationPrefixes(true);
		foreach ($ldapServerPrefixList as $prefix) {
      // check if this connection already exists
      if (isset($this->establishedConnections[ $prefix ])) {
        $serverList[] = $this->establishedConnections[ $prefix ];
        continue;
      }

			$connection = new Connection($this->ldapWrapper, $prefix);
			// check if a connection is possible
			try {
				$connection->init();
			}
			catch (ServerNotAvailableException $e) {
        $this->log->logException($e);
				continue;
			}

			$access = $this->accessFactory->get($connection);
			$access->setUserMapper($this->ldapUserMapping);
			$access->setGroupMapper($this->ldapGroupMapping);
			$serverList[] = $access;
      // chache the connection for later use
      $this->establishedConnections[ $prefix ] = $access;
		}

    return $serverList;
  }

  /**
   * @param string $entityNcId
   * @param string $entityType
   * @return string|false
   */
  private static function getEntityDnByNcId(string $entityNcId, string $entityType, Access $server) {
    return $entityType === EntityFactory::UserEntity ? $server->username2dn($entityNcId) : $server->groupname2dn($entityNcId);;
  }

  /**
   * @param string $entityDn
   * @param string $entityType
   * @return string|false
   */
  private static function getEntityNcIdByDn(string $entityDn, string $entityType, Access $server) {
    return $entityType === EntityFactory::UserEntity ? $server->dn2username($entityDn) : $server->dn2groupname($entityDn);;
  }

  /**
   * @param string $entityNcId
   * @param string $entityType
   * @return string|false
   */
  private static function getEntityUuidByDn(string $entityDn, string $entityType, Access $server) {
    $isUser = $entityType === EntityFactory::UserEntity;
    return $server->getUUID($entityDn, $isUser);
  }

  /**
   * @param string $entityNcId
   * @param string $entityDn
   * @param string $entityUuid
   * @param Access $server
   * @param string $entityType
   * @return LdapEntity
   * @throws LdapEntityUnknownException
   */
  private function createLdapEntity(string $entityNcId, string $entityDn, string $entityUuid, Access $server, string $entityType) {
    switch ($entityType) {
      case EntityFactory::UserEntity:
        return new LdapUser($entityNcId, $entityDn, $entityUuid, $server, $this->ldap, $this, $this->settings, $this->groupPluginManager);
      case EntityFactory::GroupEntity:
        return new LdapGroup($entityNcId, $entityDn, $entityUuid, $server, $this->ldap, $this, $this->settings);
      default:
        throw new LdapEntityUnknownException();
    }
  }
}
