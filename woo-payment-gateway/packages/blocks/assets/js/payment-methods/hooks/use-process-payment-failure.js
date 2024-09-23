import {useEffect} from '@wordpress/element';

export const useProcessPaymentFailure = ({event, responseTypes, messageContext = null}) => {
    useEffect(() => {
        const unsubscribe = event((data) => {
            if (data?.processingResponse?.paymentDetails?.braintreeErrorMessage) {
                const message = data.processingResponse.paymentDetails.braintreeErrorMessage;
                return {
                    type: responseTypes.ERROR,
                    message,
                    messageContext
                }
            }
            return null;
        });
        return () => unsubscribe();
    }, [event]);
}

export default useProcessPaymentFailure;