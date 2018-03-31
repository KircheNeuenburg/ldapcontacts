<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Hornig Software 2017
 */

namespace OCA\LdapContacts\AppInfo;

use \OCP\AppFramework\App;
use OCA\LdapContacts\Controller\ContactController;
use OCA\LdapContacts\Controller\SettingsController;
use OCA\LdapContacts\Settings\Admin;

class Application extends App {
	/** @var string */
	protected $name;
	/** @var string */
	protected $id = 'ldapcontacts';
	
	/**
	 * @param array $urlParams
	 */
    public function __construct( $urlParams = array() ) {
        parent::__construct( $this->id, $urlParams );
		
		// get the apps name
		$this->name = $this->getContainer()->query( 'OCP\IL10N' )->t( 'Contacts' );
		
		// register the apps services
		$this->registerServices();
	}
	
	/**
	 * register all required services
	 */
	private function registerServices() {
        $container = $this->getContainer();
		$container->registerAlias( 'ContactController', ContactController::class);
		$container->registerAlias( 'SettingsController', SettingsController::class);
		$container->registerAlias( 'Admin', Admin::class);
		$container->registerAlias( 'AdminStatistics', AdminStatistics::class);
    }
	
	/**
	 * register the navigation button
	 */
	public function registerNavigation() {
		$container = $this->getContainer();
		
		$container->query( 'OCP\INavigationManager' )->add( function() use ( $container ) {
			$urlGenerator = $container->query( 'OCP\IURLGenerator' );
			return [
				// the string under which your app will be referenced in owncloud
				'id' => $this->id,

				// sorting weight for the navigation. The higher the number, the higher
				// will it be listed in the navigation
				'order' => 100,

				// the route that will be shown on startup
				'href' => $urlGenerator->linkToRoute( $this->id . '.contact.index' ),

				// the icon that will be shown in the navigation
				// this file needs to exist in img/
				'icon' => $urlGenerator->imagePath( $this->id, 'app.svg' ),

				// the title of your application. This will be used in the
				// navigation or on the settings page of your app
				'name' => $this->name,
			];
		});
		
		\OCP\App::registerPersonal( 'ldapcontacts', 'personal');
	}
	
	/**
	 * register the apps notifier
	 */
	public function registerNotifier() {
		$manager = \OC::$server->getNotificationManager();
		$manager->registerNotifier( function() {
			return new \OCA\LdapContacts\Notification\Notifier(
				\OC::$server->getL10NFactory()
			);
		}, function() {
			return [ 'id' => $this->id, 'name' => $this->name ];
		} );
	}
}
