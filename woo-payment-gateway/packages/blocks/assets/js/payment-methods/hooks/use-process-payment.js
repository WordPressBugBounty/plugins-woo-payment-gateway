import {useEffect, useRef} from '@wordpress/element';
import {ensureErrorResponse, ensureSuccessResponse} from "../utils";
import {useThreeDSecure} from "./use-three-d-secure";
import {useDeviceData} from "./use-device-data";
import {usePaymentMethodDataContext} from '../context';

export const useProcessPayment = (
    {
        onPaymentSetup,
        responseTypes,
        name,
        shippingData,
        billing,
        activePaymentMethod,
        advancedFraudOptions = {}
    }) => {
    const currentData = useRef({shippingData, billing, name, activePaymentMethod});
    const {paymentMethodNonce, paymentHandler, doPaymentDataFilter} = usePaymentMethodDataContext();
    useThreeDSecure({name});
    useDeviceData(advancedFraudOptions);

    useEffect(() => {
        currentData.current = {shippingData, billing, name, activePaymentMethod, paymentMethodNonce};
    });

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            if (currentData.current.activePaymentMethod == currentData.current.name) {
                try {
                    const nonce = currentData.current?.paymentMethodNonce || '';
                    let data = {meta: {paymentMethodData: {[`${name}_nonce_key`]: nonce}}}, result;
                    if (paymentHandler) {
                        result = await Promise.resolve(paymentHandler(currentData.current));
                        if (result?.nonce) {
                            data.meta.paymentMethodData[`${name}_nonce_key`] = result.nonce;
                        }
                    }
                    result = await doPaymentDataFilter(data, {...currentData.current, ...{result}});
                    return ensureSuccessResponse(responseTypes, result);
                } catch (error) {
                    return ensureErrorResponse(responseTypes, error);
                }
            }
        });
        return () => unsubscribe();
    }, [
        onPaymentSetup,
        doPaymentDataFilter,
        paymentHandler
    ]);
}

export default useProcessPayment;