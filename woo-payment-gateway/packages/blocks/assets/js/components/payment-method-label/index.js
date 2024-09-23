export const PaymentMethodLabel = ({title, paymentMethod, components, icon = []}) => {
    const {PaymentMethodLabel: Label, PaymentMethodIcons: Icons} = components;
    if (!Array.isArray(icon)) {
        icon = [icon];
    }
    return (
        <span className={'wc-braintree-blocks-paymentMethod__label'}>
            <Label text={title}/>
            <Icons icons={icon} align='left'/>
        </span>
    )
}

export default PaymentMethodLabel;