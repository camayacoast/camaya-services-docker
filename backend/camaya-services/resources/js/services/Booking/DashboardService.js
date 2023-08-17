import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'
import moment from 'moment-timezone'


const data = (date) => {

    if (date) {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard?date=${moment(date).format('YYYY-MM-DD')}`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    } else {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    }

}

const arrivalForecastReport = (startDate, endDate) => {

    if (startDate && endDate) {
        return useQuery("arrival-forecast-report-data", async () => {
            const { data } = await Http.get(`/api/booking/arrival-forecast-report?start_date=${moment(startDate).format('YYYY-MM-DD')}&end_date=${moment(endDate).format('YYYY-MM-DD')}`);
            return data;
        }, {
            refetchOnWindowFocus: false,
            // refetchOnMount: false,
        });
    } else {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard`);
            return data;
        });
    }
}

const guestArrivalStatusReport = (startDate, endDate) => {

    if (startDate && endDate) {
        return useQuery("guest-arrival-status-data", async () => {
            const { data } = await Http.get(`/api/booking/guest-arrival-status-report?start_date=${moment(startDate).format('YYYY-MM-DD')}&end_date=${moment(endDate).format('YYYY-MM-DD')}`);
            return data;
        });
    } else {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard`);
            return data;
        });
    }
}

const dttReport = (startDate, endDate) => {

    if (startDate && endDate) {
        return useQuery("dtt-report-data", async () => {
            const { data } = await Http.get(`/api/booking/dtt-report?start_date=${moment(startDate).format('YYYY-MM-DD')}&end_date=${moment(endDate).format('YYYY-MM-DD')}`);
            return data;
        });
    } else {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard`);
            return data;
        });
    }
}

const dttRevenueReport = (startDate, endDate) => {

    if (startDate && endDate) {
        return useQuery("dtt-revenue-data", async () => {
            const { data } = await Http.get(`/api/booking/dtt-revenue-report?start_date=${moment(startDate).format('YYYY-MM-DD')}&end_date=${moment(endDate).format('YYYY-MM-DD')}`);
            return data;
        });
    } else {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard`);
            return data;
        });
    }
}

const conciergeData = (date) => {

    if (date) {
        return useQuery("concierge-dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/concierge-dashboard?date=${moment(date).format('YYYY-MM-DD')}`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    } else {
        return useQuery("dashboard-data", async () => {
            const { data } = await Http.get(`/api/booking/dashboard`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    }

}

const corregidorBookings= () => {
    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/corregidor-bookings-per-date', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}


const updateDailyLimit = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update-daily-limit', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const updateFerryPassengersLimit = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update-ferry-passengers-limit', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const generateDailyGuestPerDay = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/generate-daily-guest-per-day', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}


const dailyGuestLimitPerDay = (month, year) => {

    if (month && year) {
        return useQuery("daily-guest-limit-data", async () => {
            const { data } = await Http.get(`/api/booking/get-daily-guest-limit-per-month-year?month=${month}&year=${year}`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    }

}

const editDailyGuestPerDay = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/update-daily-guest-per-day', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const updateRemarks = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/update-remarks', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}


export default {
    data,
    arrivalForecastReport,
    guestArrivalStatusReport,
    dttReport,
    dttRevenueReport,
    conciergeData,
    corregidorBookings,
    updateDailyLimit,
    updateFerryPassengersLimit,
    generateDailyGuestPerDay,
    dailyGuestLimitPerDay,
    editDailyGuestPerDay,
    updateRemarks
}