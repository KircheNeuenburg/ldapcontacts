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
use OCA\LdapContacts\Controller\SettingsController;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Mapping\GroupMapping;
use OCP\IDBConnection;

class ContactController extends Controller {
	// LDAP configuration
	protected $host;
	protected $port;
	protected $base_dn;
	protected $group_dn;
	protected $admin_dn;
	protected $admin_pwd;
	protected $user_filter;
	protected $user_filter_specific;
	protected $group_filter;
	protected $group_filter_specific;
	protected $ldap_version;
	protected $user_display_name;
	protected $access;
	// ldap server connection
	protected $connection = false;
	// other variables
	protected $l;
	protected $config;
	protected $uid;
	protected $user_dn;
	protected $AppName;
    protected $settings;
	protected $db;
    // values
    protected $contacts_available_attributes;
    protected $contacts_default_attributes;
 	// all available statistics
 	protected $statistics = [ 'entries', 'entries_filled', 'entries_empty', 'entries_filled_percent', 'entries_empty_percent', 'users', 'users_filled_entries', 'users_empty_entries', 'users_filled_entries_percent', 'users_empty_entries_percent' ];

    /**
	 * @param string $AppName
	 * @param IRequest $request
	'entries', 'entries_filled', 'entries_empty', 'entries_filled_percent', 'entries_empty_percent', 'users', 'users_filled_entries', 'users_empty_entries', 'users_filled_entries_percent', 'users_empty_entries_percent' ];  * @param IConfig $config
	 * @param SettingsController $settings
     * @param mixed $UserId
	 * @param Manager $userManager
	 * @param Helper $helper
	 * @param UserMapping $userMapping
	 * @param GroupMapping $groupMapping
	 */
	public function __construct( $AppName, IRequest $request, IConfig $config, SettingsController $settings, $UserId, Manager $userManager, Helper $helper, UserMapping $userMapping, GroupMapping $groupMapping, IDBConnection $db ) {
		// check we have a logged in user
		\OCP\User::checkLoggedIn();
		parent::__construct( $AppName, $request );
		// get database connection
		$this->db = $db;
        // get the settings controller
        $this->settings = $settings;
		// get the config module for user settings
		$this->config = $config;
		// save the apps name
		$this->AppName = $AppName;
		// get the current users id
		$this->uid = $UserId;
		// load ldap configuration from the user_ldap app
		$this->load_config( $userManager, $helper, $userMapping, $groupMapping );
		// connect to the ldap server
		$this->connection = ldap_connect( $this->host, $this->port );
		
		// TODO(hornigal): catch ldap errors
		ldap_set_option( $this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_version);
		ldap_bind( $this->connection, $this->admin_dn, $this->admin_pwd );
		
		// load translation files
		$this->l = \OC::$server->getL10N( 'ldapcontacts' );
        
        // define ldap attributes
        $this->contacts_available_attributes = $this->settings->getSetting( 'user_ldap_attributes', false );
		
		// set the ldap attributes that are filled out by default
		$this->contacts_default_attributes = [ $this->settings->getSetting( 'login_attribute', false ), 'givenname', 'sn' ];
	}
	
	/**
	 * get the user_display_name variable
	 * 
	 * @NoAdminRequired
	 */
	public function getUserDisplayName( $DataResponse=true ) {
		if( $DataResponse ) return new DataResponse( [ 'data' => $this->user_display_name, 'status' => 'success' ] );
		else return $this->user_display_name;
	}
	
	/**
	 * loads the ldap configuration from the user_ldap app
	 * 
	 * @param string $prefix
	 */
	private function load_config( Manager $userManager, Helper $helper, UserMapping $userMapping, GroupMapping $groupMapping, $prefix = '' ) {
		// load configuration
		$ldapWrapper = new \OCA\User_LDAP\LDAP();
		$connection = new \OCA\User_LDAP\Connection( $ldapWrapper );
		$config = $connection->getConfiguration();
		// check if this is the correct server or if we have to use a prefix
		if( empty( $config['ldap_host'] ) ) {
			$connection = new \OCA\User_LDAP\Connection( $ldapWrapper, 's01' );
			$config = $connection->getConfiguration();
		}
		
		// get the users dn
		$this->access = new \OCA\User_LDAP\Access( $connection, $ldapWrapper, $userManager, $helper );
		$this->access->setUserMapper( $userMapping );
		$this->access->setGroupMapper( $groupMapping );
		$this->user_dn = $this->access->username2dn( $this->uid );
		
		// put the needed configuration in the local variables
		$this->host = $config['ldap_host'];
		$this->port = $config['ldap_port'];
		$this->base_dn = $config['ldap_base_users'];
		$this->group_dn = $config['ldap_base_groups'];
		$this->admin_dn = $config['ldap_dn'];
		$this->admin_pwd = $config['ldap_agent_password'];
		$this->user_filter =  $config['ldap_userlist_filter'];
		$this->user_filter_specific = $config['ldap_login_filter'];
		$this->group_filter = $config['ldap_group_filter'];
		$this->group_filter_specific = '(&' . $config['ldap_group_filter'] . '(gidNumber=%gid))';
		$this->ldap_version = 3;
		$this->user_display_name = $config['ldap_display_name'];
	}
	
	/**
	 * returns the main template
	 * 
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
        $params = [];
        // get the users possible ldap attributes
        if( $user_ldap_attributes = $this->settings->getSetting( 'user_ldap_attributes', false ) ) {
            $params['user_ldap_attributes'] = $user_ldap_attributes;
        }
        // get the users login attribute
        if( $login_attribute = $this->settings->getSetting( 'login_attribute', false ) ) {
            $params['login_attribute'] = $login_attribute;
        }
        
        // return the main template
		return new TemplateResponse( 'ldapcontacts', 'main', $params );
	}

	/**
	 * get all users
	 *
	 * @NoAdminRequired
	 */
	public function load() {
		return new DataResponse( $this->getUsers() );
	}
	
	/**
	* shows a users own data
	* 
	* @NoAdminRequired
	*/
	public function show() {
		// get the users info
		return new DataResponse( $this->getUsers( $this->uid ) );
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
	* @param string $data		jQuery parsed form
	*/
	public function update( $data ) {
		// parse given data
		parse_str( urldecode( $data ), $array );

		$modify = [];
		foreach( $array['user_ldap_attributes'] as $attribute => $value ) {
			$value = trim( $value );
			$attribute = str_replace( "'", "", $attribute );
			
			// remove, add or modify attribute
			$modify[ $attribute ] = $value === '' ? [] : $value;
		}
		
		// get own dn
		if( !$dn = $this->get_own_dn() ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Something went wrong while saving your data' ) ), 'status' => 'error' ) );
		
		// update given values
		if( ldap_modify( $this->connection, $dn, $modify ) ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Your data has successfully been saved' ) ), 'status' => 'success' ) );
		else return new DataResponse( array( 'data' => array( 'message' => $this->l->t( 'Something went wrong while saving your data' ) ), 'status' => 'error' ) );
	}
	
	/**
	 * get all users from the LDAP server
	 * 
	 * @NoAdminRequired
	 * 
	 * @param string $uid
	 */
	protected function getUsers( $uid=false ) {
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		
		// get a specific user filter if a specific user is requested
		if( $uid ) {
			// get the users dn
			$dn = $this->access->username2dn( $uid );
			// if no user was found, abort
			if( !$dn ) return false;
			$entry_id = $this->getEntryLdapId( $dn );
			$user_filter = '(&' . $this->user_filter . '(' . $entry_id_attribute . '=' . ldap_escape( $entry_id ) . '))';
		}
		else $user_filter = $this->user_filter;
		
		$request = ldap_search( $this->connection, $this->base_dn, $user_filter, [ '*', $entry_id_attribute ] );
		
		// if no user was found, abort
		if( is_bool( $request ) ) return false;
		
		$results = ldap_get_entries( $this->connection, $request );
		
		unset( $results['count'] );
		$return = array();
		$ldap_attributes = array_merge( $this->settings->getSetting( 'user_ldap_attributes', false ), [ $this->user_display_name => '', $this->settings->getSetting( 'user_group_id_attribute', false ) => '' ] );
		$id = 1;
		
		// get all hidden users
		$hidden = $this->adminGetEntryHidden( 'user', false );
		
		foreach( $results as $i => $result ) {
				// check that the user is not hidden
				$is_hidden = false;
				foreach( $hidden as $user ) {
					if( $result[ $entry_id_attribute ] == $user[ $entry_id_attribute ] ) {
						$is_hidden = true;
						break;
					}
				}
				if( $is_hidden ) continue;
			
			$tmp = array();
			foreach( $ldap_attributes as $attribute => $value ) {
				// check if the value exists for the user
				if( isset( $result[ $attribute ] ) ) {
					if( is_array( $result[ $attribute ] ) )
						$tmp[ $attribute ] = trim( $result[ $attribute ][0] );
					else
						$tmp[ $attribute ] = trim( $result[ $attribute ] );
				}
			}
			
			// a contact has to have a name
			// TODO: check if it might be useful to put a placeholder here if no name is given
			if( empty( trim( $tmp[ $this->user_display_name ] ) ) ) continue;
			$tmp['name'] = $tmp[ $this->user_display_name ];
			
			// save the current id
			$tmp['id'] = $id;
			
			// get the users groups
			$groups = $this->get_user_groups( $tmp[ $this->settings->getSetting( 'user_group_id_attribute', false ) ] );
			if( $groups ) $tmp['groups'] = $groups;
			else $tmp['groups'] = array();
			
			// delete all empty entries
			foreach( $tmp as $key => $value ) {
				if( !is_array( $value ) && empty( trim( $value ) ) ) unset( $tmp[ $key ] );
			}
			
			array_push( $return, $tmp );
			$id++;
		}
		
		// order the users
		usort( $return, [ $this, 'order_ldap_contacts' ] );
		
		return $return;
	}
				  
	/**
	 * orders the given user array by the ldap attribute selected by the user
	 * 
	 * @param array $a
	 * @param array $b
	 */
	protected function order_ldap_contacts( $a, $b ) {
		$order_by = $this->config->getUserValue( $this->uid, $this->AppName, 'order_by' );
		// check if the arrays can be compared
		if( !isset( $a[ $order_by ], $b[ $order_by ] ) ) return 1;
		// compare
		return $a[ $order_by ] <=> $b[ $order_by ];
	}		  

	/**
	 * returns all the groups the user is a member in
	 * 
	 * @param $uid		the users uid
	 */
	protected function get_user_groups( $user_group_id_attribute ) {

		// construct the filter
		$user_group_id_group_attribute = $this->settings->getSetting( 'user_group_id_group_attribute', false );
		$filter = '(&' . $this->group_filter . '(' . $user_group_id_group_attribute . '=' . $user_group_id_attribute . '))';
		
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
		// get the users dn
		$dn = $this->access->username2dn( $uid );
		// run a query with the found dn
		$request = ldap_search( $this->connection, $dn, '(objectClass=*)', array( $this->settings->getSetting( 'user_group_id_attribute', false ) ) );
		
		$entries = ldap_get_entries($this->connection, $request);
		// check if request was successful
		if( $entries['count'] < 1 ) return false;
		else return $entries[0][ $this->settings->getSetting( 'login_attribute', false ) ][0];
	}
	
	/**
	 * get the users own dn
	 */
	protected function get_own_dn() {
		// check this user actually has a uid
		if( empty( $this->uid ) ) return false;
		// get the users dn
		return $this->access->username2dn( $this->uid );
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
		$dn = $this->access->username2dn( $uid );
		// check this is an ldap user
		if( empty( $dn ) ) return false;
		// get the users entry id
		$entry_id = $this->getEntryLdapId( $dn );
		
		// check if the user is already hidden
		if( $this->userHidden( $entry_id ) ) return true;
		
		// hide the user
		$sql = "INSERT INTO *PREFIX*ldapcontacts_hidden_entries SET entry_id = ?, type = 'user'";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $entry_id, \PDO::PARAM_STR );
		$stmt->execute();
		
		// check for sql errors
		if( $stmt->errorCode() == '00000' ) return true;
		else return false;
	}
	
	/**
	 * get an ldap entrys unique id
	 * 
	 * @param string $dn
	 */
	protected function getEntryLdapId( string $dn ) {
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		// fetch the entrys info from the ldap server
		$request = ldap_search( $this->connection, $dn, '(objectClass=*)', array( $entry_id_attribute ) );
		$results = ldap_get_entries( $this->connection, $request );
		// check if an entry was found
		if( $results['count'] == 0 ) return false;
		
		// get the entry id from the ldap info
		if( is_array( $results[0][ $entry_id_attribute ] ) ) $entry_id = $results[0][ $entry_id_attribute ][0];
		else $entry_id = $results[0][ $entry_id_attribute ];
		
		return $entry_id;
	}
	
	/**
	 * checks if the given user is already hiden
	 * 
	 * @param string $user_id
	 * 
	 * @return bool		wether the user is hidden or not
	 */
	private function userHidden( $user_id ) {
		// get all hidden users
		$hidden = $this->adminGetEntryHidden( 'user', false );
		// check if the given user is one of them
		return in_array( $uid, $hidden );
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
		// get the users dn
		$dn = $this->access->username2dn( $uid );
		// run a query with the found dn
		$request = ldap_search( $this->connection, $dn, '(objectClass=*)', array( 'objectClass' ) );
		
		$results = ldap_get_entries( $this->connection, $request );
		// check if something has been found
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'] ) || $results['count'] !== 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		// go through every objectclass and check if it is the shadowAccount attribute we have to remove
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class === "shadowAccount" ) unset( $results[0]['objectclass'][ $i ] );
		}
		
		// reorder array
		$objectclass = array_values( $results[0]['objectclass'] );
		// save the modified data
		return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $objectclass ) );
	}
	
	/**
	 * gets all entries of the given type that are hidden
	 * 
	 * @param string $type
	 * @param bool $DataResponse
	 */
	public function adminGetEntryHidden( string $type, bool $DataResponse=true ) {
		$sql = "SELECT entry_id FROM *PREFIX*ldapcontacts_hidden_entries WHERE type = ?";
		$stmt = $this->db->prepare( $sql );
		$stmt->bindParam( 1, $type, \PDO::PARAM_STR );
		$stmt->execute();
		
		// check for sql errors
		if( $stmt->errorCode() != '00000' ) {
			if( $DataResponse ) return new DataResponse( array( 'data' => array( 'message' => $this->l->t( "Hidden entries couldn't be loaded" ) ), 'status' => 'error' ) );
			else return false;
		}
		
		// get all hidden entries
		$tmp = [];
		while( $hidden = $stmt->fetchColumn() ) {
			array_push( $tmp, $hidden );
		}
		$stmt->closeCursor();
		
		// get additional data for each entry
		$entries = [];
		foreach( $tmp as $entry ) {
			array_push( $entries, $this->getLdapEntryById( $entry, $type ) );
		}
		
		// return fetched entries
		if( $DataResponse ) return new DataResponse( array( 'data' => $entries, 'status' => 'success' ) );
		else return $entries;
	}
	
	/**
	 * gets data
	 * 
	 * @param string $entry_id
	 * @param string $type
	 */
	protected function getLdapEntryById( string $entry_id, string $type='' ) {
		$entry_id_attribute = $this->settings->getSetting( 'entry_id_attribute', false );
		$request = ldap_search( $this->connection, $this->base_dn, '(' . $entry_id_attribute . '=' . ldap_escape( $entry_id ) . ')', [ '*', $entry_id_attribute ] );
		$entry = ldap_get_entries( $this->connection, $request )[0];
		
		// add the entry id
		$entry[ 'ldapcontacts_entry_id' ] = $entry_id;
		
		// add the entrys name
		switch( $type ) {
			case 'user':
				$name = $entry[ $this->user_display_name ];
				$entry['ldapcontacts_name'] = is_array( $name ) ? $name[0] : $name;
				break;
			case 'group':
				// TODO: add group name
				break;
		}
		
		return $entry;
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
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'], $results[0]['cn'][0] ) || $results['count'] !== 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		$shadowGiven = false;
		// go through every objectclass and check if it is the shadowAccount attribute is already there
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class === "shadowAccount" ) {
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
		if( !isset( $results['count'], $results[0]['objectclass'], $results[0]['dn'] ) || $results['count'] !== 1 ) return False;
		// remove the count variable from the object class
		unset( $results[0]['objectclass']['count'] );
		// go through every objectclass and check if it is the shadowAccount attribute we have to remove
		foreach( $results[0]['objectclass'] as $i => $class ) {
			if( $class === "shadowAccount" ) unset( $results[0]['objectclass'][ $i ] );
		}
		// save the modified data
		return ldap_modify( $this->connection, $results[0]['dn'], array( 'objectclass' => $results[0]['objectclass'], 'uid' => array() ) );
	}
    
    /**
     * get all available statistics
     */
    public function getStatistics() {
        // get them all
        $data = [ 'status' => 'success' ];
        foreach( $this->statistics as $type ) {
            // get the statistic
            $stat = $this->getStatistic( $type )->getData();
            // check if something went wrong
            if( $stat['status'] !== 'success' ) {
                return new DataResponse( [ 'status' => 'error' ] );
            }
            // add the data to the bundle
            $data[ $type ] = $stat['data'];
        }
        
        // return collected statistics
        return new DataResponse( $data );
    }
    
    /**
     * computes the wanted statistic
     * 
     * @param string $type      the type of statistic to be returned
     */
    public function getStatistic( $type ) {
        switch( $type ) {
            case 'entries':
                $data = $this->entryAmount();
                break;
            case 'entries_filled':
                $data = $this->entriesFilled();
                break;
            case 'entries_empty':
                $data = $this->entriesEmpty();
                break;
            case 'entries_filled_percent':
                $data = $this->entriesFilledPercent();
                break;
            case 'entries_empty_percent':
                $data = $this->entriesEmptyPercent();
                break;
            case 'users':
                $data = $this->userAmount();
                break;
            case 'users_filled_entries':
                $data = $this->usersFilledEntries();
                break;
            case 'users_empty_entries':
                $data = $this->usersEmtpyEntries();
                break;
            case 'users_filled_entries_percent':
                $data = $this->usersFilledEntriesPercent();
                break;
            case 'users_empty_entries_percent':
                $data = $this->usersEmptyEntriesPercent();
                break;
            default:
                // no valid statistic given
                return new DataResponse( [ 'status' => 'error' ] );
        }
        // return gathered data
        return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
    }
    
    /**
     * get all user attributes that aren't filled from the start
     */
    protected function userNonDefaultAttributes() {
        // get all user attributes
        $attributes = $this->contacts_available_attributes;
        // remove all defaults
        foreach( $this->contacts_default_attributes as $key ) {
            unset( $attributes[ $key ] );
        }
        // return non default attributes
        return $attributes;
    }
    
    /**
     * amount of entries users can edit
     */
    protected function entryAmount() {
        // get all attributes the users can edit
        $attributes = $this->userNonDefaultAttributes();
        // get all users and their data
        $users = $this->getUsers();
        // init counter
        $amount = 0;
        
        // count the entries
        foreach( $users as $user ) {
            foreach( $attributes as $attr ) {
                $amount++;
            }
        }
        
        // return the counted amount
        return $amount;
    }
    
    /**
     * amount of entries the users have filled out
     */
    protected function entriesFilled() {
        // get all attributes the users can edit
        $attributes = $this->userNonDefaultAttributes();
        // get all users and their data
        $users = $this->getUsers();
        // init counter
        $amount = 0;
        
        // count the entries
        foreach( $users as $user ) {
            foreach( $attributes as $attr => $v ) {
                // check if the entry is filled
                if( !empty( $user[ $attr ] ) ) {
                    $amount++;
                }
            }
        }
        
        // return the counted amount
        return $amount;
    }
    
    /**
     * amount of entries the users haven't filled out
     */
    protected function entriesEmpty() {
        return $this->entryAmount() - $this->entriesFilled();
    }
    
    /**
     * amount of entries the users have filled out, in percent
     */
    protected function entriesFilledPercent() {
        return round( $this->entriesFilled() / $this->entryAmount() * 100, 2 );
    }
    
    /**
     * amount of entries the users haven't filled out, in percent
     */
    protected function entriesEmptyPercent() {
        return round( $this->entriesEmpty() / $this->entryAmount() * 100, 2 );
    }
    
    /**
     * amount of registered users
     */
    protected function userAmount() {
        return count( $this->getUsers() );
    }
    
    /**
     * how many users have filled at least one of their entries
     */
    protected function usersFilledEntries() {
        // get all attributes the users can edit
        $attributes = $this->userNonDefaultAttributes();
        // get all users and their data
        $users = $this->getUsers();
        // init counter
        $amount = 0;
        
        // count the entries
        foreach( $users as $user ) {
            foreach( $attributes as $attr => $v ) {
                // check if the entry is filled
                if( !empty( $user[ $attr ] ) ) {
                    $amount++;
                    break;
                }
            }
        }
        
        // return the counted amount
        return $amount;
    }
    
    /**
     * how many users have filled none of their entries
     */
    protected function usersEmtpyEntries() {
        return $this->userAmount() - $this->usersFilledEntries();
    }
    
    /**
     * how many users have filled at least one of their entries, in percent
     */
    protected function usersFilledEntriesPercent() {
        return round( $this->usersFilledEntries() / $this->userAmount() * 100, 2 );
    }
    
    /**
     * how many users have filled none of their entries, in percent
     */
    protected function usersEmptyEntriesPercent() {
        return round( $this->usersEmtpyEntries() / $this->userAmount() * 100, 2 );
    }
	
	/**
	 * get the id LDAP users are identified by in this app
	 * 
	 * @param string $uid
	 * 
	 * @return string
	 */
	protected function getUserLdapId( $uid ) {
		$user_dn = $this->access->username2dn( $this->uid );
		$request = ldap_search( $this->connection, $user_dn, '(objectClass=*)', array( $this->settings->getSetting( 'entry_id_attribute', false ) ) );
		$result = ldap_get_entries( $this->connection, $request )[0];
		
		return $result[ strtolower( $this->settings->getSetting( 'entry_id_attribute', false ) ) ][0];
	}
}
