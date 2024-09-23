import {useEffect, useCallback, useRef} from '@wordpress/element';
import {usePaymentMethodDataContext} from "../../context";

export const useHandleTokenization = ({venmoInstance, onSubmit}) => {
    const paymentData = useRef(null);
    const {onPaymentDataFilter, paymentMethodNonce, setPaymentMethodNonce, notice: {addNotice}} = usePaymentMethodDataContext();
    const handleClick = useCallback(async () => {
        try {
            if (paymentMethodNonce) {
                onSubmit();
            } else {
                const result = await venmoInstance.tokenize();
                paymentData.current = {nonce: result.nonce};
                setPaymentMethodNonce(result.nonce);
            }
        } catch (error) {
            addNotice(error);
        }
    }, [
        venmoInstance,
        onSubmit,
        paymentMethodNonce
    ]);

    useEffect(() => {
        const unsubscribe = onPaymentDataFilter((data, {name}) => {
            data.meta.paymentMethodData[`${name}_nonce_key`] = paymentData.current.nonce;
            return data;
        }, 5);
        return () => unsubscribe();
    }, [onPaymentDataFilter]);

    return {handleClick}
}

export default useHandleTokenization;