<?php
if(!class_exists('UOY_ART_UPLOAD')) {
    class UOY_ART_UPLOAD {
        public static $instance;

        private function __construct() {
            add_action('woocommerce_before_single_variation', array($this, 'upload_form'));
            add_filter('woocommerce_add_cart_item_data', array($this, 'upload_add_to_cart'), 10, 3);
            add_filter('woocommerce_get_item_data', array($this, 'display_in_cart'), 10, 2);
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_to_order_items'), 10, 4 );
            add_action('wp_footer', array($this, 'add_scripts'), 999999);
            add_filter('body_class', [$this, 'add_body_class']);
            add_filter('woocommerce_add_to_cart_redirect', [$this, 'redirect_to_cart'], 99999999999, 2);
        }

        public static function instance() {
            if(!UOY_ART_UPLOAD::$instance) {
                UOY_ART_UPLOAD::$instance = new UOY_ART_UPLOAD();
            }

            return UOY_ART_UPLOAD::$instance;
        }

        public function upload_form() {
            if(!$this->isProductUoyArt(get_the_ID())) {
                return false;
            }


            $product_id = get_the_ID();
            // See if there's a media id already saved as post meta
            $your_img_id = get_post_meta( $product_id, 'uoy_art_image_tutorial', true );
            // Get the image src
            $your_img_src = wp_get_attachment_image_src( $your_img_id, 'full' );
            ?>
            <div class="uoy-art-container">
                <div>Upload your photo</div>
                <div style="margin-bottom: 10px;">We’ll focus on the head to chest region. <a href="javascript:void(0)" class="uoy-art-tips" title="Go to photo tips?">Want photo tips?</a></div>
                <div class="flex-container">
                    <div class="img-show">
                        <img id="uoy-art-img" width="80px" height="80px"/>
                        <a href="javascript:void(0)" id="uoy-art-close-img">x</a>
                    </div>
                    <input type="file" class="uoy-art-file-input" name="uoy-art-file-input"/>
                </div>
                <input type="hidden" name="uoy-art-file" value=""/>
                <div class="uoy-art-message"></div>
            </div>
            <div class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <p>
                        <?php
                            if(is_array( $your_img_src )) {
                        ?>
                            <img src="<?php echo esc_url($your_img_src[0]);?>"/>
                        <?php
                            } else {
                        ?>
                            <img src="<?php echo esc_url(UOY_ART_URL . 'public/img/personalized-photo-tips.jpg');?>"/>
                        <?php
                            }
                        ?>
                    </p>
                </div>
            </div>
            <?php
        }

        public function add_scripts() {
            if(!$this->isProductUoyArt(get_the_ID())) {
                return false;
            }

            ?>
            <script type='text/javascript' src="<?php echo esc_url(UOY_ART_URL . 'public/js/product-art.js');?>"></script>
            <link rel='stylesheet'  href='<?php echo esc_url(UOY_ART_URL . 'public/css/product-art.css');?>' type='text/css' media='all'/>
            <?php
        }

        public function upload_add_to_cart($cart_item_data, $product_id, $variation_id) {
            // ADD ITEM PRODUCT META
            if(isset($_POST['uoy-art-file']) && $_POST['uoy-art-file'] != '') {

                // Upload Img to wp-content
                $attach_id = $this->save_image(sanitize_text_field($_POST['uoy-art-file']), 'Image of Product Art');
                $cart_item_data['uoy-art-file'] = $attach_id;
            }

            return $cart_item_data;
        }

        public function display_in_cart($item_data, $cart_item) {
            if (empty($cart_item['uoy-art-file'])) {
                return $item_data;
            }

            $thumb = wp_get_attachment_image_src($cart_item['uoy-art-file'], array(100,100));

            $item_data[] = array(
                'key'     => __('Product Art', 'custom-image-product-art'),
                'value'   => wc_clean($cart_item['uoy-art-file']),
                'display' => '<div style="text-transform: initial; max-width: 80px;"><img src="' . esc_url($thumb[0]) . '" alt="Customized image"/></div>',
            );

            return $item_data;
        }

        public function add_to_order_items($item, $cart_item_key, $values, $order) {
            if (empty($values['uoy-art-file'])) {
                return;
            }

            $item->add_meta_data(__('Image Art ID', 'custom-image-product-art'), $values['uoy-art-file']);
            $item->add_meta_data(__('Image Art Link', 'custom-image-product-art'), wp_get_attachment_url($values['uoy-art-file']));
        }

        public function add_body_class($classes) {
            if(!$this->isProductUoyArt(get_the_ID())) {
                return $classes;
            }

            $classes[] = 'not-sticky';
            $classes[] = 'uoymedia-custom-art';

            return $classes;
        }

        public function redirect_to_cart($url, $adding_to_cart) {
            if(!$adding_to_cart) {
                return $url;
            }

            if(!$this->isProductUoyArt($adding_to_cart->get_id())) {
                return $url;
            }

            return wc_get_cart_url();
        }

        private function isProductUoyArt(int $id): bool
        {
            $enable = get_post_meta($id, 'uoy_art_enable', true);
            if($enable !== 'yes') {
                return false;
            }

            return true;
        }

        /**
         * Save the image on the server.
         */
        private function save_image($base64_img, $content) {

            // Upload dir.
            $upload_dir  = wp_upload_dir();
            $upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

//            $img             = str_replace( 'data:image/jpeg;base64,', '', $base64_img );
            $img             = explode(',', $base64_img);
            $ext             = isset($img[0]) ? $this->get_extension($img[0]) : 'jpg';
            $img             = end($img) !== null ? end($img) : '';
            $img             = str_replace( ' ', '+', $img );
            $decoded         = base64_decode($img);
            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
            $title = substr(str_shuffle($permitted_chars), 0, 10);
            $filename        = $title . '.' . $ext;
            $file_type       = 'image/' . $ext;
            $hashed_filename = md5( $filename . microtime() ) . '.' . $ext;

            // Save the image in the uploads directory.
            $upload_file = file_put_contents($upload_path . $hashed_filename, $decoded );

            $attachment = array(
                'post_mime_type' => $file_type,
                'post_title'     => $hashed_filename,
                'post_content'   => $content,
                'post_status'    => 'inherit',
                'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename )
            );

            $attach_id = wp_insert_attachment($attachment, $upload_dir['path'] . '/' . $hashed_filename);
            if (!function_exists('wp_crop_image')) {
                include(ABSPATH . 'wp-admin/includes/image.php');
            }
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload_dir['path'] . '/' . $hashed_filename);
            wp_update_attachment_metadata($attach_id,  $attach_data);

            return $attach_id;
        }

        private function get_extension($str) {
            if(preg_match('(png)', $str)) {
                return 'png';
            } else if (preg_match('(jpeg)', $str)) {
                return 'jpeg';
            } else if (preg_match('(jpg)', $str)) {
                return 'jpg';
            } else {
                return 'jpg';
            }
        }

    }

    UOY_ART_UPLOAD::instance();
}