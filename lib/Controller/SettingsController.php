<?php
namespace OCA\LdapContacts\Controller;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;

class SettingsController extends Controller {
	/** @var string **/
	protected $appName;
	/** @var IConfig **/
	protected $config;
	/** @var string **/
	protected $uid;
	/** @var IL10N **/
	protected $l;
	/** @var string[] **/
	protected $array_settings = [ 'userLdapAttributes', 'hiddenUsers', 'hiddenGroups' ];
	/** @var array **/
  protected $default;
	/** @var array **/
	protected $user_default;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N l10n
	 * @param IConfig $config
   * @param string $userId
	 */
	public function __construct(string $appName,
															IRequest $request,
															IL10N $l10n,
															IConfig $config,
															string $userId) {
		parent::__construct($appName, $request);
		// set class variables
		$this->appName = $appName;
		$this->config = $config;
		// load translation files
		$this->l = $l10n;
		// get the current users id
		$this->uid = $userId;
    // set default values
    $this->default = [
      // available data
      'userLdapAttributes' => [
				[ 'name' => 'mail', 'label' => $this->l->t( 'Mail' ) ],
				[ 'name' => 'givenname', 'label' => $this->l->t( 'First Name' ) ],
				[ 'name' => 'sn', 'label' => $this->l->t( 'Last Name' ) ],
				[ 'name' => 'street', 'label' => $this->l->t( 'Address' ) ],
				[ 'name' => 'postalcode', 'label' => $this->l->t( 'zip Code' ) ],
				[ 'name' => 'l', 'label' => $this->l->t( 'City' ) ],
				[ 'name' => 'homephone', 'label' => $this->l->t( 'Phone' ) ],
				[ 'name' => 'mobile', 'label' => $this->l->t( 'Mobile' ) ]
			],
			'hiddenUsers' => [],
			'hiddenGroups' => []
    ];
    // set default user values
    $this->user_default = [
      'order_by' => 'givenname'
    ];
	}

	/**
	 * gets the value for the given setting
	 *
	 * @NoAdminRequired
	 * @param string $key
	 * @param bool $DataResponse=true
	 * @return string|DataResponse
	 */
	public function getUserValue(string $key, bool $DataResponse=true) {
		// check if this is a valid setting
		if( !isset( $this->user_default[ $key ] ) ) return false;
        // get the setting
        $data = $this->config->getUserValue( $this->uid, $this->appName, $key, $this->user_default[ $key ] );
        // return message and data if given
		if( $DataResponse ) {
			if( $data !== false ) return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
			else return new DataResponse( [ 'status' => 'error' ] );
		}
		else return $data;
	}

	/**
	 * saves the given user value and returns a DataResponse
	 *
	 * @NoAdminRequired
	 * @param string $key
	 * @param string $value
	 * @return DataResponse
	 */
	public function setUserValue(string $key, string $value) {
		if( isset( $this->user_default[ $key ] ) && !$this->config->setUserValue( $this->uid, $this->appName, $key, $value ) ) {
			return new DataResponse( array( 'message' => $this->l->t( 'Settings saved' ), 'status' => 'success' ) );
    }
		else {
			return new DataResponse( array( 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ), 'status' => 'error' ) );
		}
	}

	/**
	 * returns the value for the given general setting
   *
	 * @NoAdminRequired
   * @param string $key
   * @param bool $DataResponse=true
	 * @return DataResponse
	 */
	public function getSetting(string $key, bool $DataResponse=true) {
		// check if this is a valid setting
		if( !isset( $this->default[ $key ] ) ) return false;
    // get the setting
    $data = $this->config->getAppValue( $this->appName, $key, $this->default[ $key ] );
    // return message and data if given
    if( !is_bool( $data ) ) {
			// if this is an array setting, decode it
			if( in_array( $key, $this->array_settings ) && !is_array( $data ) ) $data = json_decode( $data, true );

			// return the data
      if( $DataResponse ) return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
      else return $data;
    }
		else if( $DataResponse ) return new DataResponse( [ 'status' => 'error' ] );
    else return false;
	}

	/**
	 * returns all settings from this app
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function getSettings() {
		// output buffer
		$data = array();
    $success = true;
		// go through every existing setting
		foreach( $this->default as $key => $v ) {
			// get the settings value
			$response = $this->getSetting( $key )->getData();
			// if the setting was successfuly fetched, put it to the output
			if( $response['status'] === 'success' ){
				$data[ $key ] = $response['data'];
			}
			else {
				$success = false;
			}
		}
		// return the buffered data
		if( $success ) return new DataResponse( [ 'data' => $data, 'status' => 'success' ] );
    else return new DataResponse( [ 'status' => 'error' ] );
	}

	/**
	 * updates the given setting
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	public function updateSetting(string $key, $value) {
		$key = str_replace( "'", "", $key );

		// check if the setting is an actual setting this app has
		if( !isset( $this->default[ $key ] ) ) return false;

		/** special processing for certain settings **/
		if( $key === 'userLdapAttributes' ) {
			$array = [];
			// go through every attribute
			foreach( $value as $i => &$attr ) {
				// process the attributes name
				$attr['name'] = strtolower( trim( $attr['name'] ) );
				if( empty( $attr['name'] ) ) {
					unset( $value[ $i ] );
					continue;
				}

				// process the attributes label
				$attr['label'] = trim( $attr['label'] );
				if( empty( $attr['label'] ) ) $attr['label'] = $attr['name'];
			}
		}

		// convert the value if it is an array
		if( is_array( $value ) ) $value = json_encode( $value );
		// save the setting
		return !$this->config->setAppValue( $this->appName, $key, $value );
	}

	/**
	 * updates all the given settings
	 *
	 * @param array $settings
	 * @return DataResponse
	 */
	public function updateSettings(array $settings) {
		$success = true;
		// go through every setting and update it
		foreach( $settings as $key => $value ) {
			// update the setting
			$success &= $this->updateSetting( $key, $value );
		}
		// return message
		if( $success ) return new DataResponse( [ 'message' => $this->l->t( 'Settings saved' ), 'status' => 'success'] );
		else return new DataResponse( [ 'message' => $this->l->t( 'Something went wrong while saving the settings. Please try again.' ), 'status' => 'error' ] );
	}

	/**
	 * remove the given key from the given settings array
	 *
	 * @param string $settingKey		the key for the settings array to be modifyed
	 * @param string $key		the key to be removed from the array
	 * @return bool
	 */
	public function arraySettingRemoveKey(string $settingKey, string $key) {
		// get the current setting
		$setting = $this->getSetting($settingKey, false);

		// check if the setting is an array
		if (!is_array($setting)) return false;

		// remove the given key from the array
		unset($setting[ $key ]);
		// update the setting
		return $this->updateSetting($settingKey, $setting);
	}

	/**
	 * adds a value to the given settings array
	 *
	 * @param string $settingKey		the key for the settings array to be modifyed
	 * @param mixed $value	the value to add
	 * @param string $key=''		the key to be removed from the array
	 */
	public function arraySettingAddKey(string $settingKey, $value, string $key='') {
		$key = trim($key);

		// get the current setting
		$setting = $this->getSetting($settingKey, false);

		// check if the setting is an array
		if (!is_array($setting)) return false;

		// add the value
		if (empty($key)) $setting[] = $value;
		else $setting[ $key ] = $value;

		// update the setting
		return $this->updateSetting( $settingKey, $setting );
	}
}
