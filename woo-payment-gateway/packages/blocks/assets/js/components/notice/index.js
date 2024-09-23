import classNames from "classnames";
import {Notice as WordpressNotice} from '@wordpress/components';

export const Notice = ({notice, onRemove}) => {
    const classes = classNames(`wc-braintree-blocks__notice ${notice.type}`);
    return (
        <WordpressNotice
            key={`notice-${notice.type}`}
            className={classes}
            onRemove={() => {
                if (notice.isDismissible) {
                    onRemove();
                }
            }} {...notice}>
            {notice.message}
        </WordpressNotice>
    )
}

export default Notice;