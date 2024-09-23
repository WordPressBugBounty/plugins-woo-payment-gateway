const forms = {};

export const registerCustomForm = (config) => {
    assertValidConfig(config);
    forms[config.id] = config;
}

export const getCustomFormConfig = (id) => {
    if (assertValidFormId(id)) {
        return forms[id];
    }
    return {};
}

const assertValidFormId = (id) => {
    return forms.hasOwnProperty(id);
}

const assertValidConfig = (config) => {
    if (!config.id) {
        throw new Error('Custom forms must have a unique ID.');
    }
    assertConfigHasProperties(config, ['content', 'breakpoint']);
}

const assertConfigHasProperties = (config, props = []) => {
    const missingParams = props.reduce((acc, prop) => {
        if (!config.hasOwnProperty(prop)) {
            acc.push(prop);
        }
        return acc;
    }, []);
    if (missingParams.length > 0) {
        throw new TypeError(`Custom form config missing params ${missingParams.join(', ')}`);
    }
}