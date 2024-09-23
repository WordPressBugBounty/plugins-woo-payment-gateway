import {getShippingOptionId, removeNumberPrecision} from "../utils";

export const formatLineItems = (lineItems, minorUnit) => {
    let items = [];
    const keys = ['total_tax', 'total_shipping'];
    lineItems.forEach(item => {
        if (0 < item.value || (item.key && keys.includes(item.key))) {
            items.push({
                label: item.label,
                type: 'final',
                amount: removeNumberPrecision(item.value, minorUnit).toString()
            });
        }
    })
    return items;
}

export const formatShippingMethods = (shippingRates) => {
    let options = [];
    shippingRates.forEach((shippingPackage, idx) => {
        let rates = shippingPackage.shipping_rates.map(rate => {
            let txt = document.createElement('textarea');
            txt.innerHTML = rate.name;
            return {
                identifier: getShippingOptionId(idx, rate.rate_id),
                label: txt.value,
                detail: '',
                amount: removeNumberPrecision(rate.price, rate.currency_minor_unit)
            }
        });
        options = [...options, ...rates];
    });
    return options;
}