<?php

namespace OCA\LdapContacts\LDAP;

use OCA\User_LDAP\LDAP;
use resource;

class LdapWrite extends LDAP {
  /**
   * @param resource $link
   * @param string $entityDN
   * @param array $attributes
   * @return bool
   */
  public function modAttributes($link, string $entityDN, array $attributes) {
    return $this->invokeLDAPMethod('mod_replace', $link, $entityDN, $attributes);
  }
}
