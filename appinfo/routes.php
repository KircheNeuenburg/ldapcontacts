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
	   [ 'name' => 'contact#adminShowEntry', 'url' => '/admin/show/{entry_id}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#adminHideEntry', 'url' => '/admin/hide/{type}/{entry_id}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#adminGetEntryHidden', 'url' => '/admin/hidden/{type}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#searchFilterPreviews', 'url' => '/searchfilter/previews', 'verb' => 'GET' ],
	   [ 'name' => 'contact#testSettings', 'url' => '/test/{type}/{var}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#getStatistic', 'url' => '/statistic/{type}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#getStatistics', 'url' => '/statistics', 'verb' => 'GET' ],
		
		
	   [ 'name' => 'contact#notifyContactInfoNotFilledOut', 'url' => '/notify/contact-not-filled', 'verb' => 'GET' ],
		
		
	   [ 'name' => 'settings#setUserValue', 'url' => '/settings/personal', 'verb' => 'POST' ],
	   [ 'name' => 'settings#getUserValue', 'url' => '/settings/personal/{key}', 'verb' => 'GET' ],
	   [ 'name' => 'settings#updateSettings', 'url' => '/settings/update', 'verb' => 'POST' ],
	   [ 'name' => 'settings#getSettings', 'url' => '/settings', 'verb' => 'GET' ],
	   [ 'name' => 'settings#getSetting', 'url' => '/setting/{type}', 'verb' => 'GET' ],
	   [ 'name' => 'settings#updateSetting', 'url' => '/setting', 'verb' => 'POST' ],
	   [ 'name' => 'settings#arraySettingRemoveKey', 'url' => '/setting/array/remove', 'verb' => 'POST' ],
    ]
];
