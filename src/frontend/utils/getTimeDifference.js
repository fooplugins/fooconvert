const SECOND = 1000;
const MINUTE = SECOND * 60;
const HOUR = MINUTE * 60;
const DAY = HOUR * 24;

/**
 *
 * @param {Date} date1
 * @param {?Date} [date2]
 * @returns {{date1: Date, date2: Date, valid: boolean, expired: boolean, days: number, hours: number, minutes: number, seconds: number}}
 */
const getTimeDifference = ( date1, date2 = new Date() ) => {
    let valid = false;
    let expired = false;
    let days = 0;
    let hours = 0;
    let minutes = 0;
    let seconds = 0;

    if ( date1 instanceof Date && date2 instanceof Date ) {
        valid = true;
        const diff = date1.getTime() - date2.getTime();
        expired = diff < 0;
        days = Math.max( 0, Math.floor( diff / DAY ) );
        hours = Math.max( 0, Math.floor( ( diff % DAY ) / HOUR ) );
        minutes = Math.max( 0, Math.floor( ( diff % HOUR ) / MINUTE ) );
        seconds = Math.max( 0, Math.floor( ( diff % MINUTE ) / SECOND ) );
    }
    return {
        date1,
        date2,
        valid,
        expired,
        days,
        hours,
        minutes,
        seconds
    };
};

export default getTimeDifference;