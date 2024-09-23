import {useState, useEffect} from '@wordpress/element';
import {registerExpressPaymentMethod} from '@woocommerce/blocks-registry';
import {create as createApplePay} from '@braintree/apple-pay';
import {getSettings, getClientToken, isCartPage, isCheckoutPage} from "../utils";
import {PaymentMethod} from "../../components/payment-method";
import {usePaymentMethodDataContext} from "../context";
import {useCreatePaymentRequest, useCreateSession} from "./hooks";
import ErrorBoundary from "../../components/error-boundary";

const getData = getSettings('braintree_applepay');

const loadApplePay = new Promise((resolve, reject) => {
    createApplePay({
        authorization: getClientToken()
    }).then(instance => resolve(instance)).catch(error => reject(error));
});

const Test = (props) => {
    return (
        <ErrorBoundary>
            <ApplePayContainer {...props}/>
        </ErrorBoundary>
    )
}

const ApplePayContainer = (
    {
        getData,
        billing,
        shippingData,
        eventRegistration,
        onClick,
        onClose
    }) => {
    const [applePayInstance, setApplePayInstance] = useState(null);
    const {notice: {addNotice}} = usePaymentMethodDataContext();
    const paymentRequest = useCreatePaymentRequest({
        applePayInstance,
        billing,
        shippingData,
        getData
    });
    const {handleClick} = useCreateSession({
        applePayInstance,
        billing,
        shippingData,
        paymentRequest,
        eventRegistration,
        onClick,
        onClose,
        getData
    });
    useEffect(() => {
        loadApplePay.then(instance => setApplePayInstance(instance)).catch(error => addNotice(error));
    }, []);

    return (
        <div
            className={'wc-braintree-blocks-apple-pay-button__container'}>
            <ApplePayButton
                onClick={handleClick}
                type={getData('buttonType')}
                style={getData('buttonStyle')}
                rounded={getData('roundedButton')}/>
        </div>
    )
}

const ApplePayButton = ({onClick, type, style, rounded = false}) => {
    const getButtonStyle = (style) => {
        switch (style) {
            case 'apple-pay-button-black':
                return 'black';
            case 'apple-pay-button-white':
                return 'white';
            case 'apple-pay-button-white-with-line':
                return 'white-outline';
        }
    }
    const styles = {
        'ApplePayButtonType': type,
        'ApplePayButtonStyle': getButtonStyle(style)
    }
    return <button
        className={`wc-braintree-blocks-apple-pay__button ${style} ${rounded ? 'apple-pay-button-rounded' : ''}`}
        style={styles}
        onClick={onClick}/>

}
if ((isCartPage() && getData('expressCartEnabled')) || (isCheckoutPage() && getData('expressCheckoutEnabled'))) {
    registerExpressPaymentMethod({
        name: getData('name'),
        canMakePayment: async () => {
            return window.ApplePaySession && ApplePaySession.canMakePayments();
        },
        content: <PaymentMethod content={Test}
                                title={getData('title')}
                                description={getData('description')}
                                name={getData('name')}
                                icon={getData('icon')}
                                getData={getData}
                                isExpress={true}/>,
        edit: <PaymentMethod content={ApplePayContainer} getData={getData}/>,
        supports: {
            features: getData('features')
        }
    });
}