import $ from 'jquery';
import {isEqual} from 'lodash';
import {isEmail} from '@wordpress/url';

class FastlaneCheckout {

    constructor(props) {
        this.settings = props.settings;
        this.components = {};
        this.currentCard = null;
        this.canceledEmails = [];
        this.customerContextId = null;
    }

    create_instance(client, clientToken) {
        this.client = client;
        this.clientToken = clientToken;
        this.initialize();
    }

    async initialize() {

        this.addActions();

        await this.initializeDataCollector();
        await this.createFastlaneInstance();
        const promises = [];
        if (this.isEmailDetectionEnabled()) {
            promises.push(this.mountFastlaneWatermark());
        }
        promises.push(this.authenticateOnPageLoad());
        await Promise.all(promises);

        $('.wc_braintree_banner_gateway_braintree_cc').css('max-width', $('.woocommerce-billing-fields').outerWidth(true));
    }

    addActions() {
        $(document.body).on('updated_checkout', this.onUpdatedCheckout.bind(this));
        $(document.body).on('input', '[name="billing_email"]', this.onEmailChange.bind(this));
        $(document.body).on('click', '.wc-ppcp-fastlane-button', this.onFastlaneClick.bind(this));
        $(document.body).on('click', '.wc-ppcp-fastlane-cancel', this.onCloseModal.bind(this));
        $(document.body).on('click', '.wc-ppcp-fastlane-tokenize', this.onPaymentTokenize.bind(this));
        $(document.body).on('click', '.wc-ppcp-tokenized-card-cancel', this.cancelTokenizedPayment.bind(this));
        $(document.body).on('click', '.wc-ppcp-tokenized-card-change', this.changePaymentMethod.bind(this));
        $(document.body).on('click', '.fastlane-signup-link-container', this.openFastlaneModal.bind(this));
        $(document.body).on('blur', '.wc-ppcp-fastlane-modal-field', (e) => {
            $(e.currentTarget).removeClass('focused');
        });
        $(document.body).on('keyup', '.wc-ppcp-fastlane-modal-input', e => {
            if ($(e.currentTarget).val()) {
                $(e.currentTarget).addClass('not-empty');
            } else {
                $(e.currentTarget).removeClass('not-empty');
            }
        })
    }

    hasComponents() {
        return Object.keys(this.components).length > 0;
    }

    isEmailDetectionEnabled() {
        return this.settings.fastlane_flow === 'email_detection';
    }

    async initializeDataCollector() {
        try {
            this.dataCollector = await braintree.dataCollector.create({
                client: this.client
            });
        } catch (error) {
            console.info('Error setting up data collector.');
        }
    }

    async createFastlaneInstance() {
        try {
            const fastlane = await braintree.fastlane.create({
                authorization: this.clientToken,
                ...(this.dataCollector && {
                    deviceData: this.dataCollector.deviceData
                })
            });
            if (fastlane) {
                const {
                    identity,
                    profile,
                    FastlanePaymentComponent,
                    FastlaneCardComponent,
                    FastlaneWatermarkComponent
                } = fastlane;

                this.identity = identity;
                this.profile = profile;
                this.components = {
                    FastlanePaymentComponent,
                    FastlaneCardComponent,
                    FastlaneWatermarkComponent
                }
            }
        } catch (error) {
            console.info('Error creating fastlane instance. ', error);
            return this.submitError(error, $('form.checkout'));
        }
    }

    async onUpdatedCheckout() {
        if (this.currentCard) {
            this.displayTokenizedCard(this.mapFromTokenToCard(this.currentCard));
        }
        if (this.isEmailDetectionEnabled()) {
            await this.mountFastlaneWatermark();
        }
    }

    async onPaymentTokenize(e) {
        if (this.paymentComponent) {
            const $button = $(e.currentTarget);
            $button.prop('disabled', true);
            try {

                const response = await this.paymentComponent.getPaymentToken();
                this.currentCard = response;
                this.displayTokenizedCard(this.mapFromTokenToCard(this.currentCard));
                this.prepareCardGatewayForCheckout(this.currentCard);
                this.onCloseModal();
                if (response?.paymentSource?.card?.billingAddress) {
                    let address = response.paymentSource.card.billingAddress;
                    if (response.paymentSource.card.billingAddress.toBtSdkType) {
                        address = response.paymentSource.card.billingAddress.toBtSdkType();
                    } else {
                        const name = this.extractFullName(response.paymentSource.card.name);
                        address = {
                            firstName: name[0],
                            lastName: name[1],
                            ...address
                        }
                        this.populateShippingAddress(address);
                    }
                    await this.updateBillingAddress(address);
                }
                this.scrollToPlaceOrder();
            } catch (error) {
                this.submitError(error, $('form.checkout'), 'checkout');
            } finally {
                $button.prop('disabled', false);
            }
        }
    }

    async mountFastlaneWatermark() {
        if (this.hasComponents()) {
            try {
                if ($('#billing_email_field').length) {
                    if (!$('#billing_email_field #wc-ppcp-watermark-container').length) {
                        const component = await this.components.FastlaneWatermarkComponent({includeAdditionalInfo: true});
                        $('#billing_email_field').append('<div id="wc-ppcp-watermark-container"></div>');
                        component.render('#wc-ppcp-watermark-container');
                    }
                }
            } catch (error) {
                console.info('Error mounting watermark. Reason: ', error);
            }
        }
    }

    async authenticateOnPageLoad() {
        if (this.settings.fastlane_pageload === '1' && $('#billing_email').length) {
            const email = $('#billing_email').val();
            if (isEmail(email)) {
                await this.triggerAuthentication(email, false);
            }
        }
    }

    async onEmailChange(e) {
        if (this.isEmailDetectionEnabled()) {
            clearTimeout(this.emailTimeout);
            this.emailTimeout = setTimeout(async () => {
                const email = $(e.currentTarget).val();
                if (!this.canceledEmails.includes(email)) {
                    if (isEmail(email)) {
                        await this.triggerAuthentication(email, false);
                        if (!this.canceledEmails.includes(email)) {
                            this.showSignupLink();
                        }
                    }
                } else {
                    this.hideSignupLink();
                }
            }, 250);
        }
    }

    async onFastlaneClick(e) {
        e.preventDefault();
        this.$button = $(e.currentTarget);
        const email = $('#billing_email').val();
        try {
            this.$button.addClass('processing').prop('disabled', true);
            this.validateEmail(email);
            await this.triggerAuthentication(email)
        } catch (error) {
            return this.submitError(error, $('form.checkout'));
        } finally {
            if (this.$button) {
                this.$button.removeClass('processing').prop('disabled', false);
            }
        }
    }

    async triggerAuthentication(email, showSignup = true) {
        try {
            this.authenticationState = null;
            const {
                customerContextId
            } = await this.identity.lookupCustomerByEmail(email);
            if (customerContextId) {
                this.customerContextId = customerContextId;
                const {
                    profileData,
                    authenticationState
                } = await this.identity.triggerAuthenticationFlow(customerContextId);

                this.authenticationState = authenticationState;
                if (authenticationState === 'canceled') {
                    this.canceledEmails.push(email);
                    this.hideSignupLink();
                } else if (authenticationState === 'succeeded') {
                    let billingAddress;
                    let {
                        name,
                        card,
                        shippingAddress
                    } = profileData;

                    this.currentCard = card;

                    /**
                     * 1. If shipping is required, show the shipping selector.
                     * 2. Let them select their payment method.
                     */

                    if (this.needsShipping()) {
                        const {
                            selectedAddress,
                            ...props
                        } = await this.profile.showShippingAddressSelector();
                        if (!selectedAddress) {
                            this.canceledEmails.push(email);
                            this.hideSignupLink();
                            return;
                        }
                        shippingAddress = selectedAddress;
                    }

                    if (card?.paymentSource?.card?.billingAddress) {
                        billingAddress = card.paymentSource.card.billingAddress;
                        this.populateBillingAddress({
                            ...name,
                            ...billingAddress,
                            phoneNumber: shippingAddress?.phoneNumber || null
                        });
                    }
                    if (shippingAddress && this.needsShipping()) {
                        await this.updateShippingAddress(shippingAddress, billingAddress);
                    }
                    this.prepareCardGatewayForCheckout(card);
                    this.displayTokenizedCard(this.mapFromTokenToCard(card));
                    this.scrollToPlaceOrder();
                }
            } else if (showSignup) {
                await this.openFastlaneModal();
            }
        } catch (error) {
            this.submitError(error, $('form.checkout'));
        }
    }

    async openFastlaneModal() {
        this.customerContextId = null;
        this.paymentComponent = await this.components.FastlanePaymentComponent({
            ...(this.needsShipping() && {
                shippingAddress: this.getFastlaneShippingAddress()
            })
        });
        this.$modal = $(this.settings.html.modal);
        $(document.body).prepend(this.$modal);
        $(document.body).addClass('fastlane-modal-open');
        this.$modal.find('.wc-ppcp-fastlane-modal-field').on('mousedown', (e) => {
            $(e.currentTarget).addClass('focused');
        });

        await this.paymentComponent.render('.wc-ppcp-fastlane-modal-body');

        setTimeout(() => {
            this.$modal.addClass('active');
        }, 10);
    }

    onCloseModal() {
        if (this.$modal) {
            this.$modal.removeClass('active');
            setTimeout(() => {
                this.$modal.remove();
                this.$modal = null;
                $(document.body).removeClass('fastlane-modal-open');
            }, 400);
        }
    }

    getFastlaneShippingAddress() {
        const shippingAddress = {
            firstName: $('#shipping_first_name').val(),
            lastName: $('#shipping_last_name').val(),
            streetAddress: $('#shipping_address_1').val(),
            extendedAddress: $('#shipping_address_2').val(),
            locality: $('#shipping_city').val(),
            region: $('#shipping_state').val(),
            postalCode: $('#shipping_postcode').val(),
            countryCodeAlpha2: $('#shipping_country').val(),
            phoneNumber: $('#billing_phone').val(),
        }
        return shippingAddress;
    }

    mapFromTokenToCard(data) {
        const card = data.paymentSource.card;
        return {
            brand: card.brand,
            card_type: card.brand,
            last4: card.lastDigits,
            expiry: card.expiry
        }
    }

    displayTokenizedCard(card) {
        let text = this.formatPaymentMethod(this.settings.payment_format, card);
        if (this.$card) {
            this.$card.remove();
            this.$card = null;
        }
        this.$card = $(this.settings.html.tokenized_card);
        if (this.customerContextId) {
            this.$card.addClass('has-customer-context');
        }
        this.$card.find('.wc-ppcp-tokenized-card-format').text(text);
        if (this.settings.icons[card.brand.toLowerCase()]) {
            const src = this.settings.icons[card.brand.toLowerCase()];
            this.$card.find('.wc-ppcp-tokenized-card-icon-container img').attr('src', src);
        }

        $('.payment_box.payment_method_braintree_cc .wc-braintree-payment-gateway').hide();
        $('.payment_box.payment_method_braintree_cc .wc-braintree-payment-gateway').after(this.$card);
    }

    cancelTokenizedPayment() {
        this.currentCard = null;
        this.authenticationState = null;
        if (this.$card) {
            this.$card.remove();
            this.$card = null;
            $('.payment_box.payment_method_braintree_cc .wc-braintree-payment-gateway').show();
            $('#braintree_cc_nonce_key').val('');
            const cardGateway = wc_braintree.get_payment_method('braintree_cc');
            cardGateway.set_is_fastlane(false);
            cardGateway.payment_method_received = false;
        }
    }

    async changePaymentMethod() {
        try {
            const {
                selectedCard,
                selectionChanged
            } = await this.profile.showCardSelector();
            if (selectedCard) {
                this.cancelTokenizedPayment();
                this.prepareCardGatewayForCheckout(selectedCard);
                this.displayTokenizedCard(this.mapFromTokenToCard(selectedCard));
                if (selectedCard?.paymentSource?.card?.billingAddress) {
                    await this.updateBillingAddress(selectedCard.paymentSource.card.billingAddress);
                }
            }
        } catch (error) {
            this.submitError(error, $('form.checkout'));
        }
    }

    populateBillingAddress(address) {
        const existing = {
            firstName: $('#billing_first_name').val(),
            lastName: $('#billing_last_name').val(),
            phoneNumber: $('#billing_phone').val()
        }
        $('#billing_first_name').val(address?.firstName || existing.firstName);
        $('#billing_last_name').val(address?.lastName || existing.lastName);
        $('#billing_address_1').val(address?.streetAddress || '');
        $('#billing_address_2').val(address?.extendedAddress || '');
        $('#billing_postcode').val(address?.postalCode || '');
        $('#billing_city').val(address?.locality || '');
        $('#billing_state').val(address?.region || '');
        $('#billing_country').val(address?.countryCodeAlpha2 || '');
        $('#billing_phone').val(address?.phoneNumber || existing.phoneNumber);
    }

    populateShippingAddress(shippingAddress) {
        $('#shipping_first_name').val(shippingAddress?.firstName || '');
        $('#shipping_last_name').val(shippingAddress?.lastName || '');
        $('#shipping_address_1').val(shippingAddress?.streetAddress || '');
        $('#shipping_address_2').val(shippingAddress?.extendedAddress || '');
        $('#shipping_postcode').val(shippingAddress?.postalCode || '');
        $('#shipping_city').val(shippingAddress?.locality || '');
        $('#shipping_state').val(shippingAddress?.region || '');
        $('#shipping_country').val(shippingAddress?.countryCodeAlpha2 || '');
        $('#shipping_phone').val(shippingAddress?.phoneNumber || '');
    }

    updateBillingAddress(address) {
        return new Promise((resolve) => {
            this.populateBillingAddress(address);
            $(document.body).one('updated_checkout', () => {
                resolve(true);
            });
            $(document.body).trigger('update_checkout');
        });
    }

    updateShippingAddress(shippingAddress, billingAddress = null) {
        return new Promise(resolve => {
            this.populateShippingAddress(shippingAddress);
            if (billingAddress && !isEqual(billingAddress, shippingAddress)) {
                if (!$('[name="ship_to_different_address"]').is(':checked')) {
                    $('[name="ship_to_different_address"]').trigger('click');
                }
            }
            $(document.body).one('updated_checkout', () => {
                resolve(true);
            });
            $(document.body).trigger('update_checkout');
        });
    }

    prepareCardGatewayForCheckout(token) {
        const cardGateway = wc_braintree.get_payment_method('braintree_cc');
        cardGateway.set_is_fastlane(true);
        cardGateway.payment_method_received = true;
        $('#braintree_cc_nonce_key').val(token.id);
        $('input[name="payment_method"][value="braintree_cc"]').trigger('click');
        $('#braintree_cc_use_nonce').trigger('click');
    }

    validateEmail(email) {
        if (!email) {
            throw new Error(this.settings.i18n.email_empty);
        }
        if (!isEmail(email)) {
            throw new Error(this.settings.i18n.email_invalid);
        }
    }

    scrollToPlaceOrder() {
        const $el = $('#place_order');
        if ($el.length) {
            $('html, body').animate(
                {
                    scrollTop: $el.offset().top - 200,
                },
                1000
            );
        }
    }

    needsShipping() {
        return this.settings.needs_shipping;
    }

    formatPaymentMethod(format, data) {
        const replacementData = {};

        // Create placeholders from the data object
        Object.keys(data).forEach(key => {
            replacementData[`{${key}}`] = data[key];
        });

        // Replace all placeholders with actual values
        let result = format;
        Object.keys(replacementData).forEach(placeholder => {
            // Use split and join which is more efficient for simple replacements
            while (result.includes(placeholder)) {
                result = result.replace(placeholder, replacementData[placeholder]);
            }
        });

        return result;
    }

    submitError(error, $el) {
        $(document.body).triggerHandler('wc_braintree_submit_error', {
            error: error,
            element: $el,
            ignore: true
        });
    }

    extractFullName(name) {
        const firstName = name.split(' ').slice(0, -1).join(' ');
        const lastName = name.split(' ').pop();
        return [firstName, lastName];
    }

    hideSignupLink() {
        $('.fastlane-signup-link-container').hide();
    }

    showSignupLink() {
        $('.fastlane-signup-link-container').show();
    }

}

wc_braintree.register(function () {
    return new FastlaneCheckout({
        settings: wc_braintree_fastlane_params
    })
});