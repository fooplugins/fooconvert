import removeLocalData from "./removeLocalData";
import removeSessionData from "./removeSessionData";
import { SEEN_STORAGE_KEY } from "./getSeen";

const resetSeen = () => {
    removeLocalData( SEEN_STORAGE_KEY );
    removeSessionData( SEEN_STORAGE_KEY );
};

export default resetSeen;