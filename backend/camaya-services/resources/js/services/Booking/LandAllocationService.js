import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/booking/land-allocation/create', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const list = (date) => {

    return useQuery("land-allocations", async () => {
        const { data } = await Http.get(`/api/booking/land-allocations?date=${date}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const changeLandAllocationStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/land-allocation/update-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateLandAllocationAllowedRoles = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/land-allocation/update/allowed-roles', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateLandAllocationAllowedUsers = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/land-allocation/update/allowed-users', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateLandAllocation = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/land-allocation/update/allocation', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const allocationPerDate = (start_date, end_date) => {

    return useQuery("land-allocations-per-date", async () => {
        const { data } = await Http.get(`/api/booking/land-allocation-per-date?start_date=${start_date}&end_date=${end_date}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

export default {
    create,
    list,
    changeLandAllocationStatus,
    updateLandAllocationAllowedRoles,
    updateLandAllocationAllowedUsers,
    updateLandAllocation,
    allocationPerDate,
}