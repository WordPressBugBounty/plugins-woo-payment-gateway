import {useEffect, useRef, useCallback} from '@wordpress/element';
import {ensureSuccessResponse, ensureErrorResponse, BraintreeError} from "../../payment-methods/utils";
import {useThreeDSecure, useProcessPaymentFailure, useDeviceData} from "../../payment-methods/hooks";
import apiFetch from '@wordpress/api-fetch';
import {usePaymentMethodDataContext, PaymentMethodDataProvider} from "../../payment-methods/context";

export const SavedTokenProvider = (props) => {
    return (
        <PaymentMethodDataProvider {...{...props}}>
            <SavedTokenComponent {...{...props}}/>
        </PaymentMethodDataProvider>
    )
}
export const SavedTokenComponent = (
    {
        name,
        vaultedThreeDSecure = false,
        token,
        eventRegistration,
        billing,
        shippingData,
        emitResponse
    }) => {
    const {responseTypes} = emitResponse;
    const {onPaymentSetup, onCheckoutFail} = eventRegistration;
    const currentData = useRef({billing, shippingData});
    const {doPaymentDataFilter} = usePaymentMethodDataContext();
    useDeviceData();
    useThreeDSecure({name, vaulted: vaultedThreeDSecure});
    useProcessPaymentFailure({
        event: onCheckoutFail,
        responseTypes
    });

    useEffect(() => {
        currentData.current = {name, billing, shippingData};
    });

    const fetchTokenNonce = useCallback(async (token_id) => {
        try {
            let result = await apiFetch({
                path: '/wc-braintree/v1/3ds/vaulted_nonce',
                method: 'POST',
                data: {token_id, version: 2}
            });
            return result;
        } catch (error) {

        }
    }, []);

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            if (vaultedThreeDSecure) {
                try {
                    let result = await fetchTokenNonce(token);
                    if (!result.success) {
                        throw new BraintreeError({message: result.message});
                    }
                    result = await doPaymentDataFilter({
                        meta: {
                            paymentMethodData: {
                                [`${name}_3ds_nonce_key`]: 'true'
                            }
                        }
                    }, {...currentData.current, ...{result: result.data}});
                    return ensureSuccessResponse(responseTypes, result);
                } catch (error) {
                    return ensureErrorResponse(responseTypes, error);
                }
            }
            return null;
        });
        return () => unsubscribe();
    }, [
        onPaymentSetup,
        vaultedThreeDSecure,
        token,
        name,
        responseTypes
    ]);
    return null;
}

export default SavedTokenComponent;