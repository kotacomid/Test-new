<?php
/**
 * Admin UI for Dynamic Landing Page Generator
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DLPG_Admin {

    /**
     * Constructor – hooks.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_post_dlpg_generate', [ $this, 'handle_form' ] );
    }

    /**
     * Register plugin top-level menu.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Landing Page Generator', 'dlpg' ),
            __( 'Landing Pages', 'dlpg' ),
            'manage_options',
            'dlpg',
            [ $this, 'render_page' ],
            'dashicons-welcome-widgets-menus',
            56
        );
    }

    /**
     * Render admin form page.
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Create New Landing Page', 'dlpg' ); ?></h1>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" enctype="multipart/form-data">
                <?php wp_nonce_field( 'dlpg_generate' ); ?>
                <input type="hidden" name="action" value="dlpg_generate" />

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="dlpg_page_title"><?php esc_html_e( 'Page Title', 'dlpg' ); ?></label></th>
                            <td><input name="page_title" type="text" id="dlpg_page_title" value="" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dlpg_headline"><?php esc_html_e( 'Headline', 'dlpg' ); ?></label></th>
                            <td><input name="headline" type="text" id="dlpg_headline" value="" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dlpg_description"><?php esc_html_e( 'Description', 'dlpg' ); ?></label></th>
                            <td><textarea name="description" id="dlpg_description" rows="5" class="large-text" required></textarea></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dlpg_cta_text"><?php esc_html_e( 'CTA Text', 'dlpg' ); ?></label></th>
                            <td><input name="cta_text" type="text" id="dlpg_cta_text" value="<?php esc_attr_e( 'Start Now', 'dlpg' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dlpg_cta_link"><?php esc_html_e( 'CTA Link', 'dlpg' ); ?></label></th>
                            <td><input name="cta_link" type="url" id="dlpg_cta_link" value="<?php echo esc_attr( home_url() ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dlpg_hero_image"><?php esc_html_e( 'Hero Image', 'dlpg' ); ?></label></th>
                            <td><input name="hero_image" type="file" id="dlpg_hero_image" accept="image/*" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dlpg_builder"><?php esc_html_e( 'Page Builder', 'dlpg' ); ?></label></th>
                            <td>
                                <select name="builder" id="dlpg_builder">
                                    <option value="elementor">Elementor</option>
                                    <option value="greenshift">Greenshift</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button( __( 'Generate Landing Page', 'dlpg' ) ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle form submission to generate a landing page.
     */
    public function handle_form() {
        // Basic capability & nonce checks.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'dlpg' ) );
        }
        check_admin_referer( 'dlpg_generate' );

        // Sanitize & prepare data.
        $data = [
            'title'       => isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '',
            'headline'    => isset( $_POST['headline'] ) ? sanitize_text_field( wp_unslash( $_POST['headline'] ) ) : '',
            'description' => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
            'cta_text'    => isset( $_POST['cta_text'] ) ? sanitize_text_field( wp_unslash( $_POST['cta_text'] ) ) : '',
            'cta_link'    => isset( $_POST['cta_link'] ) ? esc_url_raw( wp_unslash( $_POST['cta_link'] ) ) : home_url(),
            'builder'     => ( isset( $_POST['builder'] ) && 'greenshift' === $_POST['builder'] ) ? 'greenshift' : 'elementor',
        ];

        // Handle hero image upload (optional).
        if ( ! empty( $_FILES['hero_image']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            $attachment_id = media_handle_upload( 'hero_image', 0 );
            if ( ! is_wp_error( $attachment_id ) ) {
                $data['hero_image_id'] = $attachment_id;
            }
        }

        // Generate page.
        $page_id = DLPG_Generator::generate_page( $data );

        // Redirect to edit screen.
        wp_safe_redirect( admin_url( 'post.php?post=' . $page_id . '&action=edit' ) );
        exit;
    }
}