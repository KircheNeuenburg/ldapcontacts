<?php
namespace OCA\LdapContacts\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {
	/** @var IL10N */
	protected $l10n;
	/** @var IURLGenerator */
	protected $urlGenerator;
	/** @var string */
	protected $appName;

	/**
	 * @param string $appName
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct($appName, IL10N $l10n, IURLGenerator $urlGenerator) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->appName = $appName;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getID() {
		return 'contacts';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return $this->l10n->t( 'Contacts' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 10;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIcon() {
		return $this->urlGenerator->imagePath( $this->appName, 'app_dark.svg' );
	}
}
