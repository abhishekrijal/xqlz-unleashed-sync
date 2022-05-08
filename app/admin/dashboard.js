import CryptoJS from 'crypto-js';
import { useState } from "@wordpress/element";
import $ from 'jquery';
export default () => {
    (function ($) {
        $(document).ready(function () {
            function getWcProducts() {
                var paged = $("#curpro_pageid").val();
                if (paged.length == 0 || paged == 0) {
                    paged = 1;
                }
                var data = { "paged": paged, "action": "xqluz_get_wc_products" };
                $.post(ajaxurl, data, function (response) {
                    $("#bulk-update-process").html("");
                    var productsHtml = '';
                    if (response.status == 1) {
                        productsHtml += '<table class="wp-list-table widefat">';
                        productsHtml += '<thead><tr><td>Product Id</td><td>Product Name</td><td>Action</td></tr></thead><tbody>';
                        $(response.items).each(function (index, item) {
                            productsHtml += '<tr class="product-row" data-productid="' + item.ID + '"><td>' + item.ID + '</td><td>' + item.post_title + '</td><td class="processing-td">Processing...</td></tr>';
                        });
                        productsHtml += '</tbody></table>';
                        $("#curpro_pageid").val(response.next_page);
                        $("#bulk-update-process").html(productsHtml);
                        checkItemsRecursively();
                    }
                }, 'json');
            }

            function removeAndCheck(curEle) {
                curEle.remove();
                var totalRows = $(".product-row").length;
                if (totalRows == 0) {
                    getWcProducts();
                }

            }
            function checkItemsRecursively() {
                var status = 1;
                $("#bulk-update-process .product-row").each(function () {
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
                        removeAndCheck(curEle);
                    }
                    );
                });

            }


            $("#saveoptions").click(function () {

                $("#processLoading").addClass("active");

                $(".info-block-remote").html('Saving Settings...');

                var data = $("#settings_form").serialize();

                $.post(ajaxurl, data, function (response) {

                    var msgTitle = 'Warning';

                    var msgBody = response.message;

                    if (response.status == 1) {

                        msgTitle = 'Success';

                    }

                    var infomsg = '<div style="position:relative;" class="notice notice-warning"><a href="#" class="dismiss-current-block woocommerce-message-close notice-dismiss" style="position:absolute;right:1px;padding:9px;text-decoration:none;" data-dismiss="alert" aria-label="close" title="close"></a><p><strong>' + msgTitle + '!</strong> ' + msgBody + '</p></div>';

                    $(".notification").html(infomsg);

                    $("#processLoading").removeClass("active");



                }, 'json');

            });
            $(".notification").on("click", ".dismiss-current-block", function () {
                $(".notification").html("");
            });
            $(".bulk-update-unleashed").click(function () {
                getWcProducts();

            });

        });
    })(jQuery);
    const [page, setPage] = useState(1);
    const [unleashedProducts, setUnleashedProducts] = useState([]);
    // build the url based on the different parameters
    var urlParam = "pageSize=100";
    //CryptoJS is being used in javascript to generate the hash security keys
    // We need to pass the url parameters as well as the key to return a SHA256
    var hash = CryptoJS.HmacSHA256(urlParam, 'rdkPWaGiaKt2ZnoXrDx2HwC5Gin6NiVy0upndM1jSp19L4m9oOjffGCF4D6fJyHNmltOy8zeMcCU2QGKiw==');
    // That hash generated has to be set into base64
    var hash64 = CryptoJS.enc.Base64.stringify(hash);
    const getProductsFromUnleashed = async () => {
        const response = await fetch(`https://api.unleashedsoftware.com/Products/${page}?${urlParam}`, {
            headers: {
                'Accept': 'application/json',
                'api-auth-id': '8195221a-0157-4502-84b5-8abbfe135328',
                'api-auth-signature': hash64,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        // return if data is not empty
        if (data?.Items.length > 0) {
            let updatedData = unleashedProducts.concat(data.Items);
            setUnleashedProducts(updatedData);
            setPage(page + 1);
        }
        return data;
    }
    return (
        <>
            <div className="header-sync">
                <h1>Woocommerce | Unleashed Sync</h1>
            </div>

            {/* List products in table if available */}
            {unleashedProducts.length > 0 ? (
                <table className="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Price</th>
                            <th scope="col">Image</th>
                            <th scope="col">SKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        {unleashedProducts.map((product, index) => {
                            return (
                                <tr key={index}>
                                    <td>{product.ProductCode}</td>
                                    <td>{product.ProductDescription}</td>
                                    <td>{product.DefaultSellPrice}</td>
                                    <td><img width={150} height={150} src={product.ImageUrl} /></td>
                                    <td>{product.ProductCode}</td>
                                </tr>
                            )
                        })}
                    </tbody>
                </table>
            ) : (
                <div className="header-sync">
                    <h1>No products found</h1>
                </div>
            )}

            <button onClick={getProductsFromUnleashed} className="button button-primary">Get Products from API</button>
            Unleashed Product (Bulk Update)
            <button className="button bulk-update-unleashed button-secondary" type="button">Bulk Update</button>
            <div id="bulk-update-process"></div>
            <input type="hidden" id="curpro_pageid" value="1" />
        </>
    );
}