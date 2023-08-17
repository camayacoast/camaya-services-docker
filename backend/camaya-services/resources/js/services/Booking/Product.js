import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/booking/product/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const update = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/product/update', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const list = () => {

    return useQuery("products", async () => {
        const { data } = await Http.get(`/api/booking/products`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const imageUploadRemove = () => {
    return useMutation(async formData => {
        try {
            return await Http.post('/api/booking/product/image-upload-remove', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const deleteImage = () => {
    return useMutation(async id => {
        try {
            return await Http.delete(`/api/booking/product/image/${id}`);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

export default {
    create,
    update,
    list,
    imageUploadRemove,
    deleteImage,
}