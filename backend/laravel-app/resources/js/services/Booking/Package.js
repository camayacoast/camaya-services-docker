import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/booking/package/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const update = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/package/update', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

// const list = () => {

//     return useQuery("packages", async () => {
//         const { data } = await Http.get(`/api/booking/packages`);
//         return data;
//     },{
//         refetchOnWindowFocus: false,
//     });

// }

const list = (date, flag = 0) => {

    let url = `/api/booking/packages`;

    if (flag == 0) {
        url = `/api/booking/packages?inventory=1`;
    }

    if (date && date.length) {
        url = `/api/booking/packages?start_date=${date[0].format('Y-MM-DD')}&end_date=${date[1].format('Y-MM-DD')}`;
    }

    return useQuery("packages", async () => {
        const { data } = await Http.get(url);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const deleteImage = () => {
    return useMutation(async id => {
        try {
            return await Http.delete(`/api/booking/package/image/${id}`);
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