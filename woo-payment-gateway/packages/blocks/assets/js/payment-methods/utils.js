import {getSetting} from '@woocommerce/settings';
import {create as createClient} from '@braintree/client';
import {getCurrency, formatPrice as wcFormatPrice} from '@woocommerce/price-format';

const wcBraintreeData = getSetting('wcBraintreeData');

const errorMessages = getSetting('wcBraintreeMessages');

const CACHE_PREFIX = 'wcBraintree:';

const SHIPPING_OPTION_REGEX = /^([\w]+)\:(.+)$/;

export const getSettings = (name) => {
    name = `${name}_data`;
    return (key) => {
        if (key) {
            return getSetting(name)[key];
        }
        return getSetting(name);
    }
}

export const getClientToken = () => wcBraintreeData.clientToken;

export const getMerchantAccount = (currency) => wcBraintreeData?.merchantAccounts?.[currency] ? wcBraintreeData.merchantAccounts[currency] : null

export const loadClient = new Promise((resolve, reject) => {
    createClient({
        authorization: wcBraintreeData.clientToken
    }, (err, clientInstance) => {
        if (err) {
            reject(err);
        } else {
            resolve(clientInstance);
        }
    });
});

export const canMakePayment = () => {
    return new Promise((resolve, reject) => {
        loadClient.then(() => resolve(true)).catch(error => {
            console.log('Error loading client: ', error);
            resolve({error});
        });
    });
}

export const ensureSuccessResponse = (responseTypes, data = {}) => {
    return {type: responseTypes.SUCCESS, ...data};
}

export const ensureErrorResponse = (responseTypes, error) => {
    return {type: responseTypes.ERROR, message: getErrorMessage(error)}
};

export const getErrorMessage = (error) => {
    let msg = error?.message;
    if (typeof error == 'string') {
        msg = error;
    } else {
        if (error.code && errorMessages[error.code]) {
            msg = errorMessages[error.code];
        }
    }
    return msg;
}

export const removeNumberPrecision = (value, unit) => {
    return value / 10 ** unit;
}

export class BraintreeError extends Error {
    constructor(error) {
        super(error.message);
        this.error = error;
    }
}

const getCacheKey = (key) => `${CACHE_PREFIX}${key}`;

export const storeInCache = (key, value) => {
    const exp = Math.floor(new Date().getTime() / 1000) + (60 * 15);
    if ('sessionStorage' in window) {
        sessionStorage.setItem(getCacheKey(key), JSON.stringify({value, exp}));
    }
}

export const getFromCache = (key, defaultValue = null) => {
    if ('sessionStorage' in window) {
        try {
            const item = JSON.parse(sessionStorage.getItem(getCacheKey(key)));
            if (item) {
                const {value, exp} = item;
                if (Math.floor(new Date().getTime() / 1000) > exp) {
                    deleteFromCache(getCacheKey(key));
                } else {
                    return value;
                }
            }
        } catch (err) {
        }
    }
    return defaultValue;
}

export const deleteFromCache = (key) => {
    if ('sessionStorage' in window) {
        sessionStorage.removeItem(getCacheKey(key));
    }
}

export const formatPrice = (price, currencyCode) => {
    const {prefix, suffix, decimalSeparator, minorUnit, thousandSeparator} = getCurrency(currencyCode);
    if (price == '' || price === undefined) {
        return price;
    }

    price = typeof price === 'string' ? parseInt(price, 10) : price;
    price = price / 10 ** minorUnit;
    price = price.toString().replace('.', decimalSeparator);
    let fractional = '';
    const index = price.indexOf(decimalSeparator);
    if (index < 0) {
        if (minorUnit > 0) {
            price += `${decimalSeparator}${new Array(minorUnit + 1).join('0')}`;
        }
    } else {
        const fractional = price.substr(index + 1);
        if (fractional.length < minorUnit) {
            price += new Array(minorUnit - fractional.length + 1).join('0');
        }
    }
    // separate out price and decimals so thousands separator can be added.
    const match = price.match(new RegExp(`(\\d+)\\${decimalSeparator}(\\d+)`));
    if (match) {
        ({1: price, 2: fractional} = match);
    }
    price = price.replace(new RegExp(`\\B(?=(\\d{3})+(?!\\d))`, 'g'), `${thousandSeparator}`);
    price = fractional?.length > 0 ? price + decimalSeparator + fractional : price;
    price = prefix + price + suffix;
    return price;
}

export const getShippingOptionId = (packageId, rateId) => `${packageId}:${rateId}`;

export const extractSelectedShippingOption = (id) => {
    const result = id.match(SHIPPING_OPTION_REGEX);
    if (result) {
        const {1: packageIdx, 2: rate} = result;
        return [rate, packageIdx];
    }
    return [];
}

export const getSelectedShippingOptionId = (selectedRates) => {
    for (let idx of Object.keys(selectedRates)) {
        return getShippingOptionId(idx, selectedRates[idx]);
    }
}

export const extractFullName = (name) => {
    const firstName = name.split(' ').slice(0, -1).join(' ');
    const lastName = name.split(' ').pop();
    return [firstName, lastName];
}

export const extractAddressLines = (lines) => {
    let address_1, address_2;
    if (Array.isArray(lines)) {
        address_1 = lines?.[0];
        if (lines.length > 1) {
            address_2 = lines?.[1];
        }
    }
    return {address_1, address_2};
}

export const getCartTotalItem = (key, cartTotalItems) => {
    for (let item of cartTotalItems) {
        if (item.key === key) {
            return item.value;
        }
    }
    return null;
}

export const canShowSavedCard = () => {
    return !cartContainsSubscription();
}

export const cartContainsSubscription = () => wcBraintreeData?.hasSubscription == true;

export const isCartPage = () => wcBraintreeData.page === 'cart';

export const isCheckoutPage = () => wcBraintreeData.page === 'checkout';

export const DEFAULT_SHIPPING_ADDRESS = {
    'first_name': '',
    'last_name': '',
    'company': '',
    'address_1': '',
    'address_2': '',
    'city': '',
    'state': '',
    'postcode': '',
    'country': '',
    'phone': '',
}

export const DEFAULT_BILLING_ADDRESS = {
    ...DEFAULT_SHIPPING_ADDRESS,
    'email': ''
}
