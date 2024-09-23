import threeDSecure from '@braintree/three-d-secure';
import dataCollector from '@braintree/data-collector';
import $ from 'jquery';
import apiFetch from '@wordpress/api-fetch';

let threeDSecureInstance = null;

let params = null;

const loadDataCollector = () => {
    dataCollector.create({
        authorization: getData('clientToken'),
        kount: true,
    }).then(instance => setData('dataCollector', instance)).catch(error => {
        console.log(error);
    });
}

const getData = (key, defaultValue = '') => {
    return params?.[key] || defaultValue;
}

const setData = (key, value) => {
    params[key] = value;
}

const setThreeDSecureInstance = (instance) => {
    threeDSecureInstance = instance;
}

const loadThreeDSecure = () => {
    threeDSecure.create({
        authorization: getData('clientToken'),
        version: 2
    }).then(instance => {
        setThreeDSecureInstance(instance)
        initEvents();
    }).catch(error => {
        console.log(error);
        addClientError(error);
    })
}

const initialize = () => {
    $(document).on('wfocuBucketCreated', onBucketCreated);
}

const initEvents = () => {
    $(document).on('wfocu_external', onHandleSubmit);
}

const onBucketCreated = (e, bucket) => {
    params = window?.wfocu_vars?.wcBraintree;
    params.bucket = bucket;
    loadThreeDSecure();
    loadDataCollector();
}

const onHandleSubmit = (e, bucket) => {
    if (0 < bucket.getTotal() && !getData('threeDSecureComplete', false)) {
        const vaultedNonce = getData('vaultedNonce');
        bucket.inOfferTransaction = true;
        const args = {
            amount: bucket.formatPrice(bucket.getTotal(), 2, "", "."),
            nonce: vaultedNonce?.nonce,
            bin: vaultedNonce?.details.bin,
            onLookupComplete: (data, next) => {
                next()
            },
            ...getData('threeDSecureData')
        }
        threeDSecureInstance.verifyCard(args).then(payload => {
            if ((!payload.liabilityShifted && !payload.liabilityShiftPossible) || payload.liabilityShifted) {
                wfocuCommons.addFilter('wfocu_front_charge_data', (e) => {
                    e._wc_braintree_woofunnels_3ds_nonce = payload.nonce;
                    if (getData('dataCollector')) {
                        e[`${getData('paymentMethod')}_device_data`] = getData('dataCollector')?.deviceData
                    }
                    setData('threeDSecureComplete', true);
                    return e;
                });
                bucket.sendBucket();
            } else {
                getData('bucket')?.swal?.hide();
                resetPaymentProcess();
                fetchVaultedNonce();
            }
        }).catch(error => {
            console.log(error);
            if (error?.details?.originalError?.details?.originalError?.error?.message) {
                const message = error.details.originalError.details.originalError.error.message;
                displayNotice(message, 'warning');
            }
            addClientError(error);
        });
    } else {
        resetPaymentProcess();
    }
}

const addClientError = (error) => {
    wfocuCommons.addFilter('wfocu_front_charge_data', (e) => {
        e._client_error = error?.message;
        return e;
    });
    resetPaymentProcess();
}

const resetPaymentProcess = () => {
    getData('bucket').inOfferTransaction = false;
    getData('bucket').EnableButtonState();
    getData('bucket').HasEventRunning = false;
}
const displayNotice = (message, type) => {
    getData('bucket')?.swal?.show({text: message, type});
    setTimeout(() => {
        getData('bucket')?.swal?.hide();
    }, 3000);
}

const fetchVaultedNonce = () => {
    apiFetch({
        path: '/wc-braintree/v1/3ds/vaulted_nonce',
        method: 'POST',
        data: {token: getData('paymentMethodToken')}
    }).then(response => {
        setData('vaultedNonce', response.data);
    }).catch(error => console.log(error));
}

initialize();