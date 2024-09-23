import {useState, useEffect, useCallback} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import classnames from 'classnames';
import {__} from '@wordpress/i18n';
import {getSettings, loadClient, getClientToken, canShowSavedCard} from "../utils";
import {PaymentMethod, PaymentMethodLabel, SavedTokenProvider, Notice} from '../../components';
import DropinForm from "./dropin-form";
import CustomForm from "./custom-form";
import {usePaymentMethodDataContext} from "../context";

const getData = getSettings('braintree_cc');

const CreditCardComponent = (props) => {
    const {setPaymentHandler, notice} = usePaymentMethodDataContext();
    const dropinEnabled = getData('dropinEnabled');
    const Content = dropinEnabled ? DropinContainer : HostedFieldsContainer;
    const classes = classnames('wc-braintree-card-container', {
        'dropin': dropinEnabled,
        'customForm': !dropinEnabled
    })

    return (
        <>
            {notice?.notice && <Notice notice={notice.notice} onRemove={notice.removeNotice}/>}
            <div className={classes}>
                <Content setPaymentHandler={setPaymentHandler}/>
            </div>
        </>
    )
}

const HostedFieldsContainer = (props) => {
    const options = {
        fields: getData('hostedFieldsOptions'),
        styles: getData('hostedFieldsStyles'),
        icons: getData('icons')
    };
    return <CustomForm options={options} client={loadClient} id={getData('customForm')} {...props}/>
}

const DropinContainer = ({setPaymentHandler}) => {
    const [dropinInstance, setDropinInstance] = useState(null);
    const handlePaymentProcessing = useCallback(() => {
        return new Promise((resolve, reject) => {
            dropinInstance.requestPaymentMethod((err, payload) => {
                if (err) {
                    reject(err);
                } else {
                    resolve(payload);
                }
            });
        })
    }, [dropinInstance]);
    useEffect(() => {
        setPaymentHandler(handlePaymentProcessing);
    }, [handlePaymentProcessing]);

    const options = {
        clientToken: getClientToken(),
        locale: 'en_US'
    }
    return (
        <DropinForm options={options} setDropinInstance={setDropinInstance}/>
    )
}

registerPaymentMethod({
    name: getData('name'),
    label: <PaymentMethodLabel
        title={getData('title')}
        paymentMethod={getData('name')}
        icon={getData('icon')}/>,
    ariaLabel: __('Credit Cards', 'woo-payment-gateway'),
    canMakePayment: () => true,
    content: <PaymentMethod content={CreditCardComponent}
                            title={getData('title')}
                            description={getData('description')}
                            name={getData('name')}
                            icon={getData('icon')}
                            getData={getData}/>,
    savedTokenComponent: <SavedTokenProvider getData={getData}
                                             name={getData('name')}
                                             vaultedThreeDSecure={getData('vaultedThreeDSecure')}/>,
    edit: <PaymentMethod content={CreditCardComponent} getData={getData}/>,
    supports: {
        showSavedCards: getData('features').includes('tokenization'),
        showSaveOption: canShowSavedCard(),
        features: getData('features')
    }
});