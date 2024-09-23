import {useEffect, useMemo, useRef, useCallback} from '@wordpress/element';
import {removeNumberPrecision} from "../../utils";
import {formatLineItems, formatShippingMethods} from "../utils";

export const useCreatePaymentRequest = (
    {
        applePayInstance,
        billing,
        shippingData,
        getData
    }) => {
    const currentData = useRef({});

    useEffect(() => {
        currentData.current.billing = billing;
        currentData.current.shippingData = shippingData;
    }, [billing, shippingData]);

    const request = useMemo(() => {
        if (applePayInstance) {
            const {cartTotal, currency, cartTotalItems, billingAddress} = currentData.current.billing;
            const {needsShipping, shippingRates} = currentData.current.shippingData;
            const request = {
                total: {
                    label: getData('displayName'),
                    type: 'final',
                    amount: removeNumberPrecision(cartTotal.value, currency.minorUnit).toString()
                },
                lineItems: formatLineItems(cartTotalItems, currency.minorUnit),
                currencyCode: currency.code,
                countryCode: getData('countryCode'),
                requiredBillingContactFields: ['postalAddress'],
                requiredShippingContactFields: (() => {
                    let fields = needsShipping ? ['postalAddress'] : [];
                    if (!billingAddress.email) {
                        fields = [...fields, 'email'];
                    }
                    if (!billingAddress.phone) {
                        fields = [...fields, 'phone'];
                    }
                    return fields;
                })()
            }
            if (needsShipping) {
                request.shippingMethods = formatShippingMethods(shippingRates);
            }
            const paymentRequest = applePayInstance.createPaymentRequest(request);
            return paymentRequest;
        }
        return null;
    }, [applePayInstance]);
    return request;
}

export default useCreatePaymentRequest;