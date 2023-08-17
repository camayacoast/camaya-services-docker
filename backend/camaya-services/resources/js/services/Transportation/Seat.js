import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/transportation/seat/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const list = () => {

    return useQuery("seats", async () => {
        const { data } = await Http.get(`/api/transportation/seats`);
        return data;
    });

}

const updateSeatOrder = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/seat/update/order', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateAutoCheckInStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/seat/update/auto-check-in-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/seat/update/status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

export default {
    create,
    list,
    updateSeatOrder,
    updateAutoCheckInStatus,
    updateSeatStatus
}