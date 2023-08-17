import Http from 'utils/Http'
import moment from 'moment'
import { useQuery, useMutation } from 'react-query'

const list = (start, end, customerBookingCode) => {
    // console.log(start, end);
    if (start && end) {
        return useQuery(["rooms-reservations"], async () => {
            const { data } = await Http.get(`/api/hotel/room-reservations/${start && moment(start).format('YYYY-MM-DD')}/${end && moment(end).format('YYYY-MM-DD')}?customerBookingCode=`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    }

}

const dashboard = () => {
    
    return useQuery("room-reservations-dashboard", async () => {
        const { data } = await Http.get(`/api/hotel/room-reservation/dashboard`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const updateRoomReservationStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-reservation/update/status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const switchRoom = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-reservation/switch-room', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const roomBlocking = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-reservation/room-blocking', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const cancelBlocking = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-reservation/cancel-room-blocking', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const getLastAvailableReservationDate = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-reservation/get-last-available-reservation-date', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const availableRoomList = (arrival_date, departure_date) => {

    return useQuery("available-room-list", async () => {
        const { data } = await Http.get(`/api/hotel/room-reservation/available-room-list?arrival_date=${arrival_date}&departure_date=${departure_date}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const addRoomToBooking = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-reservation/add-room-to-booking', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const getAvailableRoomsForDate = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-reservation/get-available-rooms-for-date', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const roomTransfer = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-reservation/room-transfer', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateCheckInTime = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/update-check-in-time', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const roomReservationCalendar = (start_date, end_date) => {
    return useQuery("hotel-occupancy-dashboard", async () => {
        const { data } = await Http.get(`/api/hotel/room-reservation-calendar/${start_date}/${end_date}`);
        return data;
    }, {
        refetchOnWindowFocus: false,
    });

}

export default {
    list,
    dashboard,
    updateRoomReservationStatus,
    switchRoom,
    roomBlocking,
    cancelBlocking,
    getLastAvailableReservationDate,
    availableRoomList,
    addRoomToBooking,
    getAvailableRoomsForDate,
    roomTransfer,
    updateCheckInTime,

    roomReservationCalendar,
}