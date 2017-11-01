<?php
/**
 * Nextcloud - ldapcontacts
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alexander Hornig <alexander@hornig-software.com>
 * @copyright Hornig Software 2017
 */

namespace OCA\LdapContacts\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

Class AdminStatistics implements ISettings {
	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		return new TemplateResponse( 'ldapcontacts', 'statistics' );
	}
	
	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'serverinfo';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 70;
	}
}