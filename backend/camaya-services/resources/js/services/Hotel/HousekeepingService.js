import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'


const updateRoomStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room/update/room-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

export default {
    updateRoomStatus
}