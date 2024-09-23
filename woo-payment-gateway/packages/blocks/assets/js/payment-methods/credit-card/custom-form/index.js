import {useState, useEffect, useCallback, useRef, cloneElement} from '@wordpress/element';
import {__, sprintf} from '@wordpress/i18n';
import {create} from '@braintree/hosted-fields';
import classnames from "classnames";
import {getCustomFormConfig} from "./registry";
import {useBreakpointWidth} from "../../hooks";
import '../custom-forms';

export const CustomForm = ({options, client, id, setPaymentHandler}) => {
    const config = getCustomFormConfig(id);
    const {breakpoint = 0, fields: fieldKeys = [], content: Content = null, className = ''} = config;
    const [hostedFieldInstance, setHostedFieldsInstance] = useState(false);
    const [fields, setFields] = useState({});
    const [containerElement, setContainerElement] = useState();
    const [cardBrand, setCardBrand] = useState(null);
    const fieldOrder = useRef([]);
    const setFieldOrder = (id) => {
        if (!fieldOrder.current.includes(id)) {
            fieldOrder.current.push(id);
        }
    }
    const getIconSrc = (type) => {
        for (let icon of options.icons) {
            if (icon.type === type) {
                return icon.src;
            }
        }
        return null;
    };
    const assertFieldsValid = useCallback(fields => {
        const missingFields = fieldKeys.reduce((acc, prop) => {
            if (!fields.hasOwnProperty(prop)) {
                acc.push(prop);
            }
            return acc;
        }, []);
        return missingFields.length == 0;
    }, []);
    const setFieldContainer = useCallback((id) => (container) => {
        if (!fields?.[id]?.container && container !== null) {
            const field = {
                container,
                ...options.fields[id]
            }
            setFields({...fields, [id]: field});
        }
    }, [fields, setFields]);

    const addClassToFields = useCallback((keys, className) => {
        keys.forEach(key => {
            fields?.[key]?.container.classList.add(className);
        });
    }, [fields]);

    const paymentHandler = useCallback(({shippingData, billing}) => new Promise((resolve, reject) => {
        const options = {billingAddress: {}};
        if (billing?.billingAddress?.address_1) {
            options.billingAddress.streetAddress = billing.billingAddress.address_1;
        }
        if (billing?.billingAddress?.postcode) {
            options.billingAddress.postalCode = billing.billingAddress.postcode;
        }
        hostedFieldInstance.tokenize(options, (error, payload) => {
            if (error) {
                if (error.code === 'HOSTED_FIELDS_FIELDS_INVALID') {
                    addClassToFields(error?.details?.invalidFieldKeys || [], 'braintree-hosted-fields-invalid');
                } else if (error.code === 'HOSTED_FIELDS_FIELDS_EMPTY') {
                    addClassToFields(fieldKeys, 'braintree-hosted-fields-invalid');
                }
                reject(error);
            } else {
                resolve(payload);
            }
        });
    }), [hostedFieldInstance]);

    useEffect(() => {
        setPaymentHandler(paymentHandler);
    }, [paymentHandler]);

    useBreakpointWidth({key: id, el: containerElement, className, breakpoint});

    useEffect(() => {
        if (assertFieldsValid(fields)) {
            client.then(clientInstance => {
                create({
                    client: clientInstance,
                    fields,
                    styles: options.styles
                }).then(instance => setHostedFieldsInstance(instance)).catch(error => {

                });
            });
        }
    }, [fields]);

    useEffect(() => {
        if (hostedFieldInstance) {
            hostedFieldInstance.on('cardTypeChange', (event) => {
                if (event?.cards?.length == 1) {
                    setCardBrand(event.cards[0].type);
                } else {
                    setCardBrand(null);
                }
            });
            hostedFieldInstance.on('validityChange', (event) => {
                const field = event.fields[event.emittedBy];
                if (field.isValid) {
                    const idx = fieldOrder.current.indexOf(event.emittedBy);
                    if (fieldOrder.current?.[idx + 1]) {
                        const nextField = fieldOrder.current[idx + 1];
                        hostedFieldInstance.focus(nextField);
                    }
                }
            });
        }
    }, [hostedFieldInstance]);
    if (!Content) {
        return <InvalidCustomForm id={id}/>;
    }
    return (
        <div ref={setContainerElement} className='wc-braintree-blocks-custom__form'>
            {cloneElement(Content, {
                setFieldContainer,
                setFieldOrder,
                CardIcon: <CardIcon type={cardBrand} src={getIconSrc(cardBrand)}/>
            })}
        </div>
    );
}

const InvalidCustomForm = ({id}) => {
    return (
        <div className={'wc-braintree-blocks-notice__info'}>
            {sprintf(__('%s is not a supported custom form.', 'woo-payment-gateway'), id)}
        </div>
    );
};

const CardIcon = ({type, src}) => {
    if (!type) {
        return null;
    }
    const classes = classnames('wc-braintree-blocks-current-card__icon', type);
    return (
        <img className={classes} src={src}/>
    )
}

export default CustomForm;