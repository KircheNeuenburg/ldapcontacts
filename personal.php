<?php
use OCP\AppFramework\Http\TemplateResponse;

// user settings
$app = new \OCA\LdapContacts\AppInfo\Application();
$settings = $app->getContainer()->query('SettingsController');

$property['order_by'] = $settings->getUserValue( 'order_by', false );
$property['user_ldap_attributes'] = $settings->getSetting( 'user_ldap_attributes', false );
// generate template
$tmpl = new TemplateResponse( 'ldapcontacts', 'settings-personal', $property, '' );
return $tmpl->render();
