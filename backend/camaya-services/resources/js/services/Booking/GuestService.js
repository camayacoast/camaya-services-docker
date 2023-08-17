import Http from 'utils/Http'
import moment from 'moment-timezone'
import { useQuery, useMutation } from 'react-query'


const list = (date) => {

    if (date) {
        return useQuery("guests", async () => {
            const { data } = await Http.get(`/api/booking/guests?date=${moment(date).format('YYYY-MM-DD')}`);
            return data;
        });
    } else {
        return useQuery("guests", async () => {
            const { data } = await Http.get(`/api/booking/guests`);
            return data;
        });
    }

}

const hotelGuestList = (date) => {

    

    if (date) {
        return useQuery("hotel-guests", async () => {
            const { data } = await Http.get(`/api/booking/hotel-guests?date=${moment(date).format('YYYY-MM-DD')}`);
            return data;
        });
    } else {
        return useQuery("hotel-guests", async () => {
            const { data } = await Http.get(`/api/booking/hotel-guests`);
            return data;
        });
    }

}

const addGuestPass = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/guest/add-guest-pass', formData)
    });

}

const updateStatus = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/guest/update/status', formData)
    });

}

const deleteGuest = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/guest/delete', formData)
    });

}

export default {
    list,
    hotelGuestList,
    addGuestPass,
    updateStatus,
    deleteGuest
}