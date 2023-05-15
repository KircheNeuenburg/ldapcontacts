<?php
namespace OCA\LdapContacts\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class StatisticController extends Controller {
  protected $contacts;
  protected $settings;
  // all available statistics
  protected $statistics = [ 'entries', 'entries_filled', 'entries_empty', 'entries_filled_percent', 'entries_empty_percent', 'users', 'users_filled_entries', 'users_empty_entries', 'users_filled_entries_percent', 'users_empty_entries_percent' ];

  public function __construct($appName,
                              IRequest $request,
                              ContactController $contacts,
                              SettingsController $settings) {
    parent::__construct( $appName, $request );
    $this->contacts = $contacts;
    $this->settings = $settings;
  }

  /**
   * get all available statistics
   */
  public function get() {
    $data = [];
    // get them all
    foreach( $this->statistics as $type ) {
        // get the statistic
        $stat = $this->getStatistic( $type );
        // check if something went wrong
        if( $stat === false ) continue;
        // add the data to the bundle
        $data[ $type ] = $stat;
    }

    // return collected statistics
    return new DataResponse( [  'status' => 'success' , 'data' => $data ] );
  }

  /**
   * computes the wanted statistic
   *
   * @param string $type      the type of statistic to be returned
   */
  public function getStatistic( $type ) {
    switch( $type ) {
      case 'entries':
          return $this->entryAmount();
          break;
      case 'entries_filled':
          return $this->entriesFilled();
          break;
      case 'entries_empty':
          return $this->entriesEmpty();
          break;
      case 'entries_filled_percent':
          return $this->entriesFilledPercent();
          break;
      case 'entries_empty_percent':
          return $this->entriesEmptyPercent();
          break;
      case 'users':
          return $this->userAmount();
          break;
      case 'users_filled_entries':
          return $this->usersFilledEntries();
          break;
      case 'users_empty_entries':
          return $this->usersEmtpyEntries();
          break;
      case 'users_filled_entries_percent':
          return $this->usersFilledEntriesPercent();
          break;
      case 'users_empty_entries_percent':
          return $this->usersEmptyEntriesPercent();
          break;
      default:
          // no valid statistic given
          return false;
    }
  }

  /**
   * get all user attributes that aren't filled from the start
   */
  protected function userNonDefaultAttributes() {
    return $this->settings->getSetting('userLdapAttributes', false);
  }

  /**
   * amount of entries users can edit
   */
  protected function entryAmount() {
    // get all attributes the users can edit
    $attributes = $this->userNonDefaultAttributes();

    // count the entries
    return $this->userAmount() * count($attributes);
  }

  /**
   * amount of entries the users have filled out
   */
  protected function entriesFilled() {
    // get all attributes the users can edit
    $attributes = $this->userNonDefaultAttributes();
    // get all users and their data
    $users = $this->contacts->getUsers();
    if ($users['status'] !== 'success') return 0;
    // init counter
    $amount = 0;

    // count the entries
    foreach( $users['data'] as $user ) {
        foreach( $attributes as $attribute) {
            // check if the entry is filled
            if( !empty( $user['ldapAttributes'][ $attribute['name'] ] ) ) {
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
    $amount = $this->entryAmount();
    return $amount > 0 ? round( $this->entriesFilled() / $amount * 100, 2 ) : 0;
  }

  /**
   * amount of entries the users haven't filled out, in percent
   */
  protected function entriesEmptyPercent() {
    $amount = $this->entryAmount();
    return $amount > 0 ? round( $this->entriesEmpty() / $amount * 100, 2 ) : 0;
  }

  /**
   * amount of registered users
   */
  protected function userAmount() {
    $users = $this->contacts->getUsers();
    return $users['status'] === 'success' ? count($users['data']) : 0;
  }

  /**
   * how many users have filled at least one of their entries
   */
  protected function usersFilledEntries() {
    // get all attributes the users can edit
    $attributes = $this->userNonDefaultAttributes();
    // get all users and their data
    $users = $this->contacts->getUsers();
    if ($users['status'] !== 'success') return 0;
    // init counter
    $amount = 0;

    // count the entries
    foreach( $users['data'] as $user ) {
        foreach( $attributes as $attribute ) {
            // check if the entry is filled
            if( !empty( $user['ldapAttributes'][ $attribute['name'] ] ) ) {
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
    $amount = $this->userAmount();
    return $amount > 0 ? round( $this->usersFilledEntries() / $amount * 100, 2 ) : 0;
  }

  /**
   * how many users have filled none of their entries, in percent
   */
  protected function usersEmptyEntriesPercent() {
    $amount = $this->userAmount();
    return $amount > 0 ? round( $this->usersEmtpyEntries() / $amount * 100, 2 ) : 0;
  }
}
