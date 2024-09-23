import {useEffect, useRef, useCallback} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {range} from 'lodash';
import {usePaymentMethodDataContext} from "../../context";
import {usePaymentEventsHandler} from "../../hooks";
import apiFetch from '@wordpress/api-fetch';
import {isEqual, isEmpty} from 'lodash';
import {
    extractSelectedShippingOption,
    extractAddressLines,
    DEFAULT_BILLING_ADDRESS,
    DEFAULT_SHIPPING_ADDRESS
} from "../../utils";

export const useCreateSession = (
    {
        applePayInstance,
        billing,
        shippingData,
        paymentRequest,
        eventRegistration,
        onClick,
        onClose,
        getData
    }) => {
    const {addShippingHandler} = usePaymentEventsHandler({
        billing,
        shippingData,
        eventRegistration
    });
    const {setPaymentMethodNonce, onPaymentDataFilter, notice: {addNotice}} = usePaymentMethodDataContext();
    const applePayData = useRef({});
    const currentData = useRef({
        billing,
        shippingData,
        onClick,
        onClose,
        addNotice,
        addShippingHandler,
        setPaymentMethodNonce,
        onPaymentDataFilter
    });

    const getApplePayVersion = useCallback(() => {
        // always check support from highest version first
        for (let version of range(10, 2, -1)) {
            if (ApplePaySession.supportsVersion(version)) {
                return version;
            }
        }
        return 3;
    }, []);

    const onSessionCancel = useCallback(() => {
        const {onClose} = currentData.current;
        onClose();
    }, []);

    const onValidateMerchant = useCallback((event) => {
        const {session} = currentData.current;
        applePayInstance.performValidation({
            validationURL: event.validationURL,
            displayName: getData('displayName')
        }).then(merchantSession => {
            session.completeMerchantValidation(merchantSession);
        }).catch(error => {
            session.abort();
            addNotice(error);
        })
    }, [applePayInstance, addNotice]);

    const onPaymentMethodSelected = useCallback((event) => {
        // update the cart billing address info
        const {paymentMethod} = event;
        const {session, addNotice} = currentData.current;
        let address = null;
        if (paymentMethod.billingContact) {
            address = {
                country: paymentMethod.billingContact?.countryCode || '',
                state: paymentMethod.billingContact?.administrativeArea || '',
                postcode: paymentMethod.billingContact?.postalCode || '',
                city: paymentMethod.billingContact?.locality || ''
            }
        }
        apiFetch({
            method: 'POST',
            url: getData('routes').payment_method,
            data: {
                address
            }
        }).then(response => {
            if (response.code) {
                session.abort();
                addNotice(response.messages);
            } else {
                session.completePaymentMethodSelection(response.data);
            }
        }).catch((xhr) => {

        });
    }, []);

    const onShippingContactSelected = useCallback((event) => {
        const {session} = currentData.current;
        const {shippingContact} = event;
        const {shippingData} = currentData.current;
        const {shippingAddress} = shippingData;
        const {country, state, city, postcode} = shippingAddress;
        const newAddress = {
            country: shippingContact?.countryCode.toUpperCase(),
            state: shippingContact?.administrativeArea.toUpperCase(),
            city: shippingContact.locality,
            postcode: shippingContact.postalCode
        }
        //const addressEqual = isEqual({country, state, city, postcode}, newAddress);

        apiFetch({
            method: 'POST',
            url: getData('routes').shipping_address,
            data: {
                payment_method: 'braintree_applepay',
                address: newAddress
            }
        }).then(response => {
            if (response.code) {
                if (response.code === 'addressUnserviceable') {
                    session.completeShippingContactSelection(response.data.shippingContactUpdate);
                } else {
                    session.completeShippingContactSelection({
                        errors: [new ApplePayError(
                            response.code,
                            response.data.contactField,
                            response.message)],
                        newTotal: response.data.newTotal,
                        newShippingMethods: response.data.newShippingMethods
                    });
                }
            } else {
                session.completeShippingContactSelection(response.data.shippingContactUpdate)
            }
        }).finally(() => {
            shippingData.setShippingAddress({...shippingAddress, ...newAddress});
        });
    }, []);

    const onShippingMethodSelected = useCallback((event) => {
        const {shippingMethod} = event;
        const {session, shippingData, addNotice} = currentData.current;
        const {setSelectedRates, selectedRates} = shippingData;

        apiFetch({
            method: 'POST',
            url: getData('routes').shipping_method,
            data: {
                payment_method: 'braintree_applepay',
                shipping_method: shippingMethod.identifier
            }
        }).then(response => {
            if (response.code) {
                session.abort();
                //__('There was an error updating your cart totals.', 'woo-payment-gateway')
            } else {
                session.completeShippingMethodSelection(response.data.shippingMethodUpdate);
            }
        }).finally(() => {
            setSelectedRates(...extractSelectedShippingOption(shippingMethod.identifier));
        });
    }, []);

    const onPaymentAuthorized = useCallback((event) => {
        const {session, setPaymentMethodNonce, errors = []} = currentData.current;
        if (errors?.length > 0) {
            session.completePayment({
                status: ApplePaySession.STATUS_FAILURE,
                errors
            });
        } else {
            applePayInstance.tokenize({
                token: event?.payment?.token
            }).then(response => {
                const {shippingContact = null, billingContact = null} = event.payment;
                applePayData.current.billingAddress = {};

                if (billingContact) {
                    applePayData.current.billingAddress = {
                        first_name: billingContact?.givenName,
                        last_name: billingContact?.familyName,
                        city: billingContact?.locality,
                        state: billingContact?.administrativeArea,
                        postcode: billingContact?.postalCode,
                        country: billingContact?.countryCode,
                        ...extractAddressLines(billingContact?.addressLines || ''),
                        ...applePayData.current.billingAddress
                    }
                    if (billingContact.emailAddress) {
                        applePayData.current.billingAddress.email = billingContact.emailAddress;
                    }
                    if (billingContact.phoneNumber) {
                        applePayData.current.billingAddress.phone = billingContact.phoneNumber;
                    }
                }

                if (shippingContact) {
                    if (currentData.current.shippingData.needsShipping) {
                        applePayData.current.shippingAddress = {
                            first_name: shippingContact?.givenName,
                            last_name: shippingContact?.familyName,
                            city: shippingContact?.locality,
                            state: shippingContact?.administrativeArea,
                            postcode: shippingContact?.postalCode,
                            country: shippingContact?.countryCode,
                            ...extractAddressLines(shippingContact?.addressLines || [])
                        }
                        if (applePayData.current.billingAddress.phone) {
                            applePayData.current.shippingAddress.phone = applePayData.current.billingAddress.phone;
                        } else if (shippingContact?.phoneNumber) {
                            applePayData.current.shippingAddress.phone = shippingContact.phoneNumber;
                        }
                    }
                    if (shippingContact?.phoneNumber && !applePayData.current.billingAddress.phone) {
                        applePayData.current.billingAddress.phone = shippingContact?.phoneNumber;
                    }
                    if (shippingContact?.emailAddress && !applePayData.current.billingAddress.email) {
                        applePayData.current.billingAddress.email = shippingContact?.emailAddress;
                    }
                }

                setPaymentMethodNonce(response.nonce);
                session.completePayment(ApplePaySession.STATUS_SUCCESS);
            }).catch(error => {
                session.completePayment(ApplePaySession.STATUS_FAILURE);
            })
        }
    }, [applePayInstance]);

    useEffect(() => {
        currentData.current = {
            ...currentData.current,
            billing,
            shippingData,
            onClick,
            onClose,
            addNotice,
            addShippingHandler,
            setPaymentMethodNonce
        };
    });

    useEffect(() => {
        const unsubscribe = onPaymentDataFilter((data, {billing, shippingData}) => {
            if (!isEmpty(applePayData.current?.billingAddress)) {
                data.meta.billingAddress = {
                    ...DEFAULT_BILLING_ADDRESS,
                    ...billing.billingAddress,
                    ...applePayData.current.billingAddress
                };
            }
            if (!isEmpty(applePayData.current?.shippingAddress)) {
                data.meta.shippingAddress = {
                    ...DEFAULT_SHIPPING_ADDRESS,
                    ...shippingData.shippingAddress,
                    ...applePayData.current.shippingAddress
                };
            }
            return data;
        });
        return () => unsubscribe();
    }, [onPaymentDataFilter]);

    const createSession = useCallback(() => {
        const {shippingData} = currentData.current;
        const session = new ApplePaySession(getApplePayVersion(), paymentRequest);
        session.onvalidatemerchant = onValidateMerchant;
        session.onpaymentmethodselected = onPaymentMethodSelected;
        session.onpaymentauthorized = onPaymentAuthorized;
        session.oncancel = onSessionCancel;
        if (shippingData.needsShipping) {
            session.onshippingcontactselected = onShippingContactSelected;
            session.onshippingmethodselected = onShippingMethodSelected;
        }
        currentData.current.session = session;
        return session;
    }, [
        applePayInstance,
        paymentRequest,
        getApplePayVersion,
        onValidateMerchant,
        onPaymentMethodSelected,
        onPaymentAuthorized,
        onShippingContactSelected,
        onShippingMethodSelected
    ]);

    const handleClick = useCallback((e) => {
        e.preventDefault();
        currentData.current.onClick();
        try {
            const session = createSession();
            session.begin();
        } catch (error) {
            alert(error);
        }
    }, [createSession]);

    return {handleClick};
}

export default useCreateSession;