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

	public static function init() {
		add_action('admin_menu', 'Patched_Up_Bots::register_patched_up_bots_menu');
	}

	public static function register_patched_up_bots_menu() {
		add_management_page(
			'Patched Up Bots',
			'Generate Data',
			'manage_options',
			'patched-up-bots',
			'Patched_Up_Bots_Admin_Page::render'
		);
	}

}

Patched_Up_Bots::init();
