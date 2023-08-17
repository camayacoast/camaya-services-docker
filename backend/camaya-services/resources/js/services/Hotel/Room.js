import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/hotel/room/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const update = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room/update', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const list = (calendarView = false) => {

    return useQuery("rooms", async () => {
        const { data } = await Http.get(`/api/hotel/rooms${calendarView ? '?calendarView=true' : ''}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const changeStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room/update-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateRoomStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room/update/room-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

export default {
    create,
    update,
    list,
    changeStatus,
    updateRoomStatus
}