import {useState, useEffect} from '@wordpress/element';
import {create} from '@braintree/data-collector';
import {usePaymentMethodDataContext} from "../context";

export const useDeviceData = (options = {}) => {
    const {client, notice, onPaymentDataFilter, fraud} = usePaymentMethodDataContext();
    const enabled = fraud.enabled;
    const {addNotice} = notice;
    const [dataCollector, setDataCollector] = useState(null);
    useEffect(() => {
        if (enabled && client) {
            create({
                client,
                kount: true, ...options
            }, (error, instance) => {
                if (error) {
                    addNotice(error);
                } else {
                    setDataCollector(instance);
                }
            });
        }
    }, [enabled, client, addNotice]);

    useEffect(() => {
        const unsubscribe = onPaymentDataFilter((data, {name}) => {
            data.meta.paymentMethodData[`${name}_device_data`] = dataCollector?.deviceData;
            return data;
        }, 10);
        return () => unsubscribe();
    }, [dataCollector, onPaymentDataFilter]);
    return dataCollector;
}

export default useDeviceData;