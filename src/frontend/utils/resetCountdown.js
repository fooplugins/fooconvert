import removeLocalData from "./removeLocalData";
import removeSessionData from "./removeSessionData";
import { COUNTDOWN_STORAGE_KEY } from "./getCountdown";

const resetCountdown = () => {
    removeLocalData( COUNTDOWN_STORAGE_KEY );
    removeSessionData( COUNTDOWN_STORAGE_KEY );
};

export default resetCountdown;
