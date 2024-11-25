import { useEffect, useMemo } from "@wordpress/element";
import debounce from "../utils/debounce";

/**
 *
 * @template {Function} T
 * @param {T} fn
 * @param {number} delay
 * @returns {DebouncedFunction<T>}
 */
const useDebounce = ( fn, delay ) => {
    const debounced = useMemo(
        () => debounce( fn, delay ),
        []
    );
    useEffect( () => () => debounced.cancel(), [] );
    return debounced;
};

export default useDebounce;