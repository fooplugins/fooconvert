import { default as DEFAULT_ICON_SET } from "./default";

/**
 * @typedef {{name: string, label: string, icons: {name: string, label: string, value: import("react").ReactNode}[]}} IconSet
 */

/**
 * @type {IconSet[]}
 */
const ICON_SETS = [ DEFAULT_ICON_SET ];

export default ICON_SETS;
