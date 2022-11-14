<?php
/**
 * Plugin Name:     Uoymedia Custom Image Product Art
 * Plugin URI:      https://uoymedia.com/
 * Description:     Upload image for product art - UOYmedia.com.
 * Author:          nguyenhoangvu1803
 * Author URI:      https://github.com/nguyenhoangvu1803/
 * Text Domain:     custom-image-product-art
 * Domain Path:     /languages
 * Version: 		1.0
 */

define ('UOY_ART_PATH', plugin_dir_path( __FILE__ ));
define ('UOY_ART_URL', plugin_dir_url( __FILE__ ));
define ('UOY_ART_PLUGIN_TITLE', 'Uoy Art');
define ('UOY_ART_PLUGIN_SLUG', 'uoy-art');

add_action( 'activated_plugin', 'uoy_art_actived_redirect' );

include 'admin/settings.php';
include 'frontend/upload.php';

function uoy_art_actived_redirect( $plugin ) {
    if( $plugin == plugin_basename( __FILE__ ) ) {
        exit( wp_redirect( admin_url( 'admin.php?page=' . UOY_ART_PLUGIN_SLUG ) ) );
    }
}