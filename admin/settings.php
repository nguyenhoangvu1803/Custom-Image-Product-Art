<?php
if(!class_exists('UOY_ART_SETTINGS')) {
    class UOY_ART_SETTINGS {
        public static $instance;

        private function __construct()
        {
            add_action('admin_menu', array($this, 'register_settings_page'));
            add_filter('woocommerce_product_data_tabs', array($this, 'custom_product_tabs'));
		    add_action('woocommerce_product_data_panels', array($this, 'options_product_tab_content'));
            add_action('woocommerce_process_product_meta_simple', array($this, 'save_option_fields'), 11, 1);
            add_action('woocommerce_process_product_meta_variable', array($this, 'save_option_fields'), 11, 1);
        }

        public static function instance() {
            if(!UOY_ART_SETTINGS::$instance) {
                UOY_ART_SETTINGS::$instance = new UOY_ART_SETTINGS();
            }

            return UOY_ART_SETTINGS::$instance;
        }

        function register_settings_page() {
            add_submenu_page('woocommerce',
                UOY_ART_PLUGIN_TITLE,
                UOY_ART_PLUGIN_TITLE,
                'manage_woocommerce',
                UOY_ART_PLUGIN_SLUG,
                array($this, 'settings_page')
            );
        }

        function settings_page() {
            if(!current_user_can('administrator')){
                echo "you can not access this page!";
                return;
            }
            $config_tab_link = admin_url('admin.php?page='.UOY_ART_PLUGIN_SLUG);
            ?>

            <div class="wrap" style="margin-top: 32px">
                <h1><?php echo UOY_ART_PLUGIN_TITLE;?></h1>
                <h4>UOY Product Art - Settings</h4>
                <div>Enable Product Art for each product (on UOY Art tab), enable Product Art -> auto disable Customily Product</div>
                <div>Upload image size >= 4MB & width, height >= 500px</div>
            </div>

            <?php

        }

        function custom_product_tabs($tabs) {
            $tabs['uoy_art'] = array(
                'label'		=> __(UOY_ART_PLUGIN_TITLE, 'woocommerce'),
                'target'	=> 'uoy_art_options',
                'class'		=> array(   ),//'show_if_simple', 'show_if_variable'
            );
            return $tabs;
        }

        function options_product_tab_content() {
            echo '<div id="uoy_art_options" class="panel woocommerce_options_panel">';
                echo '<div>Enable upload image for Product Art -> auto disable <b>Customily</b></div>';
                woocommerce_wp_checkbox( array(
                    'id'				=> 'uoy_art_enable',
                    'name'				=> 'uoy_art_enable',
                    'label'				=> __('Enable ' . UOY_ART_PLUGIN_TITLE, 'woocommerce' ),
                    'desc_tip'			=> false,
                    'description'		=> __( 'Enable upload image for product art', 'woocommerce' )
                ) );
            echo '</div>';
        }

        function save_option_fields($product_id) {
            // Enable value 'yes'
            // Disable value 0
            if(isset($_POST['uoy_art_enable'])) {
                $this->enable_uoy_art($product_id, $_POST['uoy_art_enable']);
                update_post_meta($product_id, 'uoy_art_enable', $_POST['uoy_art_enable']);
                if($_POST['uoy_art_enable'] == 'yes') {
                    $this->disable_customily($product_id);
                }
            } else {
                $this->enable_uoy_art($product_id, 0);
            }
        }

        function product_is_on_customily($product_id) {
            $customily_state = json_decode(get_post_meta($product_id, '_cl_admin_state', true), true);
            return $customily_state['is_on'];
        }

        function enable_uoy_art($product_id, $value) {
            update_post_meta($product_id, 'uoy_art_enable', $value);
        }

        function disable_customily($product_id) {
            $customily_state = json_decode(get_post_meta($product_id, '_cl_admin_state', true));
            if($customily_state) {
                $customily_state->is_on = false;
                update_post_meta($product_id, '_cl_admin_state', $customily_state);
            }
        }

    }

    UOY_ART_SETTINGS::instance();
}