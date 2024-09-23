import {useState, useRef, useEffect, useCallback, createContext, useContext, useReducer} from '@wordpress/element';
import {actionReducer, useActionEmitter, emittFilter} from "../context";
import {loadClient} from "../utils";
import {useNoticeHandler, useProcessPayment, useProcessPaymentFailure} from "../hooks";

const PaymentMethodDataContext = createContext();
export const PaymentMethodDataProvider = (
    {
        children,
        container,
        onSubmit,
        getData,
        isExpress = null,
        setExpressPaymentError
    }) => {
    const [client, setClient] = useState();
    const [paymentMethodNonce, setPaymentMethodNonce] = useState(null);
    const [eventHandlers, dispatch] = useReducer(actionReducer, {});
    const {onPaymentDataFilter} = useActionEmitter(dispatch);
    const currentEventHandlers = useRef(eventHandlers);
    const currentData = useRef({onSubmit});
    const paymentHandler = useRef(null);
    const [notice, addNotice, removeNotice] = useNoticeHandler({isExpress, setExpressPaymentError});

    const doPaymentDataFilter = useCallback(async (data, args) => {
        return await emittFilter(data, args, {
            handlers: currentEventHandlers.current,
            type: 'payment_data_filter'
        });
    }, []);

    const setPaymentHandler = useCallback((handler) => {
        paymentHandler.current = handler;
    }, []);
    useEffect(() => {
        currentEventHandlers.current = eventHandlers;
    }, [eventHandlers]);

    useEffect(() => {
        loadClient.then(client => setClient(client)).catch(error => {
            addNotice(error)
        });
    }, [addNotice]);

    useEffect(() => {
        currentData.current = {onSubmit};
    }, [onSubmit]);

    useEffect(() => {
        if (paymentMethodNonce) {
            currentData.current.onSubmit();
        }
    }, [paymentMethodNonce]);

    const paymentMethodDataContext = {
        container,
        client,
        paymentMethodNonce,
        setPaymentMethodNonce,
        onPaymentDataFilter,
        doPaymentDataFilter,
        setPaymentHandler,
        onSubmit,
        paymentHandler: paymentHandler.current,
        notice: {
            notice,
            addNotice,
            removeNotice
        },
        fraud: {
            enabled: getData('advancedFraudEnabled')
        },
        threeDSecureEnabled: getData('threeDSecureEnabled')
    };
    return (
        <PaymentMethodDataContext.Provider value={paymentMethodDataContext}>
            {children}
        </PaymentMethodDataContext.Provider>
    )
}

export const usePaymentMethodDataContext = () => {
    return useContext(PaymentMethodDataContext);
}