import {useEffect, useRef, useCallback, useMemo} from '@wordpress/element';

export const usePaymentEventsHandler = (
    {
        billing,
        shippingData,
        eventRegistration
    }) => {
    const currentData = useRef({billing, shippingData});
    const {onShippingRateSuccess, onShippingRateFail, onShippingRateSelectSuccess} = eventRegistration;
    const handlers = useRef(new Map([
        ['shippingHandler', []],
        ['shippingMethodHandler', []],
        ['shippingAddressHandler', []]
    ]));
    useEffect(() => {
        currentData.current = {billing, shippingData};
    });

    const hasShippingOptions = (shippingRates) => shippingRates.map(rate => rate?.shipping_rates.length > 0).filter(Boolean).length > 0;

    const handleShippingChange = useCallback(async () => {
        const {billing, shippingData} = currentData.current;
        const handler = handlers.current.get('shippingHandler');
        if (handler.length > 0 && !shippingData.isSelectingRate && !shippingData.shippingRatesLoading) {
            const success = hasShippingOptions(shippingData.shippingRates);
            while (handler.length) {
                const callback = handler.pop();
                await Promise.resolve(callback(success, {billing, shippingData}));
            }
        }
    }, []);

    const handleShippingRateFail = useCallback(() => {
        const {billing, shippingData} = currentData.current;
        const handler = handlers.current.get('shippingHandler');
        while (handler.length) {
            const callback = handler.pop();
            callback(false, {billing, shippingData});
        }
    }, []);

    const addPaymentEventHandler = useCallback(type => (callback, execute = false) => {
        const handler = handlers.current.get(type);
        handler.push(callback);
        if (execute) {
            handleShippingChange();
        }
    }, [
        handleShippingChange,
        handlers.current
    ]);

    const addShippingHandler = useCallback(addPaymentEventHandler('shippingHandler'), [addPaymentEventHandler]);
    const addShippingMethodHandler = useCallback(addPaymentEventHandler('shippingMethodHandler'), [addPaymentEventHandler]);
    const addShippingAddressHandler = useCallback(addPaymentEventHandler('shippingAddressHandler'), [addPaymentEventHandler]);

    useEffect(() => {
        const unsubscribeShippingRateSuccess = onShippingRateSuccess(handleShippingChange);
        const unsubscribeShippingRateSelectSuccess = onShippingRateSelectSuccess(handleShippingChange);
        const unsubscribeShippingRateFail = onShippingRateFail(handleShippingRateFail);
        return () => {
            unsubscribeShippingRateSuccess();
            unsubscribeShippingRateSelectSuccess();
            unsubscribeShippingRateFail();
        }
    }, [
        onShippingRateSuccess,
        onShippingRateSelectSuccess,
        onShippingRateFail
    ]);

    return {
        addShippingHandler,
        addShippingMethodHandler,
        addShippingAddressHandler
    }
}