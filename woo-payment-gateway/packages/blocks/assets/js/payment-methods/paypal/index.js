import {useState, useEffect, useRef, useCallback} from '@wordpress/element';
import {registerExpressPaymentMethod} from '@woocommerce/blocks-registry';
import {ExperimentalOrderMeta, TotalsWrapper} from '@woocommerce/blocks-checkout';
import {registerPlugin} from '@wordpress/plugins';
import {create as createPayPalCheckout} from '@braintree/paypal-checkout';
import {getClientToken, getSettings, getMerchantAccount, cartContainsSubscription, isCartPage, isCheckoutPage} from "../utils";
import {Notice, PaymentMethod} from "../../components";
import {usePaymentMethodDataContext} from "../context";
import {useLoadPayPal, usePayPalOptions} from "./hooks";
import {useExpressBreakpointWidth} from "../hooks";

const getData = getSettings('braintree_paypal');

const PayPalContainer = ({billing, shippingData, eventRegistration, emitResponse, onClick, onClose}) => {
    const {currency} = billing;
    const {notice} = usePaymentMethodDataContext();
    const {addNotice} = notice;
    const [paypalCheckoutInstance, setPayPalCheckoutInstance] = useState(null);
    const [isPayPalButton, setIsPayPalButton] = useState(false);
    const paypalButton = useRef();
    useExpressBreakpointWidth({breakpoint: 375});
    const paypal = useLoadPayPal({
        paypalCheckoutInstance,
        currency: currency.code,
        clientToken: getClientToken(),
        addNotice,
        intent: getData('intent'),
        flow: cartContainsSubscription() ? 'vault' : 'checkout',
        partnerCode: getData('partnerCode')
    });
    const options = usePayPalOptions({
        getData,
        addNotice,
        paypal,
        paypalCheckoutInstance,
        billing,
        shippingData,
        eventRegistration,
        emitResponse,
        onClick,
        onClose
    })
    useEffect(() => {
        createPayPalCheckout({
            authorization: getClientToken(),
            merchantAccountId: getMerchantAccount(currency.code)
        }).then(instance => {
            setPayPalCheckoutInstance(instance)
        }).catch(error => {
            addNotice(error)
        });
    }, []);

    useEffect(() => {
        if (paypal) {
            paypal.Buttons.driver("react", {React, ReactDOM});
            paypalButton.current = paypal.Buttons.driver("react", {React, ReactDOM});
            setIsPayPalButton(true);
        }
    }, [paypal]);
    const PayPalButton = paypalButton.current;
    const BUTTON = isPayPalButton && options ? options.map(option => {
        return <PayPalButton key={option.fundingSource} {...option}/>
    }) : null;
    return (
        <>
            {notice?.notice && <Notice notice={notice.notice} onRemove={notice.removeNotice}/>}
            {BUTTON}
        </>
    )
}

if ((isCartPage() && getData('expressCartEnabled')) || (isCheckoutPage() && getData('expressCheckoutEnabled'))) {
    registerExpressPaymentMethod({
        name: getData('name'),
        canMakePayment: () => {
            return true;
        },
        content: <PaymentMethod content={PayPalContainer}
                                title={getData('title')}
                                description={getData('description')}
                                name={getData('name')}
                                icon={getData('icon')}
                                getData={getData}
                                isExpress={true}
                                advancedFraudOptions={{paypal: true}}/>,
        edit: <PaymentMethod content={PayPalContainer} getData={getData}/>,
        supports: {
            showSavedCards: getData('features').includes('tokenization'),
            showSaveOption: true,
            features: getData('features')
        }
    });
}

const OrderItemMessaging = ({cart, extensions, context}) => {
    const {cartTotals} = cart;
    const {currency_code: currency, total_price} = cartTotals;
    const [paypalCheckout, setPayPalCheckout] = useState();
    const currencies = getData('payLaterMsgCurrencies');
    const isAvailable = getData('paylaterMsgEnabled') && currencies.includes(currency) && !cartContainsSubscription();
    const container = useRef(null);
    if (!isAvailable) {
        return null;
    }
    useEffect(() => {
        createPayPalCheckout({
            authorization: getClientToken(),
            merchantAccountId: getMerchantAccount(currency)
        }).then((instance) => setPayPalCheckout(instance)).catch(() => {
        });
    }, []);
    const paypal = useLoadPayPal({
        paypalCheckoutInstance: paypalCheckout,
        currency,
        addNotice: null,
        clientToken: getClientToken(),
        intent: getData('intent'),
        flow: 'checkout',
        partnerCode: getData('partnerCode')
    });

    useEffect(() => {
        if (paypal) {
            paypal.Messages({
                amount: total_price / (10 ** cartTotals.currency_minor_unit),
                currency,
                placement: 'checkout',
                style: {
                    layout: 'text',
                    logo: {
                        type: 'primary',
                        position: 'left'
                    },
                    text: {color: getData('paylaterTxtColor')}
                }
            }).render(container.current);
        }
    }, [paypal, total_price]);
    return (
        <TotalsWrapper>
            <div className='wc-block-components-totals-item'>
                <div ref={container} className='paypal-msg-container'></div>
            </div>
        </TotalsWrapper>
    )
}

const render = () => {
    return (
        <ExperimentalOrderMeta>
            <OrderItemMessaging/>
        </ExperimentalOrderMeta>
    )
}


registerPlugin('wc-braintree', {
    render,
    scope: 'woocommerce-checkout'
});
