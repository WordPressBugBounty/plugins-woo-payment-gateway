import {useState, useCallback} from '@wordpress/element';
import {getErrorMessage} from "../utils";

export const useNoticeHandler = ({isExpress, setExpressPaymentError}) => {
    const [notice, setNotice] = useState(null);
    const addNotice = useCallback((error, type = 'error', isDismissible = true) => {
        if (isExpress && type == 'error') {
            setExpressPaymentError(getErrorMessage(error));
        } else {
            setNotice({
                message: getErrorMessage(error),
                type,
                isDismissible
            });
        }

    }, [setNotice, isExpress, setExpressPaymentError]);
    const removeNotice = useCallback(() => setNotice(null), [setNotice]);
    return [
        notice,
        addNotice,
        removeNotice
    ]
}

export default useNoticeHandler;