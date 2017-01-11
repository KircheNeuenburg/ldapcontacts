<?php
/**
 * saves the given setting
 */

// security checks
OCP\JSON::checkAppEnabled( 'ldapcontacts' );
OCP\User::checkAdminUser();
OCP\JSON::callCheck();

$success = false;
// load translation files
$l = \OC::$server->getL10N( 'ldapcontacts' );

// check if the login attribute should be saved
if( isset( $_POST['login_attribute'] ) ) {
	\OCP\Config::setAppValue( 'ldapcontacts', 'login_attribute', $_POST['login_attribute'] );
	$success = true;
}

// check if the login data url should be saved
if( isset( $_POST['edit_login_url'] ) ) {
	\OCP\Config::setAppValue( 'ldapcontacts', 'edit_login_url', $_POST['edit_login_url'] );
	$success = true;
}

// return success or failure message
if( $success ) OCP\JSON::success( array( 'data' => array( 'message' => $l->t( 'Settings saved' ) ) ) );
else OCP\JSON::error( array( 'data' => array( 'message' => $l->t( 'Something went wrong while saving the settings. Please try again.' ) ) ) );
