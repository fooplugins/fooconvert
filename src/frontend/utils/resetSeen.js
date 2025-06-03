import setLocalData from "./setLocalData";


/**
 * The key used to store the seen widget IDs in both localStorage and as a cookie.
 * @type {string}
 */
const STORAGE_KEY = 'fc_seen-list';

const resetSeen = () => setLocalData( STORAGE_KEY, [] );

export default resetSeen;