<?php
namespace OCA\LdapContacts\LDAP;

use OCP\AppFramework\Http\DataResponse;
use OCA\LdapContacts\Controller\SettingsController;
use OCA\User_LDAP\Access;

abstract class LdapEntity {
  /** @var string **/
  protected $ncId = false;
  /** @var string **/
  protected $dn = false;
  /** @var string **/
  protected $uuid = false;
  /** @var Access **/
  protected $server = false;
  /** @var LdapWrite **/
  protected $ldap;
  /** @var EntityFactory **/
  protected $entityFactory = false;
  /** @var SettingsController **/
  protected $settings;
  /** @var string[] **/
  protected $ldapAttributeKeys = [ 'dn' ];
  /** @var array **/
  protected $ldapAttributeValues = [];
  /** @var string **/
  protected $title;
  /** @var string **/
  protected $avatarUrl;
  /** @var bool **/
  protected $hidden = false;

  /**
   * @param string $ncId
   * @param string $dn
   * @param string $uuid
   * @param Access $server
   * @param LdapWrite $ldap
   * @param EntityFactory $entityFactory
   * @param SettingsController $settings
   */
  public function __construct(string $ncId,
                              string $dn,
                              string $uuid,
                              Access $server,
                              LdapWrite $ldap,
                              EntityFactory $entityFactory,
                              SettingsController $settings) {
    $this->ncId = $ncId;
    $this->dn = $dn;
    $this->uuid = $uuid;
    $this->server = $server;
    $this->ldap = $ldap;
    $this->entityFactory = $entityFactory;
    $this->settings = $settings;

    // load the entities values
    $this->loadLdapAttributeKeys();
    $this->loadLdapAttributeValues();

    $this->updateIsHiddenAttribute();
  }

  /**
   * load entity specific ldap attribute keys
   */
  abstract protected function loadLdapAttributeKeys();

  /**
   * fetch entity specific ldap attributes from the server
   */
  abstract protected function loadLdapAttributeValues();

  /**
   * convert the entity into an array with it's data
   *
   * @return array
   */
  public function toDataArray() {
    return [
      'uuid' => $this->uuid,
      'ldapAttributes' => $this->ldapAttributeValues,
      'title' => $this->title,
      'avaterUrl' => $this->avatarUrl
    ];
  }

  /**
   * convert the entity into a DataResponse object, that can be used as an ajax response
   *
   * @return DataResponse
   */
  public function toDataResponse() {
    return new DataResponse([ 'status' => 'success', 'data' => $this->toDataArray() ]);
  }

  /**
   * tells wether or not the entity is hidden
   *
   * @return bool
   */
  public function isHidden() {
    return $this->hidden;
  }

  /**
   * check if this entity is hidden and save the result
   */
  abstract protected function updateIsHiddenAttribute();

  /**
   * get the entitys value for the given attribute
   *
   * @param string $attributeKey
   *
   * @return string|bool
   */
  public function getAttributeValue(string $attributeKey) {
    return isset($this->ldapAttributeValues[ $attributeKey ]) ? $this->ldapAttributeValues[ $attributeKey ] : false;
  }

  /**
   * returns the entitys title
   *
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * returns the entitys uuid
   *
   * @return string
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * updates the entitys data entries with the given values
   *
   * @param array $newLdapAttributes
   * @return bool
   */
  public function updateData(array $newLdapAttributes) {
    $verifiedChanges = [];

    foreach ($this->ldapAttributeKeys as $key) {
      // skip not send and not changed attributes
      if (!isset($newLdapAttributes[$key]) || $newLdapAttributes[$key] === $this->ldapAttributeValues[$key]) continue;
      // handle attribute deletion
      if (empty(trim($newLdapAttributes[$key]))) {
        $verifiedChanges[$key] = [];
        continue;
      }
      // handle normal modification
      $verifiedChanges[$key] = $newLdapAttributes[$key];
    }

    $connectionResource = $this->server->getConnection()->getConnectionResource();
    return $this->ldap->modAttributes($connectionResource, $this->dn, $verifiedChanges);
  }

  /**
   * // TODO: remove after testing
   */
  public function print() {
    echo '<h4>NcId</h4>';
    echo "<pre>"; var_export( $this->ncId ); echo "</pre>";
    echo '<h4>Dn</h4>';
    echo "<pre>"; var_export( $this->dn ); echo "</pre>";
    echo '<h4>UUID</h4>';
    echo "<pre>"; var_export( $this->uuid ); echo "</pre>";
    echo '<h4>LDAP Attribute Keys</h4>';
    echo "<pre>"; var_export( $this->ldapAttributeKeys ); echo "</pre>";
    echo '<h4>LDAP Attribute Values</h4>';
    echo "<pre>"; var_export( $this->ldapAttributeValues ); echo "</pre>";
    echo '<h4>Title</h4>';
    echo "<pre>"; var_export( $this->title ); echo "</pre>";
    echo '<h4>Avatar Url</h4>';
    echo "<pre>"; var_export( $this->avatarUrl ); echo "</pre>";
    echo '<br><hr><br>';
  }
}
