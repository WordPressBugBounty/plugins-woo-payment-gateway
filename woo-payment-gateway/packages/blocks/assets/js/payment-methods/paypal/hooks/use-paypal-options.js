import {useEffect, useMemo, useCallback, useRef} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {isEqual, isEmpty} from 'lodash';
import {getSetting} from '@woocommerce/settings';
import {
    extractSelectedShippingOption,
    removeNumberPrecision,
    getCartTotalItem,
    extractFullName,
    getShippingOptionId,
    cartContainsSubscription,
    DEFAULT_BILLING_ADDRESS,
    DEFAULT_SHIPPING_ADDRESS
} from "../../utils";
import {usePaymentEventsHandler} from "../../hooks";
import {getSelectedShippingOptionId} from "../../googlepay/helpers";
import {usePaymentMethodDataContext} from "../../context";

export const usePayPalOptions = (
    {
        getData,
        addNotice,
        paypal,
        paypalCheckoutInstance,
        billing,
        shippingData,
        eventRegistration,
        emitResponse,
        onClick,
        onClose
    }) => {
    const {cartTotal, currency} = billing;
    const {needsShipping} = shippingData;
    const {noticeContexts, responseTypes} = emitResponse;
    const currentData = useRef({billing, shippingData});
    const paymentData = useRef({});
    const {addShippingHandler} = usePaymentEventsHandler({billing, shippingData, eventRegistration});
    const {setPaymentMethodNonce, onPaymentDataFilter, onSubmit} = usePaymentMethodDataContext();
    const {onCheckoutFail} = eventRegistration;

    useEffect(() => {
        const unsubscribe = onCheckoutFail(() => {
            if (paymentData.current.incompleteBilling) {
                return {
                    type: responseTypes.ERROR,
                    message: __('Please complete all required billing fields below then click Place Order.', 'woo-payment-gateway'),
                    messageContext: noticeContexts.EXPRESS_PAYMENTS
                };
            }
            return null;
        });
        return () => unsubscribe();
    }, [
        onCheckoutFail,
        responseTypes,
        noticeContexts
    ]);

    const getButtonStyle = useCallback((type) => {
        const baseStyle = getData('buttonStyle');
        switch (type) {
            case paypal.FUNDING.PAYPAL:
                return baseStyle;
            case paypal.FUNDING.PAYLATER:
                return {...baseStyle, ...getData('bnplButtonStyle')};
            default:
                return baseStyle;

        }
    }, [paypal]);

    const getFormattedShippingOptions = (shippingRates) => {
        let options = [];
        shippingRates.forEach((shippingPackage, idx) => {
            let rates = shippingPackage.shipping_rates.map(rate => {
                let txt = document.createElement('textarea');
                txt.innerHTML = rate.name;
                return {
                    id: getShippingOptionId(idx, rate.rate_id),
                    label: txt.value,
                    type: 'SHIPPING',
                    selected: rate.selected,
                    amount: {
                        value: removeNumberPrecision(rate.price, rate.currency_minor_unit),
                        currency_code: rate.currency_code
                    }
                }
            });
            options = [...options, ...rates];
        });
        return options;
    };

    useEffect(() => {
        currentData.current = {billing, shippingData, onClick, onClose, onSubmit, addNotice};
    }, [
        billing,
        shippingData,
        onClick
    ]);

    useEffect(() => {
        const unsubscribe = onPaymentDataFilter((data, {billing, shippingData}) => {
            data.meta.paymentMethodData[`${getData('name')}_nonce_key`] = paymentData.current.nonce;
            data.meta.billingAddress = {
                ...DEFAULT_BILLING_ADDRESS,
                ...billing.billingAddress,
                ...paymentData.current.billingAddress
            };
            data.meta.shippingAddress = {
                ...DEFAULT_SHIPPING_ADDRESS,
                ...shippingData.shippingAddress,
                ...paymentData.current.shippingAddress
            }

            return data;
        }, 5);
        return () => unsubscribe();
    }, [onPaymentDataFilter]);

    const onShippingChange = useCallback((actions, selectedShippingOption, shippingDataEqual, resolve) => (success, {billing, shippingData}) => {
        const {currency, cartTotal, cartTotalItems} = billing;
        const {shippingRates} = shippingData;
        if (success) {
            // create the patch
            const patch = [{
                'op': 'replace',
                'path': '/purchase_units/@reference_id==\'default\'/amount',
                'value': {
                    'currency_code': currency.code,
                    'value': removeNumberPrecision(cartTotal.value, 2),
                    'breakdown': {
                        'item_total': {
                            'currency_code': currency.code,
                            'value': removeNumberPrecision(getCartTotalItem('total_items', cartTotalItems), currency.minorUnit) +
                                removeNumberPrecision(getCartTotalItem('total_fees', cartTotalItems), currency.minorUnit)
                        },
                        'shipping': {
                            'currency_code': currency.code,
                            'value': removeNumberPrecision(getCartTotalItem('total_shipping', cartTotalItems), currency.minorUnit)
                        },
                        'tax_total': {
                            'currency_code': currency.code,
                            'value': removeNumberPrecision(getCartTotalItem('total_tax', cartTotalItems), currency.minorUnit)
                        },
                        'shipping_discount': {
                            'currency_code': currency.code,
                            'value': removeNumberPrecision(getCartTotalItem('total_discount', cartTotalItems), currency.minorUnit)
                        }
                    }
                }
            }, {
                'op': !selectedShippingOption ? 'add' : 'replace',
                'path': '/purchase_units/@reference_id==\'default\'/shipping/options',
                'value': getFormattedShippingOptions(shippingRates)
            }];
            return actions.order.patch(patch).then(() => {
                resolve();
                actions.resolve()
            });
        } else {
            resolve();
            return actions.reject();
        }
    }, []);

    const options = useMemo(() => {
        if (paypal && paypalCheckoutInstance) {
            const options = [];
            const sources = [paypal.FUNDING.PAYPAL];
            if (getData('bnplEnabled')) {
                sources.push(paypal.FUNDING.PAYLATER);
            }
            for (let fundingSource of sources) {
                const option = {
                    fundingSource,
                    style: getButtonStyle(fundingSource),
                    onError: (error) => currentData.current.addNotice(error),
                    onClick: () => {
                        currentData.current.onClick();
                    },
                    onApprove: (data, actions) => {
                        return paypalCheckoutInstance.tokenizePayment(data).then(response => {
                            const {billingAddress} = currentData.current.billing;
                            const {needsShipping} = currentData.current.shippingData;
                            // set shipping address
                            paymentData.current = {nonce: response.nonce, billingAddress: {}};
                            if (response?.details?.billingAddress) {
                                const address = response.details.billingAddress;
                                paymentData.current.billingAddress = {
                                    address_1: address.line1,
                                    address_2: address.line2,
                                    city: address.city,
                                    state: address.state,
                                    postcode: address.postalCode,
                                    country: address.countryCode
                                }
                            }
                            if (!isEmpty(response?.details?.shippingAddress)) {
                                const {shippingAddress} = response.details;
                                const [first_name, last_name] = extractFullName(shippingAddress.recipientName);
                                paymentData.current.shippingAddress = {
                                    first_name,
                                    last_name,
                                    address_1: shippingAddress.line1 || '',
                                    address_2: shippingAddress.line2 || '',
                                    city: shippingAddress.city || '',
                                    state: shippingAddress.state || '',
                                    postcode: shippingAddress.postalCode || '',
                                    country: shippingAddress.countryCode || ''
                                }
                                if (isEmpty(paymentData.current.billingAddress)) {
                                    paymentData.current.billingAddress = paymentData.current.shippingAddress;
                                }
                                if (response.details?.phone) {
                                    paymentData.current.shippingAddress.phone = response.details.phone;
                                }
                            }
                            if (response.details?.phone) {
                                paymentData.current.billingAddress.phone = response.details.phone;
                            }
                            if (response.details?.email) {
                                paymentData.current.billingAddress.email = response.details.email;
                            }
                            if (response.details?.firstName) {
                                paymentData.current.billingAddress.first_name = response.details.firstName;
                            }
                            if (response.details?.lastName) {
                                paymentData.current.billingAddress.last_name = response.details.lastName;
                            }
                            if (!paymentData.current.billingAddress.address_1 && !billingAddress.address_1) {
                                paymentData.current.incompleteBilling = true;
                            }
                            if (cartContainsSubscription() && needsShipping) {
                                currentData.current.addNotice(__('Please select a shipping option then click Place Order to complete your payment.', 'woo-payment-gateway'), 'info');
                            } else {
                                setPaymentMethodNonce(response.nonce);
                            }
                        });
                    },
                    createOrder: () => {
                        return paypalCheckoutInstance.createPayment({
                            flow: cartContainsSubscription() ? 'vault' : 'checkout',
                            intent: getData('intent'),
                            currency: currency.code,
                            displayName: getData('displayName'),
                            amount: removeNumberPrecision(cartTotal.value, currency.minorUnit),
                            enableShippingAddress: needsShipping,
                            shippingAddressEditable: needsShipping
                        }).then(id => {
                            return id;
                        }).catch(error => {
                            console.log(error);
                        })
                    },
                    onCancel: () => {
                        currentData.current.onClose();
                    }
                }
                if (cartContainsSubscription()) {
                    option.createBillingAgreement = option.createOrder;
                    delete (option.createOrder);
                }
                // can't update shipping options when using billing agreements.
                if (needsShipping && !option.hasOwnProperty('createBillingAgreement')) {
                    option.onShippingChange = (data, actions) => {
                        const {shipping_address: address, selected_shipping_option} = data;
                        const selectedShippingOption = selected_shipping_option?.id;
                        const shippingData = currentData.current.shippingData;
                        const {shippingAddress} = shippingData;
                        const {city, state, postcode, country} = shippingAddress;
                        const shippingOptionId = getSelectedShippingOptionId(currentData.current.shippingData.shippingRates);
                        const newAddress = {
                            city: address.city || '',
                            state: address.state || '',
                            postcode: address.postal_code || '',
                            country: address.country_code || ''
                        }
                        const isShippingOptionEqual = selectedShippingOption == null || selectedShippingOption == shippingOptionId;
                        const isAddressEqual = isEqual({
                            city, state, postcode, country
                        }, newAddress);
                        const shippingDataEqual = isAddressEqual && isShippingOptionEqual;
                        if (selectedShippingOption) {
                            shippingData.setSelectedRates(...extractSelectedShippingOption(selectedShippingOption));
                        }
                        shippingData.setShippingAddress({...shippingAddress, ...newAddress});
                        return new Promise(resolve => {
                            addShippingHandler(onShippingChange(actions, selected_shipping_option?.id, shippingDataEqual, resolve), shippingDataEqual);
                        });
                    }
                }
                let button = paypal.Buttons(option);
                if (button.isEligible()) {
                    options.push(option);
                }
            }
            return options;
        }
    }, [
        paypal,
        paypalCheckoutInstance,
        needsShipping,
        onShippingChange,
        addShippingHandler,
        setPaymentMethodNonce
    ]);
    return options;
}

export default usePayPalOptions;