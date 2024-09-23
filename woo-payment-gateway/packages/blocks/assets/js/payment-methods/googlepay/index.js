import {useState, useEffect, useRef, useCallback} from '@wordpress/element';
import {create as createGooglePay} from '@braintree/google-payment';
import {registerExpressPaymentMethod} from '@woocommerce/blocks-registry';
import {PaymentMethod} from "../../components";
import {getSettings, getClientToken, isCartPage, isCheckoutPage} from "../utils";
import google from '@googlepay';
import {useCreatePaymentsClient, useHandleTokenization} from "./hooks";
import {usePaymentMethodDataContext} from "../context";
import {useExpressBreakpointWidth} from "../hooks";

const getData = getSettings('braintree_googlepay');
let canPay = null;
const paymentsClient = new google.payments.api.PaymentsClient({
    environment: getData('googleEnvironment'),
    merchantInfo: {
        merchantName: getData('googleMerchantName'),
        merchantId: getData('googleMerchantId')
    }
});
const GooglePayContainer = ({getData, billing, shippingData, eventRegistration, onClick, onClose}) => {
    const el = useRef(null);
    const [googlePayInstance, setGooglePayInstance] = useState(null);
    useExpressBreakpointWidth({breakpoint: 375});
    const {notice} = usePaymentMethodDataContext();
    const paymentsClient = useCreatePaymentsClient({getData, billing, shippingData, eventRegistration});
    const handleClick = useHandleTokenization({
        getData,
        onClick,
        onClose,
        googlePayInstance,
        paymentsClient,
        billing,
        shippingData
    });

    const removeButton = useCallback((el) => {
        while (el.lastChild) {
            el.removeChild(el.lastChild);
        }
    }, []);

    useEffect(() => {
        createGooglePay({
            authorization: getClientToken(),
            googlePayVersion: 2,
            googleMerchantId: getData('googleMerchantId')
        }).then(instance => {
            setGooglePayInstance(instance);
        }).catch(error => notice.addNotice(error));
    }, []);
    useEffect(() => {
        if (el.current && paymentsClient) {
            removeButton(el.current);
            const button = paymentsClient.createButton({
                ...getData('buttonOptions'), ...{
                    onClick: handleClick
                }
            });
            if (getData('buttonShape') === 'rect') {
                button.querySelector('button')?.classList?.remove('new_style');
            } else {
                button.querySelector('button')?.classList?.add('gpay-button-round');
            }
            el.current.append(button);
        }
    }, [
        paymentsClient,
        removeButton,
        handleClick
    ]);
    return (
        <div ref={el} className={'wc-braintree-blocks-gpay-button'}>

        </div>
    );
}
if ((isCartPage() && getData('expressCartEnabled')) || (isCheckoutPage() && getData('expressCheckoutEnabled'))) {
    registerExpressPaymentMethod({
        name: getData('name'),
        canMakePayment: async () => {
            if (canPay !== null) {
                return canPay;
            }
            try {
                const googlePayInstance = await createGooglePay({
                    authorization: getClientToken(),
                    googlePayVersion: 2,
                    googleMerchantId: getData('googleMerchantId')
                });
                let result = await paymentsClient.isReadyToPay({
                    apiVersion: 2,
                    apiVersionMinor: 0,
                    allowedPaymentMethods: googlePayInstance.createPaymentDataRequest().allowedPaymentMethods
                });
                if (result.result) {
                    canPay = true;
                } else {
                    canPay = false;
                }
                return canPay;
            } catch (error) {
                return error;
            }
        },
        content: <PaymentMethod content={GooglePayContainer}
                                title={getData('title')}
                                description={getData('description')}
                                name={getData('name')}
                                icon={getData('icon')}
                                getData={getData}
                                isExpress={true}/>,
        edit: <PaymentMethod content={GooglePayContainer} getData={getData}/>,
        supports: {
            showSavedCards: getData('features').includes('tokenization'),
            showSaveOption: true,
            features: getData('features')
        }
    });
}
