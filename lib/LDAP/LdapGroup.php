<?php
namespace OCA\LdapContacts\LDAP;

class LdapGroup extends LdapEntity {
  /**
   * load group specific ldap attributes
   */
  protected function loadLdapAttributeKeys() {
    $mandatoryAttributes = [ $this->server->connection->ldapGroupDisplayName ];

    // merge all attributes
    $this->ldapAttributeKeys = array_unique( array_merge( $this->ldapAttributeKeys, $mandatoryAttributes ) );
  }

  /**
   * fetch group specific ldap attributes from the server
   *
   * @return bool
   */
  protected function loadLdapAttributeValues() {
    // fetch ladp attributes
    $groupList = $this->server->search($this->server->connection->ldapGroupFilter, $this->dn, $this->ldapAttributeKeys);
    if (empty($groupList)) return false;
    // turn the array values into single strings
    foreach ($groupList[0] as $attributeKey => $valueArray) {
			$composedValue = '';
			foreach ($valueArray as $i => $value) {
				$composedValue .= $i ? "\n" : '';
				$composedValue .= $value;
			}
			$this->ldapAttributeValues[ $attributeKey ] = $composedValue;
		}

    // get the groups name
		$this->title = $this->ldapAttributeValues[ $this->server->connection->ldapGroupDisplayName ];

    // TODO: implement avatar
    return true;
  }

  /**
   * check if this group is hidden and save the result
   */
  protected function updateIsHiddenAttribute() {
    $hiddenGroupIdList = $this->settings->getSetting('hiddenGroups', false);
    if ($hiddenGroupIdList === false) {
      // no users were hidden yet
      $this->hidden = false;
      return;
    }
    $this->hidden = in_array($this->uuid, $hiddenGroupIdList);
  }
}
