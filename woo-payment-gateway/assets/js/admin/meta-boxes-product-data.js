(function ($) {
    function Product() {
        this.init();
    }

    Product.prototype.params = {
        loadingClass: 'woocommerce-input-toggle--loading',
        enabledClass: 'woocommerce-input-toggle--enabled',
        disabledClass: 'woocommerce-input-toggle--disabled'
    }

    Product.prototype.init = function () {
        $('table.wc_gateways').sortable({
            items: 'tr',
            axis: 'y',
            cursor: 'move',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            helper: 'clone',
            opacity: 0.65,
            placeholder: 'wc-metabox-sortable-placeholder',
            start: function (event, ui) {
                ui.item.css('background-color', '#f6f6f6');
            },
            stop: function (event, ui) {
                ui.item.removeAttr('style');
            },
            change: function () {
                this.setting_changed();
            }.bind(this)
        });

        $('table.wc_gateways').find('.wc-move-down, .wc-move-up').on('click', this.move_gateway.bind(this));
        $('table.wc_gateways .wc-braintree-product-gateway-enabled').on('click', this.toggle_gateway.bind(this));
        $('.wc-braintree-save-product-data').on('click', this.save.bind(this));
        $('.wc-braintree-gateway-product-options').on('click', this.open_options);
        $(document.body).on('wc_backbone_modal_response', this.save_settings.bind(this));
    }

    /**
     * [Move the payment gateway up or down]
     * @return {[type]} [description]
     */
    Product.prototype.move_gateway = function (e) {
        var $this = $(e.currentTarget);
        var $row = $this.closest('tr');

        var moveDown = $this.is('.wc-move-down');

        if (moveDown) {
            var $next = $row.next('tr');
            if ($next && $next.length) {
                $next.after($row);
            }
        } else {
            var $prev = $row.prev('tr');
            if ($prev && $prev.length) {
                $prev.before($row);
            }
        }
        this.setting_changed();
    }

    Product.prototype.setting_changed = function () {
        $('#wc_braintree_update_product').val('true');
    }

    Product.prototype.open_options = function (e) {
        e.preventDefault();
        $(this).WCBraintreeProductModal({
            template: 'wc-product-' + $(this).data('gateway-id'),
            data: {
                product_type: $('#product-type').val(),
                is_preorder: $('#_wc_pre_orders_enabled').length > 0 && $('#_wc_pre_orders_enabled').is(':checked')
            }
        });
    }

    Product.prototype.block = function () {
        $('#braintree_product_data').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    }

    Product.prototype.unblock = function () {
        $('#braintree_product_data').unblock();
    }

    /**
     * [enable_gateway description]
     * @param  {[type]} e [description]
     * @return {[type]}   [description]
     */
    Product.prototype.toggle_gateway = function (e) {
        e.preventDefault();
        var $el = $(e.currentTarget),
            $row = $el.closest('tr'),
            $toggle = $el.find('.woocommerce-input-toggle');
        $toggle.addClass(this.params.loadingClass);
        $.ajax({
            url: wc_braintree_product_params.routes.enable_gateway,
            method: 'POST',
            dataType: 'json',
            data: {
                _wpnonce: wc_braintree_product_params._wpnonce,
                product_id: $('#post_ID').val(),
                gateway_id: $row.data('gateway_id')
            }
        }).done(function (response) {
            $toggle.removeClass(this.params.loadingClass);
            if (response.enabled) {
                $toggle.addClass(this.params.enabledClass).removeClass(this.params.disabledClass);
            } else {
                $toggle.removeClass(this.params.enabledClass).addClass(this.params.disabledClass);
            }
            $('#tmpl-wc-product-' + $row.data('gateway_id')).replaceWith(response.html);
        }.bind(this)).fail(function () {
            $toggle.removeClass(this.params.loadingClass);
        }.bind(this))
    }

    Product.prototype.save = function (e) {
        e.preventDefault();
        this.block();
        var gateways = [];
        $('[name^="braintree_gateway_order"]').each(function (idx, el) {
            gateways.push($(el).val());
        });
        $.ajax({
            url: wc_braintree_product_params.routes.save,
            method: 'POST',
            dataType: 'json',
            data: {
                _wpnonce: wc_braintree_product_params._wpnonce,
                gateways: gateways,
                product_id: $('#post_ID').val(),
                position: $('#_braintree_button_position').val()
            }
        }).done(function () {
            this.unblock();
        }.bind(this)).fail(function () {
            this.unblock();
        }.bind(this))
    }

    Product.prototype.save_settings = function (e, target, data) {
        if (target.indexOf('wc-product-') > -1) {
            this.block();
            var gateway = target.substring('wc-product-'.length);
            $.ajax({
                url: wc_braintree_product_params.routes.save + '/' + gateway,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, data, {
                    _wpnonce: wc_braintree_product_params._wpnonce,
                    product_id: $('#post_ID').val()
                })
            }).done(function (response) {
                this.unblock();
                if (response.code) {
                    window.alert(response.message);
                } else {
                    $('#tmpl-' + target).replaceWith(response.html);
                    var addClass = this.params.enabledClass, removeClass = this.params.disabledClass;
                    if (response.settings.enabled === 'no') {
                        addClass = this.params.disabledClass;
                        removeClass = this.params.enabledClass;
                    }
                    $('tr[data-gateway_id="' + gateway + '"]').find('.woocommerce-input-toggle').addClass(addClass).removeClass(removeClass);
                }
            }.bind(this)).fail(function () {
                this.unblock();
            }.bind(this));
        }
    }

    $.fn.WCBraintreeProductModal = function (options) {
        return this.each(function () {
            new WCBraintreeProductModal($(this), options);
        });
    }

    var WCBraintreeProductModal = function (element, options) {
        var settings = _.extend($.WCBackboneModal.defaultOptions, options);
        if (settings.template) {
            // prevent caching of template
            delete wp.template.cache[settings.template];
            var view = new WCBraintreeProductModal.View({
                target: settings.template,
                string: settings.data
            });
            if ($().selectWoo) {
                view.$el.find('.wc-enhanced-select').selectWoo();
            }
        }
    }

    WCBraintreeProductModal.View = $.WCBackboneModal.View.extend({
        events: _.extend({}, $.WCBackboneModal.View.prototype.events, {
            'click .btn-save-product-options': 'addButton'
        }),
        getFormData: function () {
            var data = $.WCBackboneModal.View.prototype.getFormData.apply(this, arguments);
            var extra_data = $('form input:checkbox', this.$el).map(function (i, element) {
                return {name: element.name, value: element.checked ? 'yes' : null};
            }).get();
            extra_data.forEach(function (obj) {
                data[obj.name] = obj.value;
            });
            return data;
        }
    });

    new Product();
}(jQuery))