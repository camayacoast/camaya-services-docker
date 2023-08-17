import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const generateSchedule = () => {

        return useMutation(async formData => {
            try {
                return await Http.post('/api/transportation/generate-schedules', formData);
            } catch (error) {
                return Promise.reject(error);
            }
        });

}

const list = (date) => {

    return useQuery("schedules", async () => {
        const { data } = await Http.get(`/api/transportation/schedules?date=${date}`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const updateSchedule = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/update-schedule', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatAllocationAllowedRoles = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/seat-allocation-allowed-roles', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentBookingTypes = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/seat-segment-booking-types', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentAllowedRoles = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/seat-segment-allowed-roles', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentAllowedUsers = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/seat-segment-allowed-users', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/seat-segment-status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentLink = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/seat-segment-link', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateScheduleStatus = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/schedule/update/status', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const addSeatSegment = () => {

    return useMutation(async formData => { 
        try {
            return await Http.post('/api/transportation/seat-segment/create', formData)
        } catch (error) {
            return Promise.reject(error);
        }
    });
    
}

const addSeatAllocation = () => {

    return useMutation(async formData => { 
        try {
            return await Http.post('/api/transportation/seat-allocation/create', formData)
        } catch (error) {
            return Promise.reject(error);
        }
    });
    
}

const updateSeatAllocationQuantity = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/seat-allocation/update/quantity', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentAllocated = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/seat-segment/update/allocated', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateSeatSegmentRate = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/transportation/seat-segment/update/rate', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const getAvailableCamayaTransportationSchedules = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/transportation/schedules-by-date', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const printManifest = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/transportation/schedule/print-manifest', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const passengerList = (date) => {

    return useQuery("passengers", async () => {
        const { data } = await Http.get(`/api/transportation/schedule/passengers?date=${date}`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const getAvailableTripsByBookingDate = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/transportation/schedule/available-trips-by-booking-date', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

export default {
    generateSchedule,
    list,
    updateSchedule,
    updateSeatAllocationAllowedRoles,
    updateSeatSegmentBookingTypes,
    updateSeatSegmentAllowedRoles,
    updateSeatSegmentAllowedUsers,
    updateSeatSegmentStatus,
    updateSeatSegmentLink,
    updateScheduleStatus,
    addSeatSegment,
    addSeatAllocation,
    updateSeatAllocationQuantity,
    updateSeatSegmentAllocated,
    getAvailableCamayaTransportationSchedules,
    updateSeatSegmentRate,

    passengerList,
    printManifest,

    getAvailableTripsByBookingDate,
}