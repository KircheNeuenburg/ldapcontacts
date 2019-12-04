<?php
namespace OCA\LdapContacts\Controller;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\LdapContacts\Controller\SettingsController;
use OCA\LdapContacts\Exceptions\LdapEntityNotFoundException;
use OCA\LdapContacts\LDAP\EntityFactory;
use OCA\LdapContacts\LDAP\LdapUser;
use OC\ServerNotAvailableException;

class ContactController extends Controller {
	/** @var string **/
	protected $appName;
	/** @var IConfig **/
	protected $config;
	/** @var SettingsController **/
	protected $settings;
	/** @var string **/
	protected $uid;
	/** @var IL10N **/
	protected $l;
	/** @var EntityFactory **/
	protected $entityFactory;

  /**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param SettingsController $settings
	 * @param string $UserId
	 * @param IL10N $l10n
	 * @param EntityFactory $entityFactory
	 */
	public function __construct(string $appName,
															IRequest $request,
															IConfig $config,
															SettingsController $settings,
															string $UserId,
															IL10N $l10n,
															EntityFactory $entityFactory) {
		parent::__construct( $appName, $request );
		$this->appName = $appName;
		$this->settings = $settings;
		$this->uid = $UserId;
		$this->l = $l10n;
		$this->config = $config;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * returns the main template
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @return TemplateResponse
	 */
	public function index() {
		return new TemplateResponse( 'ldapcontacts', 'main' );
	}

	/**
	 * get all users
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function users() {
		return new DataResponse( $this->getUsers() );
	}

	/**
	 * get all hidden users
	 *
	 * @NoCSRFRequired
	 * @return DataResponse
	 */
	public function hiddenUsers() {
		return new DataResponse( $this->getHiddenUsers() );
	}

	/**
	 * shows a users own data
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function show() {
		try {
			$user = $this->entityFactory->getUserByNcId($this->uid);
		}
		catch (LdapEntityNotFoundException $e) {
			return new DataResponse([ 'status' => 'warning', 'message' => $this->l->t("Your own data couldn't be fetched") ]);
		}
		$user->loadOwnGroups();
		return $user->toDataResponse();
	}

	/**
	 * shows all groups
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 */
	public function groups() {
		return new DataResponse( $this->getGroups() );
	}

	/**
	 * shows all hidden groups
	 *
	 * @return DataResponse
	 */
	public function hiddenGroups() {
		return new DataResponse( $this->getHiddenGroups() );
	}

	/**
	 * updates a users own data
	 *
	 * @param string $data
	 * @return DataResponse
	 */
	public function update( $data ) {
		// modify user
		$user = $this->entityFactory->getUserByNcId($this->uid);
		$status = $user->getUuid() === $data['uuid'] ? $user->updateData($data['ldapAttributes']) : false;
		// return response
		if ($status) return new DataResponse([ 'status' => 'success', 'message' => $this->l->t( 'Your data has successfully been saved' ) ]);
		else return new DataResponse([ 'status' => 'error', $this->l->t( 'Something went wrong while saving your data' ) ]);
	}

	/**
	 * get all users from the LDAP server
	 *
	 * @param bool $ignore_hidden
	 * @return array
	 */
	public function getUsers(bool $ignoreHidden=false) {
		$userObjectList = $this->entityFactory->getAllUsers();
		$userDataList = [];

		// order the users
		usort( $userObjectList, [ $this, 'orderLdapUserList' ] );

		// process users
		foreach ($userObjectList as $i => $user) {
			// remove hidden
			if (!$ignoreHidden && $user->isHidden()) continue;
			// load user groups
			$user->loadOwnGroups($ignoreHidden);
			// get the users data
			$userDataList[] = $user->toDataArray();
		}

		return [ 'status' => 'success', 'data' => $userDataList ];
	}

	/**
	 * get all hidden users
	 *
	 * @param array
	 */
	protected function getHiddenUsers() {
		$hiddenUsers = [];
		$hiddenUserUUIDs = $this->settings->getSetting('hiddenUsers', false);

		// get the user object and data for each given uuid
		foreach ($hiddenUserUUIDs as $uuid) {
			try {
				$user = $this->entityFactory->getUserByUuid($uuid);
			}
			catch (LdapEntityNotFoundException $e) {
				// skip invalid users
				continue;
			}
			// get the users data
			$hiddenUsers[ $uuid ] = $user->toDataArray();
		}

		return [ 'status' => 'success', 'data' => $hiddenUsers ];
	}

	/**
	 * get all hidden groups
	 *
	 * @param array
	 */
	protected function getHiddenGroups() {
		$hiddenGroups = [];
		$hiddenGroupUUIDs = $this->settings->getSetting('hiddenGroups', false);

		// get the group object and data for each given uuid
		foreach ($hiddenGroupUUIDs as $uuid) {
			try {
				$group = $this->entityFactory->getGroupByUuid($uuid);
			}
			catch (LdapEntityNotFoundException $e) {
				// skip invalid groups
				continue;
			}

			// get the groups data
			$hiddenGroups[ $uuid ] = $group->toDataArray();
		}

		return [ 'status' => 'success', 'data' => $hiddenGroups ];
	}

	/**
	 * orders the given user array by the ldap attribute selected by the user
	 *
	 * @param LdapUser $userA
	 * @param LdapUser $userB
	 * @return int
	 */
	protected function orderLdapUserList(LdapUser $userA, LdapUser $userB) {
		$order_by = $this->settings->getUserValue('order_by', false);

		// check if the arrays can be compared
		if ($userA->getAttributeValue($order_by) === false || $userB->getAttributeValue($order_by) === false) return 1;
		// compare
		return $userA->getAttributeValue($order_by) <=> $userB->getAttributeValue($order_by);
	}

	/**
	 * returns an array of all existing groups
	 *
	 * @param bool $ignore_hidden
	 * @return array
	 */
	protected function getGroups(bool $ignoreHidden=false) {
		$groupObjectList = $this->entityFactory->getAllGroups();
		$groupDataList = [];

		// order the groups
		usort( $groupObjectList, function( $groupA, $groupB ) {
			return $groupA->getTitle() <=> $groupB->getTitle();
		});

		// process groups
		foreach ($groupObjectList as $i => $group) {
			// remove hidden
			if (!$ignoreHidden && $group->isHidden()) continue;
			// get the groups data
			$groupDataList[] = $group->toDataArray();
		}

		return [ 'status' => 'success', 'data' => $groupDataList ];
	}

	/**
	 * shows the given LDAP entry
	 *
	 * @param string $type 'user' or 'group'
	 * @param string $uuid
	 */
	public function showEntity(string $type, string $uuid) {
		$settingsKey;
		// check entity type
		switch ($type) {
			case EntityFactory::UserEntity:
				$settingsKey = 'hiddenUsers';
				break;
			case EntityFactory::GroupEntity:
				$settingsKey = 'hiddenGroups';
				break;
			default:
				return new DataResponse([ 'status' => 'error', 'message' => $this->l->t( 'Unknown entity type' ) ]);
		}

		// get currently hidden entities
		$hiddenEntities = $this->settings->getSetting($settingsKey, false);
		// check if the given entity is hidden
		$givenEntityKey = array_search($uuid, $hiddenEntities);
		// show the entity again
		$success = true;
		if ($givenEntityKey !== false) {
			unset($hiddenEntities[ $givenEntityKey ]);
			$success = $this->settings->updateSetting($settingsKey, $hiddenEntities);
		}

		// return message to user
		if ($success) {
			$message = $type === EntityFactory::UserEntity ? $this->l->t( 'User is now visible again' ) : $this->l->t( 'Group is now visible again' );
			return new DataResponse( [ 'message' => $message, 'status' => 'success' ] );
		}
		else {
			return new DataResponse( [ 'message' => $message, 'status' => 'error' ] );
			$message = $type === EntityFactory::UserEntity ? $this->l->t( 'An error occured while making the user vivible again' ) : $this->l->t( 'An error occured while making the group visible again' );
		}
	}

	/**
	 * hides the given LDAP entry
	 *
	 * @param string $type 'user' or 'group'
	 * @param string $uuid
	 */
	public function hideEntity(string $type, string $uuid) {
		$entity;
		$settingsKey;

		// check if the entity exists
		try {
			switch ($type) {
				case EntityFactory::UserEntity:
					$entity = $this->entityFactory->getUserByUuid($uuid);
					$settingsKey = 'hiddenUsers';
					break;
				case EntityFactory::GroupEntity:
					$entity = $this->entityFactory->getGroupByUuid($uuid);
					$settingsKey = 'hiddenGroups';
					break;
				default:
					return new DataResponse([ 'status' => 'error', 'message' => $this->l->t( 'Unknown entity type' ) ]);
			}
		}
		catch (LdapEntityNotFoundException $e) {
			return new DataResponse([ 'status' => 'error', 'message' => $this->l->t( 'Entity not found' ) ]);
		}

		// hide the entity
		if ($this->settings->arraySettingAddKey($settingsKey, $entity->getUuid())) {
			$message = $type === EntityFactory::UserEntity ? $this->l->t( 'User is now hidden' ) : $this->l->t( 'Group is now hidden' );
			return new DataResponse( [ 'message' => $message, 'status' => 'success' ] );
		}
		else {
			$message = $type === EntityFactory::UserEntity ? $this->l->t( 'An error occured while hiding the user' ) : $this->l->t( 'An error occured while hiding the group' );
			return new DataResponse( [ 'message' => $message, 'status' => 'error' ] );
		}
	}
}
