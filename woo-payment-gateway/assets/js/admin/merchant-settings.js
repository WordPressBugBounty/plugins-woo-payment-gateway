jQuery(function ($) {
    var environments = ["production", "sandbox"];
    var settings = {
        params: woocommerce_braintree_merchant_account_settings_params,
        init: function () {
            this.init_merchant_accounts();
        },
        init_merchant_accounts: function () {
            _.each(environments, function (env) {
                new MerchantContainer({
                    environment: env
                });
            });
        }
    };

    var MerchantAccount = Backbone.Model.extend({
        defaults: {
            merchant_account: '',
            currency: ''
        }
    });

    var MerchantView = Backbone.View.extend({
        tagName: 'tr',
        events: {
            'click .wc-braintree-delete-row': 'removeMerchantAccount',
            'change .wc-braintree-currency': 'updateCurrency'
        },
        initialize: function (params) {
            this.parent = params.parent;
            this.model.set('index', this.parent.index);
            this.environment = params.environment;
            this.template = _.template($('#wc-braintree-' + this.environment + 'merchant-account-template').html());
            this.render();
        },
        render: function () {
            this.$el.html(this.template(this.model.toJSON()));
            var that = this;
            this.$el.find('select option').each(function () {
                if ($(this).val() === that.model.get('currency')) {
                    $(this).prop('selected', true);
                }
            })
            this.$el.find('.wc-enhanced-select').select2({width: '100px'}).trigger('change');
            return this;
        },
        removeMerchantAccount: function () {
            this.remove();
        },
        updateCurrency: function (e) {
            var currency = $(e.target).val();
            var $input = this.$el.find('.wc-braintree-merchant-account');
            $input.attr('name', $input.data('field-key') + '[' + currency + ']');
        }
    });

    var MerchantCollection = Backbone.Collection.extend({
        model: MerchantAccount
    });

    var MerchantsView = Backbone.View.extend({
        tagName: 'tbody',
        index: 0,
        initialize: function (params) {
            this.environment = params.environment;
            this.setElement($('#wc-braintree-' + this.environment + '-merchant-tbody'));
            this.render();
        },
        render: function () {
            this.collection = new MerchantCollection();
            if (typeof settings.params[this.environment + '_merchant_accounts'] !== "undefined") {
                this.merchants = settings.params[this.environment + '_merchant_accounts'],
                    _.each(this.merchants, function (account, currency) {
                        this.collection.add(new MerchantAccount({
                            currency: currency,
                            merchant_account: account
                        }));
                    }, this);
                this.collection.each(function (merchantAccount) {
                    var merchantView = new MerchantView({
                        model: merchantAccount,
                        parent: this,
                        environment: this.environment
                    });
                    merchantView.listenTo(this, 'clear_children', merchantView.remove);
                    this.$el.append(merchantView.$el);
                    this.index++;
                }, this);
            }
        },
        addMerchantAccount: function () {
            var merchantView = new MerchantView({
                model: new MerchantAccount(),
                parent: this,
                environment: this.environment
            });
            merchantView.listenTo(this, 'clear_children', merchantView.remove);
            this.$el.append(merchantView.$el);
            this.index++;
        },
        clearChildren: function () {
            this.trigger('clear_children');
        }
    });

    var MerchantContainer = Backbone.View.extend({
        events: {
            'click .wc-braintree-add-merchant': 'addMerchantAccount',
            'click .wc-braintree-import-accounts': 'importAccounts'
        },
        initialize: function (params) {
            this.environment = params.environment;
            this.el_id = '#wc-braintree-' + this.environment + '-merchant-container';
            this.setElement($(this.el_id));
            this.render();
        },
        render: function () {
            this.merchantsView = new MerchantsView({
                environment: this.environment
            });
            return this;
        },
        addMerchantAccount: function (e) {
            e.preventDefault();
            this.merchantsView.addMerchantAccount();
        },
        importAccounts: function (e) {
            e.preventDefault();
            $.blockUI({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            var that = this;
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: settings.params.routes.merchant_account,
                data: {
                    _wpnonce: settings.params._wpnonce,
                    environment: this.environment
                }
            }).done(function (response) {
                $.unblockUI();
                if (response.code) {
                    window.alert(response.message);
                    return;
                }
                settings.params[that.environment + '_merchant_accounts'] = response;
                that.merchantsView.clearChildren();
                that.merchantsView.render();
            }).fail(function (xhr, textStatus, errorThrown) {
                $.unblockUI();
                window.alert(errorThrown);
            })
        }
    });

    settings.init();
})