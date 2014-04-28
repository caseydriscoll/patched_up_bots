<?php
/* Plugin Name: Patched Up Bots
 * Plugin URI:
 * Description: A data generator and automator
 * Version: 0.0.1
 * Author: Casey Patrick Driscoll
 * Author URI: http:caseypatrickdriscoll.com
 * License: GPL2
 */

require_once( plugin_dir_path( __FILE__ ) . 'class-patched-up-bots-admin-page.php' );

class Patched_Up_Bots {
	const PAGE_TITLE = 'Patched Up Bots'; 
	const PAGE_SLUG  = 'patched-up-bots'; 

	public static function init() {
		add_action( 'admin_menu', 'Patched_Up_Bots::register_patched_up_bots_menu' );
		add_action( 'admin_init', 'Patched_Up_Bots::register_patched_up_bots_options' );
		add_action( 'admin_init', 'Patched_Up_Bots::generate_data' );
	}

	public static function register_patched_up_bots_menu() {
		add_management_page(
			self::PAGE_TITLE,	
			'Generate Data',
			'manage_options',
			self::PAGE_SLUG,	
			'Patched_Up_Bots_Admin_Page::render'
		);
	}

	public static function register_patched_up_bots_options() {
		add_settings_section( 'patched_up_bots_users', 'Users', 'Patched_Up_Bots_Admin_Page::render', self::PAGE_SLUG );
	}

	public static function generate_data() {
		if ( $_GET['page'] != self::PAGE_SLUG || !isset( $_POST['generate'] ) ) return;

		$generate = $_POST['generate'];

		switch ( $generate ) {
			case 'users':
				$amount = isset( $_POST['amount'] ) ? $_POST['amount'] : 0;
				for ( $i = 0; $i < $amount; $i++ ) {
					$user_name = substr(md5(rand()), 0, 7);
					$user = array(
						'user_login' => $user_name
					);
					wp_insert_user( $user );
				}

				break;

			default:
				break;
		}

	}

}

Patched_Up_Bots::init();
