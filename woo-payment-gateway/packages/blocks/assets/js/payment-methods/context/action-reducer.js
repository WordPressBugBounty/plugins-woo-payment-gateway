export const ACTIONS = {
    ADD_ACTION: 'add_action',
    REMOVE_ACTION: 'remove_action'
}

export const actionReducer = (state = {}, {action, type, id, callback, priority = 10}) => {
    const event = state[type] ? state[type] : new Map();
    switch (action) {
        case ACTIONS.ADD_ACTION:
            event.set(id, {callback, priority});
            return {...state, [type]: event};
        case ACTIONS.REMOVE_ACTION:
            event.delete(id);
            return {...state, [type]: event};
    }
    return state;
}