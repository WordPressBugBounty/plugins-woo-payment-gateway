import {create as createDropin} from '@braintree/dropin';
import {useEffect, useState, useRef} from '@wordpress/element';

export const DropinForm = ({options, setDropinInstance}) => {
    const {clientToken, locale = 'en_US'} = options;
    const [error, setError] = useState(null);
    const el = useRef(null);
    useEffect(() => {
        createDropin({
            authorization: clientToken,
            container: '.wc-braintree-blocks-dropin__form',
            locale: locale
        }, (error, dropinInstance) => {
            if (error) {
                setError(error?.message);
            } else {
                setDropinInstance(dropinInstance);
            }
        })
    }, []);
    return (
        <>
            {error && <div className={'wc-braintree-blocks__error'}>{error}</div>}
            <div ref={el} className={'wc-braintree-blocks-dropin__form'}>

            </div>
        </>
    )

}

export default DropinForm;