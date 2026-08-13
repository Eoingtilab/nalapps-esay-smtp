<?php
namespace NES;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	private static $instance = null;
	private $smtp;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once NES_PATH . 'includes/class-smtp.php';
		$this->smtp = new Smtp();
	}
}
