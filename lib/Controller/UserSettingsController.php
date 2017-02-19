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

namespace OCA\LdapContacts\Controller;

use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class UserSettingsController extends Controller {
	private $AppName;
	private $config;
	private $uid;
	private $l;
	// default values
	private $default = array(
		'order_by' => 'firstname',
		'tutorial_state' => 0,
	);

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 */
	public function __construct($AppName, IRequest $request, IConfig $config ){
		parent::__construct($AppName, $request);
		// set class variables
		$this->AppName = $AppName;
		$this->config = $config;
		// get the current users id
		$this->uid = \OC::$server->getUserSession()->getUser()->getUID();
		// load translation files
		$this->l = \OC::$server->getL10N( 'ldapcontacts' );
	}
	
	/**
	 * gets the value for the given setting
	 * 
	 * @param string $key
	 * @NoAdminRequired
	 */
	public function getUserValue( $key ) {
		// check if this is a valid setting
		if( !isset( $this->default[ $key ] ) ) return false;
		return $this->config->getUserValue( $this->uid, $this->appName, $key, $this->default[ $key ] );
	}
	
	/**
	 * saves the given setting an returns a DataResponse
	 * 
	 * @param string $key
	 * @param string $value
	 * @NoAdminRequired
	 */
	private function setUserValue( $key, $value ) {
		// check if this is a valid setting
		if( !isset( $this->default[ $key ] ) ) return false;
		return $this->config->setUserValue( $this->uid, $this->appName, $key, $value );
	}
	
	/**
	 * saves the given setting an returns a DataResponse
	 * 
	 * @param string $key
	 * @param string $value
	 * @NoAdminRequired
	 */
	public function saveSettings( $key, $value ) {
		if( isset( $this->default[ $key ] ) && !$this->setUserValue( $key, $value ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Settings saved' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ) ), 'status' => 'error' ) );
	}
}