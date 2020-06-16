<?php
use OCA\LdapContacts\AppInfo\Application;

if (\OC::$server->getAppManager()->isInstalled('user_ldap')) {
	$app = new Application();
	$app->registerNavigation();
}
