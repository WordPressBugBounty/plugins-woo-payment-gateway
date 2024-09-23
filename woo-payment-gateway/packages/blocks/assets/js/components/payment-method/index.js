import {useRef, useState, useEffect} from '@wordpress/element';
import {PaymentMethodDataProvider} from '../../payment-methods/context';
import {useProcessPayment, useProcessPaymentFailure} from "../../payment-methods/hooks";

export const PaymentMethod = ({content, description = '', icons, ...props}) => {
    const el = useRef();
    const [container, setContainer] = useState(null);
    const Content = content;
    useEffect(() => {
        if (el.current) {
            setContainer(el.current);
        }
    }, []);
    return (
        <div ref={el} className={"wc-braintree-blocks-payment-method__container"}>
            {description &&
            <div className="wc-braintree-blocks-payment-method__description">
                {description}
            </div>}
            <PaymentMethodDataProvider container={container} {...props}>
                <PaymentMethodProviderContainer {...props}>
                    <Content {...props}/>
                </PaymentMethodProviderContainer>
            </PaymentMethodDataProvider>
        </div>
    )
}

const PaymentMethodProviderContainer = (
    {
        children,
        name,
        billing,
        shippingData,
        eventRegistration,
        emitResponse,
        activePaymentMethod,
        isExpress = false,
        advancedFraudOptions = {}
    }) => {
    const {onPaymentSetup, onCheckoutFail} = eventRegistration;
    const {responseTypes, noticeContexts} = emitResponse;

    useProcessPayment({
        onPaymentSetup,
        responseTypes,
        name,
        billing,
        shippingData,
        activePaymentMethod,
        advancedFraudOptions
    });

    useProcessPaymentFailure({
        event: onCheckoutFail,
        responseTypes,
        messageContext: isExpress ? noticeContexts.EXPRESS_PAYMENTS : null
    });
    return children;
}

export default PaymentMethod;