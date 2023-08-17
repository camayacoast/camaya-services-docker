import moment from 'moment'

export const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

export const enumerateDaysBetweenDates = function(startDate, endDate) {
    var dates = [];

    var currDate = moment(startDate).startOf('day');
    var lastDate = moment(endDate).startOf('day');

    while(currDate.add(1, 'days').diff(lastDate) < 0) {
        // console.log(currDate.toDate());
        dates.push(currDate.clone().format('YYYY-MM-DD'));
    }

    return dates;
};