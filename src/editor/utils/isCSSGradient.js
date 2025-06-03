/**
 * Checks if a CSS value is a gradient.
 * @param {string} value - The CSS value to check.
 * @returns {boolean} True if the value is a valid CSS gradient function, false otherwise.
 */
const isCSSGradient = (value) => Boolean(value.match(/^(linear|radial|conic|repeating-linear|repeating-radial|repeating-conic)-gradient\(/));

export default isCSSGradient;