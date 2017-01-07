<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Alexander Hornig 2016
 */

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\ldapcontacts\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

return [
    'routes' => [
	   ['name' => 'contact#index', 'url' => '/', 'verb' => 'GET'],
	   ['name' => 'contact#load', 'url' => '/contacts', 'verb' => 'GET'],
	   ['name' => 'contact#show', 'url' => '/contacts/own', 'verb' => 'GET'],
	   ['name' => 'contact#update', 'url' => '/contacts/own', 'verb' => 'POST'],
	   ['name' => 'contact#groups', 'url' => '/contacts/groups', 'verb' => 'GET'],
	   ['name' => 'contact#save_setting', 'url' => '/admin/save', 'verb' => 'POST']
    ]
];
