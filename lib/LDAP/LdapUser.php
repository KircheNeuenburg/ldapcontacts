<?php
namespace OCA\LdapContacts\LDAP;

use OCA\LdapContacts\Controller\SettingsController;
use OCA\User_LDAP\Access;
use OCA\User_LDAP\Group_LDAP;
use OCA\User_LDAP\GroupPluginManager;

class LdapUser extends LdapEntity {
  /** @var LdapGroup[] **/
  protected $userGroups = [];
	/** @var GroupPluginManager */
	protected $groupPluginManager;

  /**
   * @param string $ncId
   * @param string $dn
   * @param string $uuid
   * @param Access $server
   * @param LdapWrite $ldap
   * @param EntityFactory $entityFactory
   * @param SettingsController $settings
   * @param GroupPluginManager $groupPluginManager
   */
  public function __construct(string $ncId,
                              string $dn,
                              string $uuid,
                              Access $server,
                              LdapWrite $ldap,
                              EntityFactory $entityFactory,
                              SettingsController $settings,
                              GroupPluginManager $groupPluginManager) {
    parent::__construct($ncId, $dn, $uuid, $server, $ldap, $entityFactory, $settings);
    $this->groupPluginManager = $groupPluginManager;
  }

  /**
   * load user specific ldap attribute keys
   */
  protected function loadLdapAttributeKeys() {
    $mandatoryAttributes = [ $this->server->connection->ldapUserDisplayName, $this->server->connection->ldapUserDisplayName2 ];
    // add attributes defined by the admin
    $additionalDefinedAttributes = $this->settings->getSetting('userLdapAttributes', false);
    foreach ($additionalDefinedAttributes as $attribute) {
      $mandatoryAttributes[] = $attribute['name'];
    }

    // merge all attributes
    $this->ldapAttributeKeys = array_unique( array_merge( $this->ldapAttributeKeys, $mandatoryAttributes ) );

    // remove empty attributes
    $this->ldapAttributeKeys = array_values( array_filter( $this->ldapAttributeKeys, function($value) { return !is_null($value) && $value !== ''; } ) );
  }

  /**
   * fetch user specific ldap attributes from the server
   *
   * @return bool
   */
  protected function loadLdapAttributeValues() {
    // fetch ladp attributes
    $userList = $this->server->search($this->server->connection->ldapUserFilter, $this->dn, $this->ldapAttributeKeys);
    if (empty($userList)) return false;
    // turn the array values into single strings
    foreach ($userList[0] as $attributeKey => $valueArray) {
			$composedValue = '';
			foreach ($valueArray as $i => $value) {
				$composedValue .= $i ? "\n" : '';
				$composedValue .= $value;
			}
			$this->ldapAttributeValues[ $attributeKey ] = $composedValue;
		}

    // compose the users name
		$this->title = $this->ldapAttributeValues[ $this->server->connection->ldapUserDisplayName ];
		$userDisplayName2 = @$this->ldapAttributeValues[ $this->server->connection->ldapUserDisplayName2 ];
		$this->title .= empty($userDisplayName2) ? '' : ' (' . $userDisplayName2 . ')';

    // TODO: implement avatar
    return true;
  }

  /**
   * load the groups the user is a member of
   *
   * @param bool $ignoreHiddenGroups=false
   */
  public function loadOwnGroups(bool $ignoreHiddenGroups=false) {
    $this->userGroups = [];

    // fetch the groups the user is a member of
    $groupHelper = new Group_LDAP($this->server, $this->groupPluginManager);
    $groupUuidList = $groupHelper->getUserGroups($this->ncId);
    // load each groups data
    foreach ($groupUuidList as $groupUuid) {
      $this->userGroups[] = $this->entityFactory->getGroupByNcId($groupUuid);
    }
  }

  /**
   * check if this user is hidden and save the result
   */
  protected function updateIsHiddenAttribute() {
    $hiddenuserIdList = $this->settings->getSetting('hiddenUsers', false);
    if ($hiddenuserIdList === false) {
      // no users were hidden yet
      $this->hidden = false;
      return;
    }
    $this->hidden = in_array($this->uuid, $hiddenuserIdList);
  }

  /**
   * convert the user into an array with it's data
   *
   * @return array
   */
  public function toDataArray() {
    // get the normal data array
    $dataArray = parent::toDataArray();
    $dataArray['groups'] = [];

    // add all groups data
    foreach ($this->userGroups as $group) {
      $dataArray['groups'][] = $group->toDataArray();
    }

    return $dataArray;
  }
}
