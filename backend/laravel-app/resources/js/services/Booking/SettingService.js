import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/booking/setting/create', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}


const data = () => {

    return useQuery("settings-data", async () => {
        const { data } = await Http.get(`/api/booking/settings`);
        return data;
    });

}

export default {
    create,
    data,
}