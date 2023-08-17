import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const list = () => {

        return useQuery("roles", async () => {
            const { data } = await Http.get(`/api/admin/roles`);
            return data;
        }, {
            refetchOnWindowFocus: false,
            // refetchOnMount: false,
        });

}


const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/admin/role/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

// const item = (id) => {
//     return useQuery(["payment", id], async () => {
//             const { data } = await Http.get(`/api/payment/${id}`);
//             return data;
//         }, {
//             enabled: id,
//         });
// }

export default {
    list,
    create,
    // item
}