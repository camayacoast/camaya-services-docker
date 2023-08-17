import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            return await Http.post('/api/booking/create', formData)
        });

}

const list = (type, isTripping) => {
    // console.log(isTripping);

    let url = `/api/booking/list/${type}`;

    if (isTripping) url = `/api/booking/list/tripping/${type}`;

    return useQuery(["bookings", type], async () => {
        const {data} = await Http.get(url);
        return data;
    }, {
        // refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const viewBooking = (refno) => {
    
    if (!refno) return false;

    return useQuery(["view-booking", refno], async () => {
        const { data } = await Http.get(`/api/booking/view-booking/${refno}`);
        return data;
    }, {
        enabled: refno,
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const myBookings = () => {

    return useQuery("my-bookings", async () => {
        const { data } = await Http.get(`/api/booking/my-bookings`);
        return data;
    });

}

const cancelBooking = () => {

    return useMutation(async data => {
        try {
            return await Http.patch('/api/booking/cancel-booking', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const pendingBooking = () => {

    return useMutation(async data => {
        try {
            return await Http.patch('/api/booking/pending-booking', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const confirmBooking = () => {

    return useMutation(async data => {
        try {
            return await Http.patch('/api/booking/confirm-booking', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const searchBookings = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/search-bookings', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const resendBookingConfirmation = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/resend-booking-confirmation', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const getAllBookingTags = () => {

    return useQuery("all-booking-tags", async () => {
        const { data } = await Http.get(`/api/booking/all-booking-tags`);
        return data;
    });

}

const updateGuest = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/guests/update', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const addVehicle = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/guest_vehicles/add', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateVehicle = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/guest_vehicles/update', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const deleteVehicle = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/guest_vehicles/delete', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateBookingLabel = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/label', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const updateRemarks = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/remarks', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const updateBillingInstructions = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/billing-instructions', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}



const newNote = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/note/new', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const addGuest = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/add-guest', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const addInclusionsToBooking = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/add-inclusions-to-booking', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const removeInclusion = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/remove-inclusion', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateAdditionalEmails = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/additional-emails', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateBookingTags = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/tags', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updatePrimaryGuest = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/primary-guest', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateAutoCancelDate = () => {

    return useMutation(async data => {
        try {
            return await Http.put('/api/booking/update/auto-cancel-date', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const getLogs = (booking_reference_number) => {

    return useQuery("logs", async () => {
        const { data } = await Http.get(`/api/booking/logs?booking_reference_number=${booking_reference_number}`);
        return data;
    });

}

const addFerryToBooking = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/add-ferry-to-booking', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const addFerryToGuests = () => {

    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/add-ferry-to-guests', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const teeTimeSchedules = () => {

    return useMutation(async formData => {
        return await Http.post('/api/golf/website-tee-time-schedules', formData, {
            // headers: {"Access-Control-Allow-Origin": "*"}
        })
    });

}

const updateBookingDate = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/update/booking-date', formData)
    });

}

const downloadBoardingPassOnePDF = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/download-boarding-pass-one-pdf', formData, {
            responseType: 'blob'
        })
    });

}

const downloadBookingConfirmation = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/download-booking-confirmation', formData, {
            responseType: 'blob'
        })
    });
}

const agentList = (type) => {

    let url = `/api/booking/agent-list/`;

    return useQuery(["agent-list", type], async () => {
        const {data} = await Http.get(url);
        return data;
    }, {
        refetchOnWindowFocus: false,
        refetchOnMount: false,
    });

}

export default {
    create,
    list,
    myBookings,
    viewBooking,
    cancelBooking,
    pendingBooking,
    confirmBooking,
    searchBookings,
    resendBookingConfirmation,
    getAllBookingTags,
    addVehicle,
    updateVehicle,
    deleteVehicle,
    updateBookingLabel,
    updateRemarks,
    updateGuest,
    newNote,
    addGuest,
    addInclusionsToBooking,
    removeInclusion,
    updateAdditionalEmails,
    updateBookingTags,
    updatePrimaryGuest,
    updateAutoCancelDate,
    getLogs,
    addFerryToBooking,
    addFerryToGuests,
    teeTimeSchedules,
    updateBookingDate,
    agentList,
    downloadBoardingPassOnePDF,
    downloadBookingConfirmation,
    updateBillingInstructions,
}