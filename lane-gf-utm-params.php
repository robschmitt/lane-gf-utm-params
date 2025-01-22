<?php
/*
Plugin Name: Gravity Forms UTM Parameters Add-On
Description: Remember UTM parameters for the current session and optionally add them to Gravity Forms submissions.
Version: 1.0
Author: The Lane Agency Ltd
Author URI: https://thelaneagency.com
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the main plugin class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-lane-gf-utm-params.php';

// Initialize the plugin
add_action( 'plugins_loaded', function() {
	if ( class_exists( 'GFForms' ) ) {

        $current_version = GFForms::$version;
        $required_version = '2.4.7'; // See: https://docs.gravityforms.com/how-to-add-field-to-form-using-gfapi/

        // Compare the current version with the required version
        if (version_compare($current_version, $required_version, '<')) {
            add_action('admin_notices', function () use ($current_version, $required_version) {
                echo '<div class="notice notice-error">
                        <p><strong>Gravity Forms Version Issue:</strong> Your Gravity Forms version (' . esc_html($current_version) . ') is outdated. Please update to at least version ' .  esc_html($required_version) . ' to ensure compatibility.</p>
                      </div>';
            });
        }
        else {
            Lane_GF_UTM_Params::get_instance();
        }

	}
});