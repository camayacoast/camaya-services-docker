import Http from 'utils/Http'
import { useQuery } from 'react-query'


const dailyArrival = (date, properties) => {

    return useQuery(['reports', 'daily-arrival', date], async () => {
        const { data } = await Http.get(`/api/hotel/reports/daily-arrival/${date}?sands=${properties.sands}&af=${properties.af}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const dailyArrivalDownload = (date, properties) => {

    Http.get(`/api/hotel/reports/daily-arrival/${date}/download?sands=${properties.sands}&af=${properties.af}`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Daily-Arrival-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const dailyDeparture = (date, properties) => {

    return useQuery(['reports', 'daily-departure', date], async () => {
        const { data } = await Http.get(`/api/hotel/reports/daily-departure/${date}?sands=${properties.sands}&af=${properties.af}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const dailyDepartureDownload = (date, properties) => {

    Http.get(`/api/hotel/reports/daily-departure/${date}/download?sands=${properties.sands}&af=${properties.af}`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Daily-Departure-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const inHouseGuestList = (date, properties) => {

    return useQuery(['reports', 'in-house-guest-list', date], async () => {
        const { data } = await Http.get(`/api/hotel/reports/in-house-guest-list/${date}?sands=${properties.sands}&af=${properties.af}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const inHouseGuestListDownload = (date, properties) => {

    Http.get(`/api/hotel/reports/in-house-guest-list/${date}/download?sands=${properties.sands}&af=${properties.af}`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `In-House-Guest-List-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const stayOverGuestList = (date, properties) => {

    return useQuery(['reports', 'stay-over-guest-list', date], async () => {
        const { data } = await Http.get(`/api/hotel/reports/stay-over-guest-list/${date}?sands=${properties.sands}&af=${properties.af}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const stayOverGuestListDownload = (date, properties) => {

    Http.get(`/api/hotel/reports/stay-over-guest-list/${date}/download?sands=${properties.sands}&af=${properties.af}`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Stay-Over-Guest-List-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const dttArrivalForecast = () => {

  return useQuery("rooms", async () => {
      const { data } = await Http.get(`/api/hotel/reports/dtt-arrival-forecast`);
      return data;
  },{
      refetchOnWindowFocus: false,
  });

}

const dttDailyArrival = () => {

    return useQuery("rooms", async () => {
        const { data } = await Http.get(`/api/hotel/reports/dtt-arrival-forecast`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });
  
}

const guestHistory = (date) => {

    return useQuery(['reports', 'guest-history', date], async () => {
        const { data } = await Http.get(`/api/hotel/reports/guest-history/${date}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });
  
}

const guestHistoryDownload = (date) => {

    Http.get(`/api/hotel/reports/guest-history/${date}/download`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Guest-History-${date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const hotelOccupancy = (start_date, end_date) => {    
    
    return useQuery(['reports', 'hotel-occupancy', start_date, end_date], async () => {  
        if (! start_date || ! end_date) return [];
        
        const { data } = await Http.get(`/api/hotel/reports/hotel-occupancy/${start_date}/${end_date}`);
        return data.data;
    },{
        refetchOnWindowFocus: false,
    });

}

const hotelOccupancyDownload = (start_date, end_date) => {

    Http.get(`/api/hotel/reports/hotel-occupancy/${start_date}/${end_date}/download`, {responseType: 'blob'}).then(({data}) => {
        const downloadUrl = window.URL.createObjectURL(new Blob([data]));
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.setAttribute('download', `Hotel-Occupancy-${start_date}-${end_date}.xlsx`); //any other extension
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

}

const occupancyDashboard = (month, year) => {
    return useQuery("hotel-occupancy-dashboard", async () => {
        const { data } = await Http.get(`/api/hotel/occupancy-dashboard/${month}/${year}`);
        return data;
    }, {
        refetchOnWindowFocus: false,
    });

}

export default {    
    dailyArrival,
    dailyArrivalDownload,
    dailyDeparture,
    dailyDepartureDownload,
    inHouseGuestList,
    inHouseGuestListDownload,
    stayOverGuestList,
    stayOverGuestListDownload,
    dttArrivalForecast,
    dttDailyArrival,
    guestHistory,
    guestHistoryDownload,
    hotelOccupancy,
    hotelOccupancyDownload,

    occupancyDashboard
}