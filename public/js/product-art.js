jQuery(document).ready(function($){
    $('input[name="uoy-art-file-input"]').on('change', function(e){
        var input = this;
        if (input.files && input.files[0]) {
            var file = input.files[0];

            // Check MB
            var filesize = Math.round(file.size / 1024);
            if(filesize >= 10240) {
                alert("File size <= 10MB.");
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
                        img_tag.attr('src', img.src);
                        $('.img-show').css('display', 'block');
                        $('input[name="uoy-art-file"]').val(img.src);
                        $(input).addClass('uploaded');
                        $(input).removeClass('null');
                        $('.single_add_to_cart_button').removeClass('loading remove-loading');
                        $('.uoy-art-message').empty();
                    } else {
                        alert("Image width & height >= 500 px");
                        return;
                    }
                }
            };

            reader.readAsDataURL(file);
        }
    });

    $('form.variations_form.cart').on('submit', function(e) {
        let form = $(this);
        let file = $('input[name="uoy-art-file-input"]');
        if(!file.val()) {
            $('.uoy-art-message').empty().append('Please upload your image before adding to cart!');
            $('.single_add_to_cart_button').addClass('remove-loading');
            file.addClass('null');
            form.unblock();

            e.preventDefault();
        }

        return;
    });

    $('#uoy-art-close-img').on('click', function(){
        $('.img-show').css('display', 'none');
        $('input[name="uoy-art-file"]').val('');
        $('input[name="uoy-art-file-input"]').val('');
        $('.uoy-art-file-input').removeClass('uploaded');
    });

    var modal = $('.modal');
    var btn = $('.uoy-art-tips');
    var close = $('.close');

    btn.click(function () {
        modal.show();
    });

    close.click(function () {
        modal.hide();
    });

    $(window).on('click', function (e) {
        if ($(e.target).is('.modal')) {
            modal.hide();
        }
    });
})