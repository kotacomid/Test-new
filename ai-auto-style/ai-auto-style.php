<?php
/**
 * Plugin Name:       AI Auto Style
 * Plugin URI:        https://example.com/plugins/ai-auto-style
 * Description:       Generate and apply AI-driven styles and layouts for pages using Gutenberg and GreenShift blocks.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AI_Auto_Style
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants.
if ( ! defined( 'AIAS_PLUGIN_FILE' ) ) {
    define( 'AIAS_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'AIAS_PLUGIN_DIR' ) ) {
    define( 'AIAS_PLUGIN_DIR', plugin_dir_path( AIAS_PLUGIN_FILE ) );
}
if ( ! defined( 'AIAS_PLUGIN_URL' ) ) {
    define( 'AIAS_PLUGIN_URL', plugin_dir_url( AIAS_PLUGIN_FILE ) );
}

/**
 * Enqueue Gutenberg editor assets for our plugin.
 */
function aias_enqueue_editor_assets() {
    $script_path = AIAS_PLUGIN_DIR . 'build/index.js';
    $script_url  = AIAS_PLUGIN_URL . 'build/index.js';

    wp_enqueue_script(
        'aias-editor-script',
        $script_url,
        array(
            'wp-plugins',
            'wp-edit-post',
            'wp-element',
            'wp-components',
            'wp-api-fetch',
            'wp-data',
            'wp-blocks',
        ),
        file_exists( $script_path ) ? filemtime( $script_path ) : AIAS_PLUGIN_FILE,
        true
    );
}
add_action( 'enqueue_block_editor_assets', 'aias_enqueue_editor_assets' );

// Load plugin files.
require_once AIAS_PLUGIN_DIR . 'includes/OpenAI.php';
require_once AIAS_PLUGIN_DIR . 'includes/Rest.php';
require_once AIAS_PLUGIN_DIR . 'includes/Templates.php';