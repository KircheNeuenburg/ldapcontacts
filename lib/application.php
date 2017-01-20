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

namespace OCA\LdapContacts;

use \OCP\AppFramework\App;
use \OCA\LdapContacts\Controller\UserSettingsController;

class Application extends App {
    public function __construct() {
        parent::__construct( 'ldapcontacts' );

        $container = $this->getContainer();

        /**
         * Controllers
         */
        $container->registerService('UserSettingsController', function($c) {
            return new UserSettingsController(
				$c->query('AppName'),
				$c->query('Request'),
				$c->query('Config')
			);
        });

        $container->registerService('Config', function($c) {
            return $c->query('ServerContainer')->getConfig();
        });
    }
}

