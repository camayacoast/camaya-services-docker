import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/hotel/property/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const update = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/hotel/property/update', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const list = () => {

    return useQuery("properties", async () => {
        const { data } = await Http.get(`/api/hotel/properties`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const deleteImage = () => {
    return useMutation(async id => {
        try {
            return await Http.delete(`/api/hotel/property/image/${id}`);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}


export default {
    create,
    update,
    list,
    deleteImage,
}