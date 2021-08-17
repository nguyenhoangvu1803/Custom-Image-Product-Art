<?php
if (!class_exists('UOY_ART_SETTINGS')) {
    class UOY_ART_SETTINGS
    {
        public static $instance;

        private function __construct()
        {
            add_action('admin_menu', array($this, 'register_settings_page'));
            add_filter('woocommerce_product_data_tabs', array($this, 'custom_product_tabs'));
            add_action('woocommerce_product_data_panels', array($this, 'options_product_tab_content'));
            add_action('woocommerce_process_product_meta_simple', array($this, 'save_option_fields'), 11, 1);
            add_action('woocommerce_process_product_meta_variable', array($this, 'save_option_fields'), 11, 1);
        }

        public static function instance()
        {
            if (!UOY_ART_SETTINGS::$instance) {
                UOY_ART_SETTINGS::$instance = new UOY_ART_SETTINGS();
            }

            return UOY_ART_SETTINGS::$instance;
        }

        function register_settings_page()
        {
            add_submenu_page('woocommerce',
                UOY_ART_PLUGIN_TITLE,
                UOY_ART_PLUGIN_TITLE,
                'manage_woocommerce',
                UOY_ART_PLUGIN_SLUG,
                array($this, 'settings_page')
            );
        }

        function settings_page()
        {
            if (!current_user_can('administrator')) {
                echo "you can not access this page!";
                return;
            }
            $config_tab_link = admin_url('admin.php?page=' . UOY_ART_PLUGIN_SLUG);
            ?>

            <div class="wrap" style="margin-top: 32px">
                <h1><?php echo UOY_ART_PLUGIN_TITLE; ?></h1>
                <h4>UOY Product Art - Settings</h4>
                <div>Enable Product Art for each product (on UOY Art tab), enable Product Art -> auto disable Customily
                    Product
                </div>
                <div>Upload image size <= 10MB & width, height >= 500px</div>
            </div>

            <?php

        }

        function custom_product_tabs($tabs)
        {
            $tabs['uoy_art'] = array(
                'label' => __(UOY_ART_PLUGIN_TITLE, 'woocommerce'),
                'target' => 'uoy_art_options',
                'class' => array(),//'show_if_simple', 'show_if_variable'
            );
            return $tabs;
        }

        function options_product_tab_content()
        {
            global $post;

            echo '<div id="uoy_art_options" class="panel woocommerce_options_panel">';
                echo '<p class="form-field">Enable upload image for Product Art -> auto disable <b>Customily</b></p>';
                woocommerce_wp_checkbox(array(
                    'id' => 'uoy_art_enable',
                    'name' => 'uoy_art_enable',
                    'label' => __('Enable ' . UOY_ART_PLUGIN_TITLE, 'woocommerce'),
                    'desc_tip' => false,
                    'description' => __('Enable upload image for product art', 'woocommerce')
                ));

                // Get WordPress' media upload URL
                $upload_link = esc_url( get_upload_iframe_src( 'image', $post->ID ) );
                // See if there's a media id already saved as post meta
                $your_img_id = get_post_meta( $post->ID, 'uoy_art_image_tutorial', true );
                // Get the image src
                $your_img_src = wp_get_attachment_image_src( $your_img_id, 'full' );
                // For convenience, see if the array is valid
                $you_have_img = is_array( $your_img_src );
                ?>

                <!-- Your add & remove image links -->
                <p class="hide-if-no-js form-field">
                    <label>Image tutorial</label>
                    <a class="upload-custom-img <?php if ( $you_have_img  ) { echo 'hidden'; } ?>"
                       href="<?php echo $upload_link ?>">
                        <?php _e('Select image') ?>
                    </a>
                    <a class="delete-custom-img <?php if ( ! $you_have_img  ) { echo 'hidden'; } ?>"
                       href="#">
                        <?php _e('Remove this image') ?>
                    </a>
                </p>

                <!-- Your image container, which can be manipulated with js -->
                <p class="custom-img-container form-field">
                    <?php if ( $you_have_img ) : ?>
                        <img src="<?php echo $your_img_src[0] ?>" alt="" style="max-width:25%;" />
                    <?php endif; ?>
                </p>

                <!-- A hidden input to set and post the chosen image id -->
                <input class="custom-img-id" name="custom-img-id" type="hidden" value="<?php echo esc_attr( $your_img_id ); ?>" />

                <script>
                    jQuery(function($){

                        // Set all variables to be used in scope
                        var frame,
                            metaBox = $('#uoy_art_options'), // Your meta box id here
                            addImgLink = metaBox.find('.upload-custom-img'),
                            delImgLink = metaBox.find( '.delete-custom-img'),
                            imgContainer = metaBox.find( '.custom-img-container'),
                            imgIdInput = metaBox.find( '.custom-img-id' );

                        // ADD IMAGE LINK
                        addImgLink.on( 'click', function( event ){
                            event.preventDefault();
                            // If the media frame already exists, reopen it.
                            if ( frame ) {
                                frame.open();
                                return;
                            }
                            // Create a new media frame
                            frame = wp.media({
                                title: 'Select or Upload Media Of Your Chosen Persuasion',
                                button: {
                                    text: 'Use this media'
                                },
                                multiple: false  // Set to true to allow multiple files to be selected
                            });
                            // When an image is selected in the media frame...
                            frame.on( 'select', function() {
                                // Get media attachment details from the frame state
                                var attachment = frame.state().get('selection').first().toJSON();
                                // Send the attachment URL to our custom image input field.
                                imgContainer.append( '<img src="'+attachment.url+'" alt="" style="max-width:25%;"/>' );
                                // Send the attachment id to our hidden input
                                imgIdInput.val( attachment.id );
                                // Hide the add image link
                                addImgLink.addClass( 'hidden' );
                                // Unhide the remove image link
                                delImgLink.removeClass( 'hidden' );
                            });
                            // Finally, open the modal on click
                            frame.open();
                        });

                        // DELETE IMAGE LINK
                        delImgLink.on( 'click', function( event ){
                            event.preventDefault();
                            // Clear out the preview image
                            imgContainer.html( '' );
                            // Un-hide the add image link
                            addImgLink.removeClass( 'hidden' );
                            // Hide the delete image link
                            delImgLink.addClass( 'hidden' );
                            // Delete the image id from the hidden input
                            imgIdInput.val( '' );
                        });

                    });
                </script>
                <?php
            echo '</div>';
        }

        function save_option_fields($product_id)
        {
            // Uoy Art Enable value 'yes'
            // Disable value 0
            if (isset($_POST['uoy_art_enable'])) {
                $this->enable_uoy_art($product_id, $_POST['uoy_art_enable']);
                update_post_meta($product_id, 'uoy_art_enable', $_POST['uoy_art_enable']);
                update_post_meta($product_id, 'uoy_art_image_tutorial', $_POST['custom-img-id']);
                if ($_POST['uoy_art_enable'] == 'yes') {
                    $this->disable_customily($product_id);
                }
            } else {
                $this->enable_uoy_art($product_id, 0);
            }
        }

        function product_is_on_customily($product_id)
        {
            $customily_state = json_decode(get_post_meta($product_id, '_cl_admin_state', true), true);
            return $customily_state['is_on'];
        }

        function enable_uoy_art($product_id, $value)
        {
            update_post_meta($product_id, 'uoy_art_enable', $value);
        }

        function disable_customily($product_id)
        {
            $customily_state = json_decode(get_post_meta($product_id, '_cl_admin_state', true));
            if ($customily_state) {
                $customily_state->is_on = false;
                update_post_meta($product_id, '_cl_admin_state', $customily_state);
            }
        }

    }

    UOY_ART_SETTINGS::instance();
}