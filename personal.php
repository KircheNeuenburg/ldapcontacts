<?php
use OCP\AppFramework\Http\TemplateResponse;

// user settings
$app = new \OCA\LdapContacts\AppInfo\Application();
$settings = $app->getContainer()->query('SettingsController');

$property['order_by'] = $settings->getUserValue( 'order_by' );
// generate template
$tmpl = new TemplateResponse( 'ldapcontacts', 'settings-personal', $property, '' );
return $tmpl->render();