<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Alexander Hornig 2018
 */

namespace OCA\LdapContacts\Notification;

use OCP\Notification\INotifier;
use OCP\Notification\INotification;

class Notifier implements INotifier {
	protected $factory;
	/** @var string */
	protected $AppName = 'ldapcontacts';

	/**
	 * @param \OCP\L10N\IFactory $factory
	 */
	public function __construct( \OCP\L10N\IFactory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 */
	public function prepare( INotification $notification, $languageCode ) {
		if( $notification->getApp() !== $this->AppName ) {
			// Not my app => throw exception
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get( $this->AppName, $languageCode );

		// replace subject with real string
		switch( $notification->getSubject() ) {
			// Deal with known subjects
			case 'contact_info_not_filled':
				$notification->setParsedSubject( (string) $l->t( "Your contact information hasn't been filled out yet" ) );
				break;
			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
		
		// replace message with real string
		switch( $notification->getMessage() ) {
			case 'fill_out_now':
				$notification->setParsedMessage( '<a href="/apps/ldapcontacts?action=edit">' . $l->t( 'Fill out now >>' ) . '</a>' );
				break;
		}
		
		// return parsed notification
		return $notification;
	}
}