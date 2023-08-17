import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/transportation/location/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const list = () => {

    return useQuery("locations", async () => {
        const { data } = await Http.get(`/api/transportation/locations`);
        return data;
    });

}

export default {
    create,
    list,
}