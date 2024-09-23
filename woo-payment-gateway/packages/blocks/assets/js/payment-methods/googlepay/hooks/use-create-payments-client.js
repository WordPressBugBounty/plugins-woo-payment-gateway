import {useState, useEffect, useRef} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import google from '@googlepay';
import {usePaymentEventsHandler} from "../../hooks";
import {extractSelectedShippingOption} from "../../utils";

export const useCreatePaymentsClient = ({getData, shippingData, billing, eventRegistration}) => {
    const {needsShipping} = shippingData;
    const [paymentsClient, setPaymentsClient] = useState(null);
    const {addShippingHandler} = usePaymentEventsHandler({billing, shippingData, eventRegistration});
    const currentData = useRef({billing, shippingData});
    useEffect(() => {
        currentData.current = {billing, shippingData};
    }, [billing, shippingData]);
    useEffect(() => {
        const args = {
            environment: getData('googleEnvironment'),
            merchantInfo: {
                merchantName: getData('merchantName'),
                merchantId: getData('googleMerchantId')
            },
            paymentDataCallbacks: {
                onPaymentAuthorized: () => Promise.resolve({
                    transactionState: "SUCCESS"
                })
            }
        }
        if (needsShipping) {
            args.paymentDataCallbacks.onPaymentDataChanged = (data) => {
                return new Promise((resolve, reject) => {
                    const {shippingAddress, shippingOptionData} = data;
                    const {shippingData} = currentData.current;
                    let shippingOptionId = shippingOptionData?.id;
                    const newAddress = {
                        city: shippingAddress?.locality || '',
                        state: shippingAddress?.administrativeArea || '',
                        postcode: shippingAddress?.postalCode || '',
                        country: shippingAddress?.countryCode || ''
                    };

                    apiFetch({
                        method: 'POST',
                        url: getData('routes').shipping,
                        data: {
                            payment_method: 'braintree_googlepay',
                            address: newAddress,
                            shipping_method: shippingOptionId
                        }
                    }).then(response => {
                        if (response.code) {
                            shippingOptionId = null;
                            resolve(response.data.braintree_googlepay);
                        } else {
                            resolve(response.data.braintree_googlepay.requestUpdate);
                        }
                    }).catch(response => {
                        resolve(response.data.braintree_googlepay);
                    }).finally(() => {
                        shippingData.setShippingAddress({...shippingData.shippingAddress, ...newAddress});
                        if (shippingOptionId && shippingOptionId !== 'shipping_option_unselected') {
                            shippingData.setSelectedRates(...extractSelectedShippingOption(shippingOptionId));
                        }
                    });
                });
            }
        }
        setPaymentsClient(new google.payments.api.PaymentsClient(args));
    }, [
        needsShipping,
        addShippingHandler
    ]);
    return paymentsClient;
}