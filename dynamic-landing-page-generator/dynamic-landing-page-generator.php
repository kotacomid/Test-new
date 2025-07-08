<?php
/*
Plugin Name: Dynamic Landing Page Generator
Plugin URI: https://example.com
Description: Auto-generate landing pages based on Elementor or Greenshift templates from a simple admin form.
Version: 1.0.0
Author: Your Name
License: GPL2
Text Domain: dlpg
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// -----------------------------------------------------------------------------
// Define plugin paths
// -----------------------------------------------------------------------------

define( 'DLPG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DLPG_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// -----------------------------------------------------------------------------
// Includes
// -----------------------------------------------------------------------------

require_once DLPG_PLUGIN_PATH . 'includes/class-dlpg-admin.php';
require_once DLPG_PLUGIN_PATH . 'includes/class-dlpg-generator.php';

// -----------------------------------------------------------------------------
// Bootstrap
// -----------------------------------------------------------------------------

add_action( 'plugins_loaded', function () {
    // Instantiate admin UI and generator functionality.
    new DLPG_Admin();
    new DLPG_Generator();
} );