import classnames from "classnames";

export const CardCVV = ({setFieldContainer, setFieldOrder, className}) => {
    setFieldOrder('cvv');
    const classes = classnames('braintree-web-hosted-field', className);
    return <div ref={setFieldContainer('cvv')} id='braintree-cvv' className={classes}></div>
}

export default CardCVV;