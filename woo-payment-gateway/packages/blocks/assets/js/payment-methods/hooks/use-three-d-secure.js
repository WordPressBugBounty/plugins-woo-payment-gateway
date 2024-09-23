import {useState, useEffect, useCallback} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {create} from '@braintree/three-d-secure';
import {removeNumberPrecision} from "../utils";
import {usePaymentMethodDataContext} from "../context";
import {getSettings} from '../utils';

export const useThreeDSecure = ({name, vaulted = false}) => {
    const settings = getSettings(name);
    const {client, notice, onPaymentDataFilter, threeDSecureEnabled} = usePaymentMethodDataContext();
    const {addNotice} = notice;
    const [instance, setInstance] = useState(null);
    useEffect(() => {
        if ((threeDSecureEnabled || vaulted) && client && !instance) {
            try {
                create({
                    version: 2,
                    client
                }, (error, instance) => {
                    if (!error) {
                        setInstance(instance);
                    } else {
                        addNotice(error);
                    }
                });
            } catch (error) {
                console.log(error);
            }
        }
    }, [
        instance,
        threeDSecureEnabled,
        vaulted,
        client,
        addNotice
    ]);

    useEffect(() => {
        if (instance) {
            const unsubscribe = onPaymentDataFilter((data, {result, name, shippingData, billing}) => {
                return new Promise((resolve, reject) => {
                    const {needsShipping, shippingAddress} = shippingData;
                    const {billingAddress, cartTotal, currency} = billing;
                    instance.verifyCard({
                        amount: removeNumberPrecision(cartTotal.value, currency.minorUnit),
                        nonce: result.nonce,
                        bin: result?.details?.bin,
                        email: billingAddress.email || '',
                        challengeRequested: settings('challengeRequested'),
                        collectDeviceData: true,
                        billingAddress: {
                            givenName: billingAddress.first_name,
                            surname: billingAddress.last_name,
                            phoneNumber: billingAddress.phone,
                            streetAddress: billingAddress.address_1?.slice(0, 50),
                            extendedAddress: billingAddress.address_2?.slice(0, 50),
                            locality: billingAddress.city?.slice(0, 50),
                            region: billingAddress.state,
                            postalCode: billingAddress.postcode,
                            countryCodeAlpha2: billingAddress.country
                        },
                        additionalInformation: needsShipping ? {
                            shippingGivenName: shippingAddress.first_name,
                            shippingSurname: shippingAddress.last_name,
                            shippingAddress: {
                                streetAddress: shippingAddress.address_1?.slice(0, 50),
                                extendedAddress: shippingAddress.address_2?.slice(0, 50),
                                locality: shippingAddress.city?.slice(0, 50),
                                region: shippingAddress.state,
                                postalCode: shippingAddress.postcode,
                                countryCodeAlpha2: shippingAddress.country
                            },
                            ipAddress: settings('ipAddress')
                        } : {
                            ipAddress: settings('ipAddress')
                        },
                        onLookupComplete: (data, next) => next()
                    }, (error, payload) => {
                        if (error) {
                            reject(error);
                        } else if (payload?.threeDSecureInfo?.status === 'challenge_required') {
                            // 3DS was cancelled so teardown and set to null.
                            instance.teardown().then(() => setInstance(null));
                            reject({
                                message: __('3DS authorization cancelled.', 'woo-payment-gateway')
                            });
                        } else {
                            data.meta.paymentMethodData[`${name}_nonce_key`] = payload.nonce;
                            resolve(data);
                        }
                    });
                })
            }, 20);
            return () => unsubscribe();
        }
    }, [instance]);

    return instance;
}

export default useThreeDSecure;