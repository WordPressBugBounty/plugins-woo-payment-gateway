jQuery(function ($) {
    var settings = {
        prefix: '#' + $('#wc_braintree_prefix').val(),
        params: wc_braintree_setting_params,
        init: function () {
            $('[name^="woocommerce_braintree"]').on('change', this.display_children);

            $('select.braintree-accepted-cards').on('select2:select', this.reorder_multiselect);

            $('.domain-association').on('click', this.create_domain_association.bind(this));

            $('.wc-braintree-connection-test').on('click', this.connection_test.bind(this));

            $('.wc-braintree-settings-modal').on('click', this.open_modal.bind(this));

            $(document.body).on('wc_backbone_modal_response', this.store_response.bind(this));

            this.display_children();

            $.each(this.params.templates, function (i, template) {
                $('body').append(template);
            })
        },
        display_children: function (e) {
            $('[data-show-if]').each(function (el) {
                var $this = $(this);
                var values = $this.data('show-if');
                var hidden = [];
                $.each(values, function (k, v) {
                    var $key = $(settings.prefix + k);
                    if (hidden.indexOf($this.attr('id')) < 0) {
                        if ($key.is(':checkbox')) {
                            if ($key.is(':checked') == v) {
                                $this.closest('tr').show();
                            } else {
                                $this.closest('tr').hide();
                                hidden.push($this.attr('id'));
                            }
                        } else {
                            if ($key.val() == v) {
                                $this.closest('tr').show();
                            } else {
                                $this.closest('tr').hide();
                                hidden.push($this.attr('id'));
                            }
                        }
                    } else {
                        $this.closest('tr').hide();
                        hidden.push($this.attr('id'));
                    }
                    var show_if = '.show_if_' + k + '_' + $key.val();
                    var hide_if = '.hide_if_' + k + '_' + $key.val();
                    $(show_if).show();
                    $(hide_if).hide();
                });
            });
        },
        reorder_multiselect: function (e) {
            var element = e.params.data.element;
            var $element = $(element);
            $element.detach();
            $(this).append($element);
            $(this).trigger('change');
        },
        create_domain_association: function (e) {
            e.preventDefault();
            this.block();
            $.ajax({
                url: wc_braintree_applepay_params.routes.domain_association,
                dataType: 'json',
                method: 'POST',
                data: {_wpnonce: this.params.rest_nonce, hostname: window.location.hostname}
            }).done(function (response) {
                this.unblock();
                $(e.target).WCBackboneModal({
                    template: 'wc-braintree-message',
                    variable: response
                });
                //window.alert(response.message);
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                window.alert(errorThrown);
            }.bind(this))
        },
        connection_test: function (e) {
            e.preventDefault();
            this.block();
            var env = $('#woocommerce_braintree_api_environment').val();
            $.ajax({
                url: woocommerce_braintree_api_settings_params.routes.connection_test,
                dataType: 'json',
                method: 'POST',
                data: {
                    _wpnonce: this.params.rest_nonce,
                    environment: env,
                    merchant_id: $('#woocommerce_braintree_api_' + env + '_merchant_id').val(),
                    public_key: $('#woocommerce_braintree_api_' + env + '_public_key').val(),
                    private_key: $('#woocommerce_braintree_api_' + env + '_private_key').val()
                }
            }).done(function (response) {
                window.alert(response.message);
                this.unblock();
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                window.alert(errorThrown);
            }.bind(this))
        },
        block: function () {
            $('.wc-braintree-settings-container').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },
        unblock: function () {
            $('.wc-braintree-settings-container').unblock();
        },
        open_modal: function (e) {
            e.preventDefault();
            $(this).WCBraintreeCreditCardSettings({
                template: 'wc-braintree-template-cc-settings',
                data: {
                    styles: woocommerce_braintree_cc_settings_params.custom_form_styles,
                    style_options: woocommerce_braintree_cc_settings_params.style_options,
                    url: this.params.routes.payment_gateways + '/braintree_cc',
                    rest_nonce: this.params.rest_nonce
                }
            });
        },
        store_response: function (e, target, data) {
            if (target === 'wc-braintree-template-cc-settings') {
                woocommerce_braintree_cc_settings_params.custom_form_styles = data;
            }
        }
    };

    $.WCBraintreeCreditCardSettings = function (element, options) {
        var settings = _.extend($.WCBackboneModal.defaultOptions, options);

        if (settings.template) {
            new $.WCBraintreeCreditCardSettings.View({
                target: settings.template,
                string: settings.data
            });
        }
    }

    $.fn.WCBraintreeCreditCardSettings = function (options) {
        return this.each(function () {
            new $.WCBraintreeCreditCardSettings($(this), options);
        });
    }

    $.WCBraintreeCreditCardSettings.View = $.WCBackboneModal.View.extend({
        events: _.extend($.WCBackboneModal.View.prototype.events, {
            'click button.save': 'save',
            'click .add-new-style': 'add_row',
            'click .delete': 'remove_row',
            'click .reset': 'reset'
        }),
        render: function () {
            $.WCBackboneModal.View.prototype.render.apply(this, arguments);
            this.render_styles();
        },
        render_styles: function () {
            if (this._string.styles) {
                for (var k in this._string.styles) {
                    for (var i in this._string.styles[k]) {
                        var row = {field: k, option: i, value: this._string.styles[k][i]};
                        this.append_row(row);
                    }
                }
            }
        },
        add_row: function (e) {
            e.preventDefault();
            this.append_row({
                field: '',
                option: '',
                value: ''
            });
        },
        append_row: function (row) {
            var template = wp.template('wc-braintree-cc-settings-row');
            this.$el.find('tbody').append(template({
                fields: this.get_fields(),
                options: this._string.style_options,
                style: row
            }));
        },
        remove_row: function (e) {
            $(e.currentTarget).closest('tr').remove();
        },
        reset: function (e) {
            e.preventDefault();
            this._string.styles = {
                'input': {
                    'font-size': '16px',
                    'font-family': 'helvetica, tahoma, calibri, sans-serif',
                    'color': '#3a3a3a'
                }
            };
            this.$el.find('tbody').empty();
            this.render_styles();
        },
        get_fields: function () {
            return ['input', '.number', '.valid', '.invalid', ':focus'];
        },
        toJson: function () {
            var styles = {};
            this.$el.find('tbody tr').each(function () {
                var $tr = $(this);
                var type = $tr.find('[name="style_type"]').val(),
                    option = $tr.find('[name="style_option"]').val(),
                    value = $tr.find('[name="style_value"]').val();
                if (!styles[type]) {
                    styles[type] = {};
                }
                styles[type][option] = value;
            });
            if ($.isEmptyObject(styles)) {
                styles = '';
            }
            return styles;
        },
        block: function () {
            this.$el.find('.wc-backbone-modal-content').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },
        unblock: function () {
            this.$el.find('.wc-backbone-modal-content').unblock();
        },
        save: function (e) {
            e.preventDefault();
            this.block();
            $.ajax({
                method: 'POST',
                dataType: 'json',
                data: {
                    _wpnonce: this._string.rest_nonce,
                    settings: {custom_form_styles: this.toJson()}
                },
                url: this._string.url
            }).done(function () {
                this.unblock();
                this.trigger_response(e);
                this.remove();
            }.bind(this)).fail(function () {
                this.unblock();
            }.bind(this));
        },
        trigger_response: function (e) {
            this.closeButton(e);
            $(document.body).trigger('wc_backbone_modal_response', [this._target, this.toJson()]);
        }
    });


    settings.init();
});