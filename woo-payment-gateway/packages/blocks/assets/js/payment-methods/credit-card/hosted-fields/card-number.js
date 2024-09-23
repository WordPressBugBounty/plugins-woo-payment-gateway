import {useEffect, useRef} from "@wordpress/element";
import classnames from "classnames";

export const CardNumber = ({onChange, setFieldContainer, setFieldOrder, className, CardIcon}) => {
    setFieldOrder('number');
    const classes = classnames('braintree-web-hosted-field', className);
    return (
        <div ref={setFieldContainer('number')} id='braintree-number' className={classes}>
            {CardIcon}
        </div>
    )
}

export default CardNumber;