import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/hotel/room-allocation/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const list = () => {

    return useQuery("room-allocations", async () => {
        const { data } = await Http.get(`/api/hotel/room-allocations`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const changeRoomAllocationStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-allocation/update-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateRoomAllocationAllowedRoles = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-allocation/update/allowed-roles', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateRoomAllocation = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/hotel/room-allocation/update/allocation', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const allocationPerDate = (start_date, end_date) => {

    return useQuery("room-allocations-per-date", async () => {
        const { data } = await Http.get(`/api/hotel/room-allocation-per-date?start_date=${start_date}&end_date=${end_date}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

export default {
    create,
    list,
    changeRoomAllocationStatus,
    updateRoomAllocationAllowedRoles,
    updateRoomAllocation,
    allocationPerDate,
}