import {__} from '@wordpress/i18n';
import {CardNumber, CardExpirationDate, CardCVV} from "../../hosted-fields";
import './style.scss';

export const SimpleForm = (props) => {
    return (
        <div className='wc-braintree-blocks-simple__form'>
            <div className='form-group'>
                <label>{__('Card Number', 'woo-payment-gateway')}</label>
                <CardNumber className='hosted-field braintree-card-number' {...props}/>
            </div>
            <div className='form-group'>
                <label>{__('Exp Date', 'woo-payment-gateway')}</label>
                <CardExpirationDate className='hosted-field' {...props}/>
            </div>
            <div className='form-group'>
                <label>{__('CVV', 'woo-payment-gateway')}</label>
                <CardCVV className='hosted-field' {...props}/>
            </div>
        </div>
    )
}

export default SimpleForm;