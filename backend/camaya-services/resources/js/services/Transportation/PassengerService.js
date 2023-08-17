import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'



const updatePassengerStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/passenger-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const getPassengerListByScheduleId = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/transportation/schedule/passenger-list-by-schedule-id', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

export default {
    updatePassengerStatus,
    getPassengerListByScheduleId,
}