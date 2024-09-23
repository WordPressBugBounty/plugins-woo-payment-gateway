import {useMemo} from '@wordpress/element';
import {uniqueId} from 'lodash';
import {ACTIONS} from './action-reducer';

export const useActionEmitter = (dispatch) => {
    const data = useMemo(() => ({
        onPaymentDataFilter: (callback, priority = 10) => {
            const action = {
                action: ACTIONS.ADD_ACTION,
                type: 'payment_data_filter',
                id: uniqueId(),
                callback,
                priority
            };
            dispatch(action);
            return () => dispatch({action: ACTIONS.REMOVE_ACTION, type: action.type, id: action.id})
        }
    }), [dispatch]);
    return data;
}

export const emittFilter = async (data, args, {handlers, type}) => {
    const events = handlers[type];
    const sortedEvents = Array.from(events.values()).sort((a, b) => a.priority - b.priority);
    try {
        for (const event of sortedEvents) {
            data = await Promise.resolve(event.callback(data, args));
        }
    } catch (error) {
        console.log(error);
        throw error;
    }
    return data;
}

