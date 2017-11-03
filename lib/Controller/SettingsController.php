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

use OCP\IL10N;
use OCP\IRequest;
use OCP\IConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class SettingsController extends Controller {
	private $AppName;
	private $config;
	private $uid;
	private $l;
    // default values
    private $default = [];
	// default user values
	private $user_default = [];

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N l10n
	 * @param IConfig $config
     * @param mixed $UserId
	 */
	public function __construct($AppName, IRequest $request, IL10N $l10n, IConfig $config, $UserId ) {
        // check we have a logged in user
		\OCP\User::checkLoggedIn();
		parent::__construct($AppName, $request);
		// set class variables
		$this->AppName = $AppName;
		$this->config = $config;
        		// load translation files
		$this->l = $l10n;
		// get the current users id
		$this->uid = $UserId;
        // set default values
        $this->default = [
            'login_attribute' => '',
            'edit_login_url' => '',
            // available data
            'user_ldap_attributes' => [ 'mail' => $this->l->t( 'Mail' ), 'givenname' => $this->l->t( 'First Name' ), 'sn' => $this->l->t( 'Last Name' ), 'street' => $this->l->t( 'Street' ), 'postaladdress' => $this->l->t( 'House number' ), 'postalcode' => $this->l->t( 'zip Code' ), 'l' => $this->l->t( 'City' ), 'homephone' => $this->l->t( 'Phone' ), 'mobile' => $this->l->t( 'Mobile' ), 'description' => $this->l->t( 'About me' ) ],
        ];
        // set default user values
        $this->user_default = [
            'order_by' => 'firstname',
            'tutorial_state' => 0,
        ];
	}
	
	/**
	 * gets the value for the given setting
	 * 
	 * @param string $key
	 * @NoAdminRequired
	 */
	public function getUserValue( $key ) {
		// check if this is a valid setting
		if( !isset( $this->user_default[ $key ] ) ) return false;
        // get the setting
        $data = $this->config->getUserValue( $this->uid, $this->AppName, $key, $this->user_default[ $key ] );
        // return message and data if given
		if( $data !== false ) return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
        else return new DataResponse( [ 'status' => 'error' ] );
	}
    
	/**
	 * saves the given user value and returns a DataResponse
	 * 
	 * @param string $key
	 * @param string $value
	 * @NoAdminRequired
	 */
	public function setUserValue( $key, $value ) {
		if( isset( $this->user_default[ $key ] ) && !$this->config->setUserValue( $this->uid, $this->AppName, $key, $value ) ) {
            return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Settings saved' ) ), 'status' => 'success' ) );
        }
		else {
            return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ) ), 'status' => 'error' ) );
        }
	}
    
    /**
	 * returns the value for the given general setting
     * 
     * @param string $key
     * @param bool $DataResponse
	 */
	public function getSetting( $key, $DataResponse=true ) {
		// check if this is a valid setting
		if( !isset( $this->default[ $key ] ) ) return false;
        // get the setting
        $data = $this->config->getAppValue( $this->AppName, $key, $this->default[ $key ] );
        // return message and data if given
        if( !is_bool( $data ) ) {
            if( $DataResponse ) return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
            else return $data;
        }
		else if( $DataResponse ) return new DataResponse( [ 'status' => 'error' ] );
        else return false;
	}
	
	/**
	 * returns all settings from this app
	 */
	public function getSettings() {
		// output buffer
		$data = array();
        $success = true;
		// go through every existing setting
		foreach( $this->default as $key => $v ) {
			// get the settings value
			$response = $this->getSetting( $key )->getData();
            $data[ $key ] = $response['data'];
            $success &= ( $response['status'] === 'success' );
		}
		// return the buffered data
		if( $success ) return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
        else return new DataResponse( [ 'status' => 'error' ] );
	}
	
	/*
	 * updates the given setting
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function updateSetting( $key, $value ) {
		// check if the setting is an actual setting this app has
		if( !isset( $this->default[ $key ] ) ) return false;
		// save the setting
		$success = !$this->config->setAppValue( $this->AppName, $key, $value );
        // return success or failure message
        if( $success ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Settings saved' ) ), 'status' => 'success' ) );
        else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * returns all settings from this app
	 * 
	 * @param array $settings
	 */
	public function updateSettings( $settings ) {
		$success = true;
		// go through every setting and update it
		 foreach( $settings as $array ) {
             // check all data is given
             if( !isset( $array['name'], $array['value'] ) ) {
                 $success = false;
                 continue;
             }
             // update the setting
			 $response = $this->updateSetting( $array['name'], $array['value'] )->getData();
             $success &= ( $response['status'] === 'success' );
		 }
		 // return message
		 if( $success ) return new DataResponse( [ 'data' => [ 'message' => $this->l->t( 'Settings saved' ) ], 'status' => 'success'] );
		 else return new DataResponse( [ 'data' => [ 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ) ], 'status' => 'error' ] );
	}
}