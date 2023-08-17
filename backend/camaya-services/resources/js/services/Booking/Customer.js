import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            return await Http.post('/api/booking/customer/create', formData)
        });

}

const list = (isTripping = false) => {

    return useQuery("customers", async () => {
        const { data } = await Http.get(`/api/booking/customers${isTripping ? '?isTripping=true':''}`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const list2 = (isTripping = false) => {

    return useMutation(async formData => {
        return await Http.post(`/api/booking/customers-v2${isTripping ? '?isTripping=true':''}`, formData)
    });

}

const linkToUser = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/customer/link-to-user', formData)
    });

}

const updateAddress = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/customer/update/address', formData)
    });

}

const update = () => {

    return useMutation(async data => {
        return await Http.put('/api/booking/customer/update', data);
    });

}


export default {
    create,
    list,
    linkToUser,
    updateAddress,
    update,
    list2,
}