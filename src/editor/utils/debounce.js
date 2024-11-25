/**
 * @template {Function} T
 * @typedef {( ...params: Parameters<T> ) => void} DebouncedFunction
 * @property {() => void} cancel - A function that cancels any pending debounced callbacks.
 * @property {T} fn - A reference to the original function.
 */
/**
 * Debounce a function only allowing its execution after the given delay.
 *
 * @template {Function} T
 * @param {T} fn - The function to debounce.
 * @param {number} delay - The number in milliseconds to delay execution of the given function.
 * @returns {DebouncedFunction<T>} Returns the debounced function.
 */
const debounce = ( fn, delay ) => {
    let timer = null;
    function debounced( ...args ) {
        clearTimeout( timer );
        timer = setTimeout( () => {
            fn( ...args );
        }, delay );
    }
    debounced.fn = fn;
    debounced.cancel = () => clearTimeout( timer );
    return debounced;
};

export default debounce;