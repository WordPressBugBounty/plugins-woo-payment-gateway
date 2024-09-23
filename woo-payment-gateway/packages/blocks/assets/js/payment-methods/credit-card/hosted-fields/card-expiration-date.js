import classnames from "classnames";

export const CardExpirationDate = ({setFieldContainer, setFieldOrder, className}) => {
    setFieldOrder('expirationDate');
    const classes = classnames('braintree-web-hosted-field', className);
    return (
        <div ref={setFieldContainer('expirationDate')} id='braintree-exp-date'
             className={classes}></div>
    )
}

export default CardExpirationDate;