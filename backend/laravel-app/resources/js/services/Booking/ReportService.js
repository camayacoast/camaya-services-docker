import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const bookingsWithInclusions = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/bookings-with-inclusions', formData)
    });
}

const arrivalForecast = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/arrival-forecast', formData)
    });
}

const guestArrivalStatus = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/guest-arrival-status', formData)
    });
}

const dtt = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/dtt', formData)
    });
}

const dttRevenue = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/dtt-revenue', formData)
    });
}

const commercialSales = (start_date, end_date) => {    
    
    return useQuery(['reports', 'commercial-sales', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/booking/reports/commercial-sales/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const commercialSalesDownload = (start_date, end_date) => {

    Http.get(`/api/booking/reports/commercial-sales/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Commercial-Sales-${start_date}-${end_date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const dailyBookingsPerSD = (start_date, end_date) => {  
    
    return useQuery(['reports', 'daily-booking-per-sd', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/booking/reports/daily-booking-per-sd/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const dailyBookingsPerSDDownload = (start_date, end_date) => {

    Http.get(`/api/booking/reports/daily-booking-per-sd/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        if (data) {
            const downloadUrl = window.URL.createObjectURL(new Blob([data]));
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', `Daily-Booking-Per-Sales-Director-${start_date}-${end_date}.xlsx`); //any other extension
            document.body.appendChild(link);
            link.click();
            link.remove();
        }
    });

}

const arrivalForecastPerSegment = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/arrival-forecast-per-segment', formData)
    });

}

const downloadArrivalForecastPerSegment = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/download-arrival-forecast-per-segment', formData, {responseType: 'blob'})
        .then(({data}) => {
            const downloadUrl = window.URL.createObjectURL(new Blob([data]));
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', `Arrival-Forecast-Per-Segment.xlsx`); //any other extension
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });

}

const revenueReport = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/revenue-report', formData)
    });
}

const revenueReportDownload = (start_date, end_date) => {

    Http.get(`/api/booking/reports/revenue-report/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        if (data) {
            const downloadUrl = window.URL.createObjectURL(new Blob([data]));
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', `Revenue Report-${start_date}-${end_date}.xlsx`); //any other extension
            document.body.appendChild(link);
            link.click();
            link.remove();
        }
    });
}

const revenuePerMOP = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/revenue-per-mop', formData)
    });
}

const getSDMBGolfCartConsumption = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/sdmb-golf-cart-consumption', formData)
    });

}

const getSDMBGolfPlayConsumption = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/report/sdmb-golf-play-consumption', formData)
    });

}

const getSDMBBookingConsumption = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/reports/sdmb-booking-consumption', formData)
    });

}

const sdmbBookingConsumption = (start_date, end_date) => {  
    
    return useQuery(['reports', 'sdmb-booking-consumption', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/booking/reports/sdmb-booking-consumption/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const sdmbBookingConsumptionDownload = (start_date, end_date) => {

    Http.get(`/api/booking/reports/sdmb-booking-consumption/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        if (data) {
            const downloadUrl = window.URL.createObjectURL(new Blob([data]));
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', `SDMB-Booking-Consumption-Report-${start_date}-${end_date}.xlsx`); //any other extension
            document.body.appendChild(link);
            link.click();
            link.remove();
        }
    });

}

const getSDMBSalesRoom = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/reports/sdmb-sales-room', formData)
    });

}

const sdmbSalesRoom = (start_date, end_date) => {  
    
    return useQuery(['reports', 'sdmb-sales-room', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/booking/reports/sdmb-sales-room/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const sdmbSalesRoomDownload = (start_date, end_date) => {

    Http.get(`/api/booking/reports/sdmb-sales-room/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        // if (data) {
            const downloadUrl = window.URL.createObjectURL(new Blob([data]));
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', `SDMB-Sales-Room-Accommodation-Report-${start_date}-${end_date}.xlsx`); //any other extension
            document.body.appendChild(link);
            link.click();
            link.remove();
        // }
    });
}

export default {
    //General Reports
    bookingsWithInclusions,
    arrivalForecast,
    guestArrivalStatus,
    dtt,
    dttRevenue,
    commercialSales,
    commercialSalesDownload,

    arrivalForecastPerSegment,
    downloadArrivalForecastPerSegment,

    revenueReport,
    revenueReportDownload,
    
    revenuePerMOP,

    //SDMB Reports
    getSDMBGolfCartConsumption,
    getSDMBGolfPlayConsumption,

    dailyBookingsPerSD,
    dailyBookingsPerSDDownload,

    getSDMBBookingConsumption,
    sdmbBookingConsumption,
    sdmbBookingConsumptionDownload,

    getSDMBSalesRoom,
    sdmbSalesRoom,
    sdmbSalesRoomDownload,
}