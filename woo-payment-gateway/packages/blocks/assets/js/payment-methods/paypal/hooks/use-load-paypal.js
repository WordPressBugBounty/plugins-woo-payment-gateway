import {useState, useEffect, useRef} from '@wordpress/element';

export const useLoadPayPal = (
    {
        paypalCheckoutInstance,
        currency,
        addNotice,
        clientToken,
        intent,
        flow,
        partnerCode
    }) => {
    const [paypal, setPayPal] = useState(null);
    const currentData = useRef({});
    useEffect(() => {
        currentData.current.addNotice = addNotice;
    }, [
        addNotice
    ]);
    useEffect(() => {
        if (paypalCheckoutInstance) {
            if (window.paypal) {
                setPayPal(window.paypal)
            } else {
                clientToken = JSON.parse(window.atob(clientToken));
                const args = {
                    'client-id': clientToken?.paypal?.clientId,
                    commit: true,
                    intent: flow === 'vault' ? 'tokenize' : intent,
                    currency,
                    vault: flow === 'vault' ? true : false,
                    components: 'buttons,messages',
                    dataAttributes: {
                        'partner-attribution-id': partnerCode
                    }
                };
                paypalCheckoutInstance.loadPayPalSDK(args).then(() => {
                    setPayPal(window.paypal)
                }).catch(error => {
                    currentData.current.addNotice(error);
                });
            }
        }
    }, [paypalCheckoutInstance]);
    return paypal;
}

export default useLoadPayPal;