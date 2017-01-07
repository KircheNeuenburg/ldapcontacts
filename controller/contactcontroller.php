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

namespace OCA\LdapContacts\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class ContactController extends Controller {
	// LDAP configuration
	private $host;
	private $port;
	private $base_dn;
	private $group_dn;
	private $admin_dn;
	private $admin_pwd;
	private $user_filter;
	private $user_filter_specific;
	private $group_filter;
	private $ldap_version;
	private $uname_property;
	// ldap server connection
	private $connection = false;
	private $mail;

	public function __construct($AppName, IRequest $request){
		parent::__construct($AppName, $request);
		// load ldap configuration from the user_ldap app
		$this->load_config();
		// connect to the ldap server
		$this->connection = ldap_connect( $this->host, $this->port );
		// TODO(hornigal): catch ldap errors
		ldap_set_option( $this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
		ldap_bind( $this->connection, $this->admin_dn, $this->admin_pwd );
		$this->mail = \OC::$server->getUserSession()->getUser()->getEMailAddress();
	}
	
	/**
	 * loads the ldap configuration from the user_ldap app
	 * 
	 * @param string $prefix
	 */
	private function load_config( $prefix = '' ) {
		// load configuration
		$ldapWrapper = new \OCA\User_LDAP\LDAP();
		$connection = new \OCA\User_LDAP\Connection( $ldapWrapper );
		$config = $connection->getConfiguration();
		// put the needed configuration in the local variables
		$this->host = $config['ldap_host'];
		$this->port = $config['ldap_port'];
		$this->base_dn = $config['ldap_base_users'];
		$this->group_dn = $config['ldap_base_groups'];
		$this->admin_dn = $config['ldap_dn'];
		$this->admin_pwd = $config['ldap_agent_password'];
		$this->user_filter =  '(&' . $config['ldap_userlist_filter'] . '(!(objectClass=shadowAccount)))';
		$this->user_filter_specific = '(&' . $config['ldap_login_filter'] . '(!(objectClass=shadowAccount)))';
		$this->group_filter = '(&' . $config['ldap_group_filter'] . '(!(objectClass=shadowAccount)))';
		$this->ldap_version = 3;
		$this->uname_property = 'uid';
	}
	
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		return new TemplateResponse( 'ldapcontacts', 'main' );
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 */
	public function load() {
		return new DataResponse( $this->get_users() );
	}
	
	/**
	* shows a users own data
	* 
	* @NoAdminRequired
	*/
	public function show() {
		return new DataResponse( $this->get_users( $this->mail ) );
	}
	
	/**
	* shows all available groups
	* 
	* @NoAdminRequired
	*/
	public function groups() {
		return new DataResponse( $this->get_groups() );
	}
	
	/**
	* updates a users own data
	* 
	* @NoAdminRequired
	*
	* @param string $givenname
	* @param string $sn
	* @param string $street
	* @param string $postaladdress
	* @param string $postalcode
	* @param string $l
	* @param string $homephone
	* @param string $mobile
	* @param string $description
	*/
	public function update( $givenname, $sn, $street, $postaladdress, $postalcode, $l, $homephone, $mobile, $description ) {
		// put all given values in one array
		$datas = explode( ',', 'givenname,sn,street,postaladdress,postalcode,l,homephone,mobile,description' );
		$modify = array();
		foreach( $datas as $data ) {
			$$data = trim( $$data );
			// remove entrie if exists
			if( $$data == '' ) {
				
			}
			else {
				// add or modify entires
				$modify[ $data ] = $$data;
			}
		}
		
		// get own dn
		if( !$dn = $this->get_own_dn() ) return false;
		
		// update given values
		if( ldap_modify( $this->connection, $dn, $modify ) ) return new DataResponse( 'SUCCESS' );
		else return new DataResponse( 'ERROR' );
	}
	
	/*
	 * get all users from the LDAP server
	 * 
	 * @NoAdminRequired
	 * 
	 * @param string $uid
	 * @param string $get_dn
	 */
	public function get_users($uid = false, $get_dn = false) {
		if( $uid )
			$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ));
		else
			$request = ldap_search( $this->connection, $this->base_dn, $this->user_filter );
		
		$results = ldap_get_entries( $this->connection, $request );
		unset( $results['count'] );
		$return = array();
		
		$datas = explode( ',', 'mail,givenname,sn,street,postaladdress,postalcode,l,homephone,mobile,description,dn' );
		
		$id = 1;
		
		foreach( $results as $i => $result ) {
			$tmp = array();
			foreach( $datas as $data ) {
				// check if the value exists for the user
				if( isset( $result[ $data ] ) ) {
					if( is_array( $result[ $data ] ) )
						$tmp[ $data ] = trim( $result[ $data ][0] );
					else
						$tmp[ $data ] = trim( $result[ $data ] );
				}
			}
			
			// combine full name
			$tmp['name'] = $tmp['givenname'] . ' ' . $tmp['sn'];
			// a contact has to have a name
			if( $tmp['name'] == ' ' ) continue;
			
			// save the current id
			$tmp['id'] = $id;
			// delete dn if not explicitly requested
			if( !$get_dn ) unset( $tmp['dn'] );
			
			// get the users groups
			$groups = $this->get_user_groups( $tmp['mail'] );
			if( $groups ) $tmp['groups'] = $groups;
			else $tmp['groups'] = array();
			
			// delete all empty entries
			foreach( $tmp as $key => $value ) {
				if( !is_array( $value ) && empty( trim( $value ) ) ) unset( $tmp[ $key ] );
			}
			
			array_push( $return, $tmp );
			$id++;
		}
		
		return $return;
	}

	/*
	 * returns all the groups the user is a member in
	 * 
	 * @param $uid		the users uid
	 */
	private function get_user_groups( $uid ) {
		// get the users username
		if( !$uname = $this->get_uname( $uid ) ) return false;
		// construct the filter
		$filter = '(&' . $this->group_filter . '(memberUid=' . $uname . '))';
		// search the entries
		$result = ldap_search($this->connection, $this->group_dn, $filter);
		$entries = ldap_get_entries($this->connection, $result);
		
		// check if request was successful and if so, remove the count variable
		if( $entries['count'] < 1 ) return false;
		array_shift( $entries );
		
		// output buffer
		$output = array();
		// go through all the groups
		foreach( $entries as $group ) {
			// check all values are there
			if( !isset( $group['dn'], $group['cn'][0] ) ) continue;
			// put the groups values in the buffer
			$array = array();
			$array['dn'] = $group['dn'];
			$array['cn'] = $group['cn'][0];
			$array['id'] = $group['gidnumber'][0];
			// write group buffer to output buffer
			array_push( $output, $array );
		}
		// return the buffer
		return $output;
	}
	
	/*
	 * returns an array of the cn and dn of all existing groups
	 */
	private function get_groups() {
		$request = ldap_search( $this->connection, $this->group_dn, $this->group_filter );
		$entries = ldap_get_entries($this->connection, $request);
		// check if request was successful and if so, remove the count variable
		if( $entries['count'] < 1 ) return false;
		array_shift( $entries );
		
		// output buffer
		$output = array();
		// go through all the groups
		foreach( $entries as $group ) {
			// check all values are there
			if( !isset( $group['dn'], $group['cn'][0] ) ) continue;
			// put the groups values in the buffer
			$array = array();
			$array['dn'] = $group['dn'];
			$array['cn'] = $group['cn'][0];
			$array['id'] = $group['gidnumber'][0];
			// write group buffer to output buffer
			array_push( $output, $array );
		}
		// return the buffer
		return $output;
	}
	
	/*
	 * gets the user username (used for identification in groups)
	 * 
	 * @param $uid		the users id
	 */
	private function get_uname( $uid ) {
		$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ), array( $this->uname_property ) );
		$entries = ldap_get_entries($this->connection, $request);
		// check if request was successful
		if( $entries['count'] < 1 ) return false;
		else return $entries[0][ $this->uname_property ][0];
	}
	
	/*
	 * get the users own dn
	 */
	private function get_own_dn() {
		$user = $this->get_users( $this->mail, true );
		// check if the user has been found
		if( !isset( $user[0]['dn'] ) || empty( trim( $user[0]['dn'] ) ) ) return false;
		// extract dn from array and return it
		return $user[0]['dn'];
	}
	
	/*
	 * saves the given setting
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function save_setting($name, $value) {
		return new DataResponse( array( $name => $value ) );
	}
}
