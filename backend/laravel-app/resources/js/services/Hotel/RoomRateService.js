import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/hotel/room-rate/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const update = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/room-rate/update', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const list = () => {

    return useQuery("room-rates", async () => {
        const { data } = await Http.get(`/api/hotel/room-rates`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const changeStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-rate/update-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const changeAllowedDays = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-rate/update-allowed-days', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const changeExcludedDays = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-rate/update-excluded-days', formData);
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
    changeAllowedDays,
    changeExcludedDays,
}