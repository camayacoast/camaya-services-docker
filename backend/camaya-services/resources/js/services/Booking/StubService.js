import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            return await Http.post('/api/booking/stub/create', formData)
        });

}

const list = () => {

    return useQuery("stubs", async () => {
        const { data } = await Http.get(`/api/booking/stubs`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const updateStubCategory = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/stub/update/category', formData)
    });

}

export default {
    create,
    list,
    updateStubCategory,
}