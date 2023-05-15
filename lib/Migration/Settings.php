<?php
namespace OCA\LdapContacts\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\IConfig;
use OCP\IL10n;

class Settings implements IRepairStep {
  /** @var string */
  protected $appName;
  /** @var IConfig */
  protected $config;

  /**
   * @param string $appName
   * @param IConfig $config
   */
  public function __construct($appName, IConfig $config) {
    $this->appName = $appName;
    $this->config = $config;
  }

  /**
   * Returns the step's name
   */
  public function getName() {
    return 'Settings restructuring';
  }

  /**
   * @param IOutput $output
   */
  public function run(IOutput $output) {

  }

  /**
   * change layout of user ldap attributes
   */
  private function convertUserLdapAttributes() {
    $oldAttributes = $this->config->getAppValue( $this->appName, 'user_ldap_attributes', false );
    $newAttributes = [];
    // if no settings exists, there is nothing to be done here
    if ($oldAttributes === false) return;

    // convert to new format
    foreach ($oldAttributes as $name => $label) {
      $newAttributes[] = [
        'name' => $name,
        'label' => $label
      ];
    }

    // set converted attributes
    $this->config->setAppValue( $this->appName, 'userLdapAttributes', $newAttributes );

    // remove old attribtues
    $this->config->deleteAppValue( $this->appName, 'user_ldap_attributes' );
  }
}
