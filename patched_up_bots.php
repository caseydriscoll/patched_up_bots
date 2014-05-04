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
			case 'users' :
				$users = isset( $_POST['users'] ) ? $_POST['users'] : null;
				foreach ( $users as $user ) wp_insert_user( $user );
				break;
			case 'posts' :
				$posts = isset( $_POST['posts'] ) ? $_POST['posts'] : null;
				foreach ( $posts as $post ) {
					// Add user if given user doesn't exist
					//		post_author is either an int (exists) or 0 (was string)
					//		no users have '0' as ID so it is a safe test 
					if( intval( $post['post_author'] == 0 ) ) // create user
						$post['post_author'] = wp_insert_user( $post['user'] );

					wp_insert_post( $post );
				}
				break;
			default:
				break;
		}

	}

}

Patched_Up_Bots::init();
