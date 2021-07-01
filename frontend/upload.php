<?php
if(!class_exists('UOY_ART_UPLOAD')) {
    class UOY_ART_UPLOAD {
        public static $instance;

        private function __construct() {
            add_action('woocommerce_before_single_variation', array($this, 'upload_form'));
            add_filter('woocommerce_add_cart_item_data', array($this, 'upload_add_to_cart'), 10, 3);
            add_filter('woocommerce_get_item_data', array($this, 'display_in_cart'), 10, 2);
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_to_order_items'), 10, 4 );
        }

        public static function instance() {
            if(!UOY_ART_UPLOAD::$instance) {
                UOY_ART_UPLOAD::$instance = new UOY_ART_UPLOAD();
            }

            return UOY_ART_UPLOAD::$instance;
        }

        public function upload_form() {
            $product_id = get_the_ID();
            $upload = get_post_meta($product_id, 'uoy_art_enable', true);

            if($upload == 'yes') {
                ?>
                <div>Upload your photo</div>
                <div>We’ll focus on the head to chest region. <a href="#" title="Go to photo tips?">Want photo tips?</a></div>
                <label for="myfile">Select a file:</label>
                <input type="file" class="uoy-art-file-input" name="uoy-art-file-input" onChange="readURL(this);" value=""/>
                <input type="hidden" name="uoy-art-file" value=""/>
                <img id="uoy-art-img" width="80px" height="80px"/>
                <script>
                    function readURL(input) {
                        if (input.files && input.files[0]) {
                            var file = input.files[0];

                            // Check MB
                            var filesize = Math.round(file.size / 1024);
                            if(filesize >= 4096) {
                                alert("File size < 4MB.");
                                return;
                            }

                            // Check img ext: .jpg, .jpeg, .png
                            // var filterType = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;
                            var filterType = /^(?:image\/jpeg|image\/jpg|image\/png)$/i;
                            if (!filterType.test(file.type)) {
                                alert("Please select a valid image. Support: .jpeg, .jpg, .png");
                                return;
                            }

                            var reader = new FileReader();

                            reader.onload = function (file) {
                                var img_tag = $('#uoy-art-img');
                                var img = new Image;
                                img.src = file.target.result;

                                img.onload = function() {
                                    // Check img width, height
                                    var width = this.width;
                                    var height = this.height;
                                    if(width >= 500 && height >= 500) {
                                        img_tag.attr('src', img.src).width(80).height(80);
                                        img_tag.css('display', 'block');
                                        $('input[name="uoy-art-file"]').val(img.src);
                                    } else {
                                        alert("Image width & height > 500 px");
                                        return;
                                    }
                                }
                            };

                            reader.readAsDataURL(file);
                        }
                    }
                </script>
                <style>
                    #uoy-art-img {
                        display: none;
                    }
                    .uoy-art-file-input {
                        color: transparent;
                        width: 100%;
                    }
                    .uoy-art-file-input::-webkit-file-upload-button {
                        visibility: hidden;
                    }
                    .uoy-art-file-input::before {
                        content: 'Choose Image';
                        -webkit-box-sizing: border-box;
                        -moz-box-sizing: border-box;
                        box-sizing: border-box;
                        color: black;
                        display: inline-block;
                        background: rgb(241, 242, 244);
                        border-radius: 3px;
                        padding: 12px;
                        outline: none;
                        white-space: nowrap;
                        -webkit-user-select: none;
                        cursor: pointer;
                        text-shadow: 1px 1px #fff;
                        font-weight: 700;
                        font-size: 10pt;
                        width: 100%;
                        text-align: center;
                    }
                    .uoy-art-file-input:hover::before {
                        border-color: black;
                    }
                    .uoy-art-file-input:active {
                        outline: 0;
                    }
                    .uoy-art-file-input:active::before {
                        background: -webkit-linear-gradient(top, #e3e3e3, #f9f9f9);
                    }
                </style>
                <?php
            }
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