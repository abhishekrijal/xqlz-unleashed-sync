(function ($) {
    $(document).ready(function () {
        $('.xqlupdate-single-data').on('click', function (e) {

            e.preventDefault();

            var curId = $(this).data("productid");
            var curEle = $(this);
            var jqxhr = $.ajax({
                type: "POST",
                /* async:false, // set async false to wait for previous response */
                url: ajaxurl,
                dataType: "json",
                data: { action: "xqluz_update_wc_products", product_id: curId },

            });
            jqxhr.always(function (data) {
                // removeAndCheck(curEle);
            }
            );
        }
        );
    });
})(jQuery);