<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Alexander Hornig 2017
 */

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\ldapcontacts\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

/** @var $this \OCP\Route\IRouter */
$this->create( 'ldapcontacts_ajax_save_settings', 'ajax/save_settings.php' )->actionInclude( 'ldapcontacts/ajax/save_settings.php' );

return [
    'routes' => [
	   [ 'name' => 'contact#index', 'url' => '/', 'verb' => 'GET' ],
	   [ 'name' => 'contact#load', 'url' => '/load', 'verb' => 'GET' ],
	   [ 'name' => 'contact#show', 'url' => '/own', 'verb' => 'GET' ],
	   [ 'name' => 'contact#update', 'url' => '/own', 'verb' => 'POST' ],
	   [ 'name' => 'contact#groups', 'url' => '/groups', 'verb' => 'GET' ],
	   [ 'name' => 'contact#admin_show_user', 'url' => '/admin/show/{uid}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#admin_hide_user', 'url' => '/admin/hide/{uid}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#adminGetUsersHidden', 'url' => '/admin', 'verb' => 'GET' ],
	   [ 'name' => 'contact#adminShowGroup', 'url' => '/admin/group/show/{gid}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#adminHideGroup', 'url' => '/admin/group/hide/{gid}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#adminGetGroupsHidden', 'url' => '/admin/group', 'verb' => 'GET' ],
	   [ 'name' => 'contact#getStatistic', 'url' => '/statistic/{type}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#getStatistics', 'url' => '/statistics', 'verb' => 'GET' ],
	   [ 'name' => 'settings#setUserValue', 'url' => '/settings/personal', 'verb' => 'POST' ],
	   [ 'name' => 'settings#getUserValue', 'url' => '/settings/personal/{key}', 'verb' => 'GET' ],
	   [ 'name' => 'settings#updateSettings', 'url' => '/settings/update', 'verb' => 'POST' ],
	   [ 'name' => 'settings#getSettings', 'url' => '/settings', 'verb' => 'GET' ],
	   [ 'name' => 'settings#getSetting', 'url' => '/setting/{type}', 'verb' => 'GET' ],
	   [ 'name' => 'settings#updateSetting', 'url' => '/setting', 'verb' => 'POST' ],
    ]
];
