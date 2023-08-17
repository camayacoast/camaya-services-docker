import Http from 'utils/Http'
import moment from 'moment'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/hotel/room-type/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const list = (dates) => {

    if (dates && dates.length) {
        return useQuery("rooms-with-availability", async () => {
            const { data } = await Http.get(`/api/hotel/room-types-with-dates/${dates && moment(dates[0]).format('YYYY-MM-DD')}/${dates && moment(dates[1]).format('YYYY-MM-DD')}`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    } else {
        return useQuery("room-types", async () => {
            const { data } = await Http.get(`/api/hotel/room-types`);
            return data;
        },{
            refetchOnWindowFocus: false,
        });
    }

}

const listForRoomAllocation = (date) => {

    return useQuery("room-types", async () => {
        const { data } = await Http.get(`/api/hotel/room-types${date ? '?date='+date: ''}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const typeOnly = () => {

    return useQuery("room-types-only", async () => {
        const { data } = await Http.get(`/api/hotel/room-types-only`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const listPerEntity = () => {

    return useQuery("rooms-types-per-entity", async () => {
        const { data } = await Http.get(`/api/hotel/rooms-types-per-entity`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const changeStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-type/update-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

export default {
    create,
    list,
    listPerEntity,
    listForRoomAllocation,
    typeOnly,
    changeStatus
}