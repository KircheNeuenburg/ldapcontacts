<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Hornig Software 2016
 */


// TODO(hornigal): make these settings editable through the administration panel	<=>		controller/contactcontroller.php:21
// TODO(hornigal): catch ldap errors	<=>		controller/contactcontroller.php:39
// TODO(hornigal): let the admin hide users



namespace OCA\LdapContacts\AppInfo;

use OCP\AppFramework\App;

\OCP\App::registerAdmin("ldapcontacts", "admin");

$app = new App('ldapcontacts');
$container = $app->getContainer();

$container->query('OCP\INavigationManager')->add(function () use ($container) {
	$urlGenerator = $container->query('OCP\IURLGenerator');
	$l10n = $container->query('OCP\IL10N');
	return [
		// the string under which your app will be referenced in owncloud
		'id' => 'ldapcontacts',

		// sorting weight for the navigation. The higher the number, the higher
		// will it be listed in the navigation
		'order' => 100,

		// the route that will be shown on startup
		'href' => $urlGenerator->linkToRoute('ldapcontacts.contact.index'),

		// the icon that will be shown in the navigation
		// this file needs to exist in img/
		'icon' => $urlGenerator->imagePath('ldapcontacts', 'app.svg'),

		// the title of your application. This will be used in the
		// navigation or on the settings page of your app
		'name' => $l10n->t('Contacts'),
	];
});
