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
use \OCP\IUserManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class ContactController extends Controller {
	// LDAP configuration
	protected $host;
	protected $port;
	protected $base_dn;
	protected $group_dn;
	protected $admin_dn;
	protected $admin_pwd;
	protected $user_filter;
	protected $user_filter_hidden;
	protected $user_filter_specific;
	protected $group_filter;
	protected $group_filter_hidden;
	protected $group_filter_specific;
	protected $ldap_version;
	protected $uname_property;
	// ldap server connection
	protected $connection = false;
	protected $mail;
	// other variables
	protected $l;
	protected $config;
	protected $uid;
	protected $AppName;

	public function __construct( $AppName, IRequest $request, IConfig $config, $UserId ) {
		// check we have a logged in user
		\OCP\User::checkLoggedIn();
		parent::__construct($AppName, $request);
		// load ldap configuration from the user_ldap app
		$this->load_config();
		// get the config module for user settings
		$this->config = $config;
		// save the apps name
		$this->AppName = $AppName;
		// get the current users id
		$this->mail = $this->uid = $UserId;
		// connect to the ldap server
		$this->connection = ldap_connect( $this->host, $this->port );
		
		// TODO(hornigal): catch ldap errors
		ldap_set_option( $this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
		ldap_bind( $this->connection, $this->admin_dn, $this->admin_pwd );
		
		// load translation files
		$this->l = \OC::$server->getL10N( 'ldapcontacts' );
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
		// check if this is the correct server of if we have to use a prefix
		if( empty( $config['ldap_host'] ) ) {
			$connection = new \OCA\User_LDAP\Connection( $ldapWrapper, 's01' );
			$config = $connection->getConfiguration();
		}
		
		// put the needed configuration in the local variables
		$this->host = $config['ldap_host'];
		$this->port = $config['ldap_port'];
		$this->base_dn = $config['ldap_base_users'];
		$this->group_dn = $config['ldap_base_groups'];
		$this->admin_dn = $config['ldap_dn'];
		$this->admin_pwd = $config['ldap_agent_password'];
		$this->user_filter =  '(&' . $config['ldap_userlist_filter'] . '(!(objectClass=shadowAccount)))';
		$this->user_filter_hidden =  '(&' . $config['ldap_userlist_filter'] . '(objectClass=shadowAccount))';
		$this->user_filter_specific = $config['ldap_login_filter'];
		$this->group_filter = '(&' . $config['ldap_group_filter'] . '(!(objectClass=shadowAccount)))';
		$this->group_filter_hidden =  '(&' . $config['ldap_group_filter'] . '(objectClass=shadowAccount))';
		$this->group_filter_specific = '(&' . $config['ldap_group_filter'] . '(gidNumber=%gid))';
		$this->ldap_version = 3;
		$this->uname_property = 'uid';
	}
	
	/**
	 * returns the main template
	 * 
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
		return new DataResponse( $this->get_users( $this->user_filter ) );
	}
	
	/**
	* shows a users own data
	* 
	* @NoAdminRequired
	*/
	public function show() {
		// check this user actually has an email
		if( empty( $this->mail ) ) return new DataResponse( false );
		// get the users info
		return new DataResponse( $this->get_users( $this->user_filter , $this->mail ) );
	}
	
	/**
	* shows all available groups
	* 
	* @NoAdminRequired
	*/
	public function groups() {
		return new DataResponse( $this->get_groups( $this->group_filter ) );
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
	
	/**
	 * get all users from the LDAP server
	 * 
	 * @NoAdminRequired
	 * 
	 * @param string $uid
	 * @param string $get_dn
	 */
	public function get_users( $user_filter, $uid = false, $get_dn = false ) {
		if( $uid )
			$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ));
		else
			$request = ldap_search( $this->connection, $this->base_dn, $user_filter );
		
		$results = ldap_get_entries( $this->connection, $request );
		unset( $results['count'] );
		$return = array();
		
		$datas = explode( ',', 'mail,givenname,sn,street,postaladdress,postalcode,l,homephone,mobile,description,dn,uid' );
		
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
		
		// check if the users should be ordered by firstname or by lastname
		if( $this->config->getUserValue( $this->uid, $this->AppName, 'order_by' ) == 'lastname' ) {
			// order the contacts by lastname
			usort( $return, function( $a, $b ) {
				if( $a['sn'] == $b['sn'] ) return $a['givenname'] <=> $b['givenname'];
				else return $a['sn'] <=> $b['sn'];
			});
		}
		else {
			// order the contacts by firstname
			usort( $return, function( $a, $b ) {
				if( $a['givenname'] == $b['givenname'] ) return $a['sn'] <=> $b['sn'];
				else return $a['givenname'] <=> $b['givenname'];
			});
		}
		
		return $return;
	}

	/**
	 * returns all the groups the user is a member in
	 * 
	 * @param $uid		the users uid
	 */
	protected function get_user_groups( $uid ) {
		// get the users username
		if( !$uname = $this->get_uname( $uid ) ) return false;
		// construct the filter
		$filter = '(&' . $this->group_filter . '(memberUid=' . $uname . '))';
		// search the entries
		$result = ldap_list($this->connection, $this->group_dn, $filter);
		$entries = ldap_get_entries($this->connection, $result);
		
		// check if request was successful and if so, remove the count variable
		if( $entries['count'] < 1 ) return array();
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
		
		// order the groups
		usort( $output, function( $a, $b ) {
			return $a['cn'] <=> $b['cn'];
		});
		
		// return the buffer
		return $output;
	}
	
	/**
	 * returns an array of the cn and dn of all existing groups
	 */
	protected function get_groups( $group_filter ) {
		$request = ldap_list( $this->connection, $this->group_dn, $group_filter );
		$entries = ldap_get_entries($this->connection, $request);
		// check if request was successful and if so, remove the count variable
		if( $entries['count'] < 1 ) return array();
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
		
		// order the groups
		usort( $output, function( $a, $b ) {
			return $a['cn'] <=> $b['cn'];
		});
		
		// return the buffer
		return $output;
	}
	
	/**
	 * gets the user username (used for identification in groups)
	 * 
	 * @param $uid		the users id
	 */
	protected function get_uname( $uid ) {
		$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ), array( $this->uname_property ) );
		$entries = ldap_get_entries($this->connection, $request);
		// check if request was successful
		if( $entries['count'] < 1 ) return false;
		else return $entries[0][ $this->uname_property ][0];
	}
	
	/**
	 * get the users own dn
	 */
	protected function get_own_dn() {
		// check this user actually has an email
		if( empty( $this->mail ) ) return false;
		
		$user = $this->get_users( $this->user_filter, $this->mail, true );
		// check if the user has been found
		if( !isset( $user[0]['dn'] ) || empty( trim( $user[0]['dn'] ) ) ) return false;
		// extract dn from array and return it
		return $user[0]['dn'];
	}
	
	/**
	 * hides the given user
	 * 
	 * @param string $uid
	 */
	public function adminHideUser( $uid ) {
		// let the helper function handle the actual work
		$return = $this->adminHideUserHelper( $uid );
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'User is now hidden' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Making user invisible failed' ) ), 'status' => 'error' ) );
	}
	/**
	 * helper function for $this->adminHideUser( $uid )
	 * 
	 * @param string $uid
	 */
	private function adminHideUserHelper( $uid ) {
		// get the users objectClasses
		$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ), array( 'objectClass' ) );
		$results = ldap_get_entries( $this->connection, $request );
		// check if something has been found
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'] ) || $results['count'] != 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		$shadowGiven = false;
		// go through every objectclass and check if it is the shadowAccount attribute is already there
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class == "shadowAccount" ) {
				$shadowGiven = true;
				break;
			}
		}
		// if the shadowAccount attribute is not given yet, add it
		if( !$shadowGiven ) array_push( $results[0]['objectclass'], 'shadowAccount' );
		// save the modified data
		return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $results[0]['objectclass'] ) );
	}
	
	/**
	 * shows the given user
	 * 
	 * @param string $uid
	 */
	public function adminShowUser( $uid ) {
		// let the helper function handle the actual work
		$return = $this->adminShowUserHelper( $uid );
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'User is now visible again' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Making user visible failed' ) ), 'status' => 'error' ) );
	}
	/**
	 * helper function for $this->adminShowUser( $uid )
	 * 
	 * @param string $uid
	 */
	private function adminShowUserHelper( $uid ) {
		// get the users objectClasses
		$request = ldap_search( $this->connection, $this->base_dn, str_replace( '%uid', $uid, $this->user_filter_specific ), array( 'objectClass' ) );
		$results = ldap_get_entries( $this->connection, $request );
		// check if something has been found
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'] ) || $results['count'] != 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		// go through every objectclass and check if it is the shadowAccount attribute we have to remove
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class == "shadowAccount" ) unset( $results[0]['objectclass'][ $i ] );
		}
		
		// reorder array
		$objectclass = array_values( $results[0]['objectclass'] );
		// save the modified data
		return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $objectclass ) );
	}
	
	/**
	 * shows all users that are hidden
	 */
	public function adminGetUsersHidden() {
		return new DataResponse( $this->get_users( $this->user_filter_hidden, false ) );
	}
	
	/**
	 * hides the given user
	 * 
	 * @param string $gid
	 */
	public function adminHideGroup( $gid ) {
		// let the helper function handle the actual work
		$return = $this->adminHideGroupHelper( $gid );
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Group is now hidden' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Making group invisible failed' ) ), 'status' => 'error' ) );
	}
	/**
	 * helper function for $this->adminHideGroup( $gid )
	 * 
	 * @param string $gid
	 */
	private function adminHideGroupHelper( $gid ) {
		// get the groups objectClasses
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $gid, $this->group_filter_specific ), array( 'objectClass', 'uid', 'cn' ) );
		$results = ldap_get_entries( $this->connection, $request );
		
		
		// check if something has been found
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'], $results[0]['cn'][0] ) || $results['count'] != 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		$shadowGiven = false;
		// go through every objectclass and check if it is the shadowAccount attribute is already there
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class == "shadowAccount" ) {
				$shadowGiven = true;
				break;
			}
		}
		// if the shadowAccount attribute is not given yet, add it
		if( !$shadowGiven ) array_push( $results[0]['objectclass'], 'shadowAccount' );
		
		// if no uid is set yet, we have to add one
		if( !isset( $results[0]['uid'] ) ) {
			$uid = 'group' . strtolower( preg_replace('/\s+/', '', $results[0]['cn'][0]) );		// TODO(hornigal): add numbers in the back, if this isn't unique
			return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $results[0]['objectclass'], 'uid' => $uid ) );
		}
		
		// save the modified data
		return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $results[0]['objectclass'] ) );
	}
	
	/**
	 * shows the given user
	 */
	public function adminShowGroup( $gid ) {
		// let the helper function handle the actual work
		$return = $this->adminShowGroupHelper( $gid );
		// check if the request was a success or not
		if( $return ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Group is now visible again' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Making group visible failed' ) ), 'status' => 'error' ) );
	}
	/**
	 * helper function for $this->adminShowGroup( $gid )
	 * 
	 * @param string $gid
	 */
	private function adminShowGroupHelper( $gid ) {
		// get the users objectClasses
		$request = ldap_search( $this->connection, $this->group_dn, str_replace( '%gid', $gid, $this->group_filter_specific ), array( 'objectClass' ) );
		$results = ldap_get_entries( $this->connection, $request );
		// check if something has been found
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'] ) || $results['count'] != 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		// go through every objectclass and check if it is the shadowAccount attribute we have to remove
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class == "shadowAccount" ) unset( $results[0]['objectclass'][ $i ] );
		}
		// save the modified data
		return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $results[0]['objectclass'], 'uid' => array() ) );
	}
	
	/**
	 * shows all groups that are hidden
	 */
	public function adminGetGroupsHidden() {
		return new DataResponse( $this->get_groups( $this->group_filter_hidden ) );
	}
}
