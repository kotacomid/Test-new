<?php
/**
 * Plugin Name:       Green AI Landing MVP
 * Description:       Generate draft landing pages from GreenShift section patterns filled with AI content.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Author:            MVP
 * License:           GPL v2 or later
 *
 * @package Green_AI_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Load modules.
require_once GAI_PLUGIN_DIR . 'includes/Patterns.php';
require_once GAI_PLUGIN_DIR . 'includes/OpenAI.php';
require_once GAI_PLUGIN_DIR . 'includes/Generator.php';
require_once GAI_PLUGIN_DIR . 'includes/Rest.php';

add_action( 'admin_enqueue_scripts', function( $hook ){
    if( $hook==='toplevel_page_gai-landing'){
        wp_enqueue_style('gai-admin', plugin_dir_url(__FILE__).'admin.css');
        wp_enqueue_script('gai-admin', plugin_dir_url(__FILE__).'admin.js', ['wp-api-fetch','wp-element'], null, true);
        wp_localize_script('gai-admin','GAI',[ 'nonce'=> wp_create_nonce('wp_rest') ]);
    }
} );

// Register admin menu.
add_action( 'admin_menu', function () {
    add_menu_page( 'AI Landing', 'AI Landing', 'edit_posts', 'gai-landing', 'gai_render_admin', 'dashicons-text', 30 );
} );

function gai_render_admin() {
    ?>
    <div class="wrap">
        <h1>Generate Landing Page</h1>
        <form id="gai-form" method="post" class="gai-admin-form">
            <?php wp_nonce_field( 'gai_generate' ); ?>
            <p><label for="gai_desc">Deskripsi bisnis / tujuan halaman</label><br>
                <textarea name="gai_desc" id="gai_desc" class="widefat" rows="4" required></textarea></p>
            <p><label>Section yang akan digunakan:</label><br>
                <?php foreach ( GAIPatterns::sections_list() as $slug => $section ) : ?>
                    <label><input type="checkbox" name="gai_sections[]" value="<?php echo esc_attr( $slug ); ?>" checked> <?php echo esc_html( $section['title'] ); ?></label><br>
                <?php endforeach; ?>
            </p>
            <p>
                <button id="gai-submit" type="submit" class="button button-primary">Generate Page</button>
                <span id="gai-spinner" class="spinner" style="float:none;vertical-align:middle;"></span>
            </p>
            <div id="gai-notice"></div>
        </form>
    </div>
    <?php
    if ( isset( $_POST['gai_desc'] ) && check_admin_referer( 'gai_generate' ) ) {
        $desc     = sanitize_textarea_field( wp_unslash( $_POST['gai_desc'] ) );
        $sections = isset( $_POST['gai_sections'] ) ? array_map( 'sanitize_key', (array) $_POST['gai_sections'] ) : array();

        $post_id = GAI_Generator::generate_page( $desc, $sections );
        if ( is_wp_error( $post_id ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $post_id->get_error_message() ) . '</p></div>';
        } else {
            $url = get_edit_post_link( $post_id, '');
            echo '<div class="notice notice-success"><p>Berhasil! <a href="' . esc_url( $url ) . '">Buka halaman baru »</a></p></div>';
        }
    }
}