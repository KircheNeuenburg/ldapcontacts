<?php

return [
    'routes' => [
	   [ 'name' => 'contact#index', 'url' => '/', 'verb' => 'GET' ],
	   [ 'name' => 'contact#users', 'url' => '/load', 'verb' => 'GET' ],
	   [ 'name' => 'contact#hiddenUsers', 'url' => '/load/hidden', 'verb' => 'GET' ],
	   [ 'name' => 'contact#show', 'url' => '/own', 'verb' => 'GET' ],
	   [ 'name' => 'contact#update', 'url' => '/own', 'verb' => 'POST' ],
	   [ 'name' => 'contact#groups', 'url' => '/groups', 'verb' => 'GET' ],
	   [ 'name' => 'contact#hiddenGroups', 'url' => '/groups/hidden', 'verb' => 'GET' ],
	   [ 'name' => 'contact#showEntity', 'url' => '/admin/show/{type}/{uuid}', 'verb' => 'GET' ],
	   [ 'name' => 'contact#hideEntity', 'url' => '/admin/hide/{type}/{uuid}', 'verb' => 'GET' ],

	   [ 'name' => 'statistic#get', 'url' => '/statistics', 'verb' => 'GET' ],

	   [ 'name' => 'settings#setUserValue', 'url' => '/settings/personal', 'verb' => 'POST' ],
	   [ 'name' => 'settings#getUserValue', 'url' => '/settings/personal/{key}', 'verb' => 'GET' ],

	   [ 'name' => 'settings#updateSettings', 'url' => '/settings/update', 'verb' => 'POST' ],
	   [ 'name' => 'settings#getSettings', 'url' => '/settings', 'verb' => 'GET' ]
    ]
];
