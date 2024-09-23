import {useEffect, useState, useRef} from '@wordpress/element';
import {getFromCache, storeInCache} from "../utils";
import {usePaymentMethodDataContext} from "../context";

export const useBreakpointWidth = (
    {
        key,
        el,
        className = '',
        breakpoint = 0
    }) => {
    const [windowWidth, setWindowWidth] = useState(window.innerWidth);
    key = `${key}:breakpoint`;
    useEffect(() => {
        if (el) {
            let minWidth = getFromCache(key, 0);
            if (breakpoint < minWidth || !minWidth) {
                minWidth = breakpoint;
                storeInCache(key, minWidth);
            }
            if (el.clientWidth < minWidth) {
                el.classList.add(className);
            } else {
                el.classList.remove(className);
            }
        }
    }, [el, windowWidth]);

    useEffect(() => {
        const onWindowResize = (e) => setWindowWidth(window.innerWidth);
        window.addEventListener('resize', onWindowResize);
        return () => window.removeEventListener('resize', onWindowResize);
    });
}

export const useExpressBreakpointWidth = (
    {
        breakpoint,
        className = 'wc-braintree-blocks-express-sm'
    }) => {
    const [element, setElement] = useState(null);
    const {container} = usePaymentMethodDataContext();

    useEffect(() => {
        if (container) {
            setElement(container.parentNode?.parentNode);
        }
    }, [container])

    useBreakpointWidth({
        key: 'express',
        el: element,
        breakpoint,
        className
    })
}