import {removeNumberPrecision, formatPrice, getShippingOptionId} from "../utils";


export const getDisplayItems = (cartTotalItems, unit = 2) => {
    let items = [];
    const keys = ['total_tax', 'total_shipping'];
    cartTotalItems.forEach(item => {
        if (0 < item.value || (item.key && keys.includes(item.key))) {
            items.push({
                label: item.label,
                type: 'LINE_ITEM',
                price: removeNumberPrecision(item.value, unit).toString()
            });
        }
    })
    return items;
}

export const getShippingOptions = (shippingRates) => {
    let options = [];
    shippingRates.forEach((shippingPackage, idx) => {
        let rates = shippingPackage.shipping_rates.map(rate => {
            let txt = document.createElement('textarea');
            txt.innerHTML = rate.name;
            let price = formatPrice(rate.price, rate.currency_code);
            return {
                id: getShippingOptionId(idx, rate.rate_id),
                label: txt.value,
                description: `${price}`
            }
        });
        options = [...options, ...rates];
    });
    return options;
}

export const getSelectedShippingOptionId = (shippingRates) => {
    let defaultSelectedOptionId = '';
    shippingRates.forEach((shippingPackage, idx) => {
        shippingPackage.shipping_rates.forEach(rate => {
            if (rate.selected) {
                defaultSelectedOptionId = getShippingOptionId(idx, rate.rate_id);
            }
        });
    });
    return defaultSelectedOptionId;
}