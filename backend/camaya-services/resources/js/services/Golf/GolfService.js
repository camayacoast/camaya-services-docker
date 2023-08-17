import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'
import moment from 'moment-timezone'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/golf/tee-time/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

// const update = () => {

//     return useMutation(async formData => {
//         try {
//             return await Http.post('/api/hotel/property/update', formData);
//         } catch (error) {
//             return Promise.reject(error);
//         }
//     });

// }

const teeTimeScheduleList = () => {

    return useQuery("tee-time-schedules", async () => {
        const { data } = await Http.get(`/api/golf/tee-time-schedules`);
        return data;
    });

}

// const deleteImage = () => {
//     return useMutation(async id => {
//         try {
//             return await Http.delete(`/api/hotel/property/image/${id}`);
//         } catch (error) {
//             return Promise.reject(error);
//         }
//     });
// }

const arrivalSummary = (date) => {

    if (date) {
        return useQuery("golf-arrival-summary", async () => {
            const { data } = await Http.get(`/api/golf/arrival-summary?date=${moment(date).format('YYYY-MM-DD')}`);
            return data;
        });
    } else {
        return useQuery("golf-arrival-summary", async () => {
            const { data } = await Http.get(`/api/golf/arrival-summary`);
            return data;
        });
    }

}

const statusChange = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/golf/tee-time/status-change', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const allocationUpdate = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/golf/tee-time/allocation-update', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}


export default {
    create,
    teeTimeScheduleList,
    arrivalSummary,
    statusChange,
    allocationUpdate
}