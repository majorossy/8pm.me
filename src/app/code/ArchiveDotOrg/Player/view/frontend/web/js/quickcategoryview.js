define(['jquery', 'Magento_Ui/js/modal/modal', 'mage/loader', 'Magento_Customer/js/customer-data','collapsible'], function($, modal, loader, customerData) {
    'use strict';
    return function(config, node) {
        var product_id = jQuery(node).data('id');
        var product_url = jQuery(node).data('url');
        var options = {
            type: 'popup',
            responsive: true,
            innerScroll: false,
            title: $.mage.__('Quick View'),
            buttons: [{
                text: $.mage.__('Close'),
                class: 'close-modal',
                click: function() {
                    this.closeModal();
                }
            }]
        };
        var popup = modal(options, $('#quickViewContainer' + product_id));
        $("#quickViewButton" + product_id).on("click", function() {
            openQuickViewModal();
        });
        var openQuickViewModal = function() {
            var modalContainer = $("#quickViewContainer" + product_id);
            modalContainer.html(createIframe());
            var iframearea = "#new_frame" + product_id;
            $(iframearea).on("load", function() {
                modalContainer.addClass("product-quickview");
                modalContainer.modal('openModal');
                observeAddToCart(this);
            });
        };
        var observeAddToCart = function(iframe) {
            var doc = iframe.contentWindow.document;
            $(doc).contents().find('#product_addtocart_form').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    data: $(this).serialize(),
                    type: $(this).attr('method'),
                    url: $(this).attr('action'),
                    success: function(response) {
                        $(".close-modal").trigger("click");
                        $('[data-block="minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog("open");
                    }
                });
            });
        };
        var createIframe = function() {
            return $('<iframe />', {
                id: 'new_frame' + product_id,
                class:'a-category-quicview-iframe',
                src: product_url + "?iframe=1"
            });
        }
    };
});