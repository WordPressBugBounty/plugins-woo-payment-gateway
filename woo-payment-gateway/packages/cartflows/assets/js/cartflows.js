import braintreeClient from '@braintree/client';
import threeDSecure from '@braintree/three-d-secure';
import $ from 'jquery';

const wcBraintreeData = cartflows_offer.wcBraintree;

const client = new Promise((resolve, reject) => {
    braintreeClient.create({
        authorization: wcBraintreeData.clientToken
    }, (err, clientInstance) => {
        if (err) {
            console.log(err);
            reject()
        } else {
            resolve(clientInstance);
        }
    })
});

const _3dSecure = new Promise(async (resolve, reject) => {
    const clientInstance = await client;
    threeDSecure.create({
        version: 2,
        client: clientInstance
    }, (err, instance) => {
        if (err) {
            return reject(err);
        }
        return resolve(instance);
    });
})

let currentButton;

const initialize = () => {
    window.addEventListener('hashchange', handleHashChange);
    $(document.body).on('click', 'a[href*="wcf-up-offer"], a[href*="wcf-down-offer"]', handleButtonClick);
}

const handleButtonClick = (e) => {
    currentButton = $(e.currentTarget);
}

const handleHashChange = async (e) => {
    var match = e.newURL.match(/wcBraintree3DS=(.*)/);
    if (match) {
        try {
            var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
            if (obj.nonce) {
                history.pushState({}, '', window.location.pathname + window.location.search);
                // process the vaulted nonce
                _3dSecure.then(instance => {
                    instance.verifyCard({
                        amount: obj.amount,
                        nonce: obj.nonce,
                        bin: obj.details.bin,
                        ...wcBraintreeData?.order?.threeDSData,
                        onLookupComplete: (data, next) => next(),
                    }, async (err, payload) => {
                        if (err) {
                            displayMessage(err.message);
                        } else {
                            if (payload.liabilityShifted) {
                                // save the nonce to the order
                                await storeVaultedNonce({
                                    nonce: payload.nonce,
                                    order_id: cartflows_offer.order_id,
                                    step_id: cartflows_offer.step_id
                                });
                                // nonce saved, trigger click
                                currentButton.click();
                            } else {
                                displayMessage(wcBraintreeData.threeDSFailedMsg);
                            }
                        }
                    });
                })
            } else if (obj.error) {
                displayMessage(obj.message);
            }
        } catch (err) {

        }
    }
    return true;
}

const storeVaultedNonce = ({nonce, order_id, step_id}) => {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: wcBraintreeData?.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {nonce, order_id, step_id, security: wcBraintreeData?.security?.storeVaultedNonce}
        }).done(result => {
            if (result.success) {
                resolve();
            } else {
                // display error
            }
        }).fail(() => {
            reject();
        })
    })
}

const displayMessage = (msg) => {
    $('body').trigger('wcf-update-msg', [msg, 'wcf-payment-error']);
    setTimeout(() => {
        $(document.body).trigger('wcf-hide-loader');
        $(document.body).trigger('wcf-update-msg', [wcBraintreeData.successMessage, 'wcf-payment-success']);
    }, wcBraintreeData.timeout);
}

initialize();