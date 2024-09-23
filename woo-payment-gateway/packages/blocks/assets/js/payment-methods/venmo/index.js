import {create as createVenmo} from '@braintree/venmo';
import {useState, useEffect} from '@wordpress/element';
import {__} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {Notice, PaymentMethodLabel} from "../../components";
import {PaymentMethod} from "../../components";
import {SavedTokenProvider} from "../../components";
import {getSettings, getClientToken} from "../utils";
import {usePaymentMethodDataContext} from "../context";
import {useHandleTokenization} from "./hooks";

const getData = getSettings('braintree_venmo');

const loadVenmo = new Promise((resolve, reject) => {
    createVenmo({
        authorization: getClientToken(),
        allowNewBrowserTab: false
    }).then(instance => resolve(instance)).catch(error => reject(error));
});

const VenmoComponent = ({onSubmit}) => {
    const {notice} = usePaymentMethodDataContext();
    const [venmoInstance, setVenmoInstance] = useState(null);
    const {handleClick} = useHandleTokenization({
        venmoInstance,
        onSubmit
    });

    useEffect(() => {
        loadVenmo.then(instance => setVenmoInstance(instance)).catch(error => notice.addNotice(error));
    }, []);
    return (
        <>
            {notice?.notice && <Notice notice={notice.notice} onRemove={notice.removeNotice}/>}
            <div className={'wc-braintree-blocks-venmo-button__container'}>
                <p>{__('Complete your order with', 'woo-payment-gateway')}</p>
                {venmoInstance && <VenmoButton onClick={handleClick} src={getData('buttonIcon')}/>}
            </div>
        </>
    )
}

const VenmoButton = ({onClick, src}) => {
    return (
        <button className={'wc-braintree-blocks-venmo__button'} onClick={onClick}>
            <img src={src}/>
        </button>
    )
}

registerPaymentMethod({
    name: getData('name'),
    label: <PaymentMethodLabel
        title={getData('title')}
        paymentMethod={getData('name')}
        icon={getData('icon')}/>,
    ariaLabel: __('Venmo', 'woo-payment-gateway'),
    placeOrderButtonLabel: getData('placeOrderButtonLabel'),
    canMakePayment: () => new Promise((resolve, reject) => {
        loadVenmo.then(instance => {
            resolve(instance.isBrowserSupported());
        }).catch(error => {
            resolve(false);
        });
    }),
    content: <PaymentMethod content={VenmoComponent}
                            title={getData('title')}
                            description={getData('description')}
                            name={getData('name')}
                            icon={getData('icon')}
                            getData={getData}/>,
    savedTokenComponent: <SavedTokenProvider getData={getData}
                                             name={getData('name')}
                                             vaultedThreeDSecure={getData('vaultedThreeDSecure')}/>,
    edit: <PaymentMethod content={VenmoComponent} getData={getData}/>,
    supports: {
        showSavedCards: getData('features').includes('tokenization'),
        showSaveOption: false,
        features: getData('features')
    }
});