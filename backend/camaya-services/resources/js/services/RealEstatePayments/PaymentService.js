import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'


const list = () => {

    return useQuery("customers", async () => {
        const { data } = await Http.get(`/api/real-estate-payments/list`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const paymentDetailsForPayMaya = () => {

    return useMutation(async formData => {
        return await Http.post('/api/real-estate-payments/paymaya/payment-details', formData)
    });

}

const setupPayMayaWebhook = () => {

    return useMutation(async formData => {
        return await Http.post('/api/real-estate-payments/paymaya/setup-webhook', formData)
    });

}

const paymentVerification = () => {
    return useMutation(async formData => {
        return await Http.post('/api/real-estate-payments/payment-verification', formData)
    });
}

const realestatePaymentactivityLogs = (reservation_number) => {
    return useQuery("real-estate-payment-activity-logs", async () => {
        const { data } = await Http.get(`/api/sales-admin-portal/real-estate-payment-activity-logs`);
        return data;
    }, {
        refetchOnWindowFocus: false,
    });
}

const exportUnidentifiedReport = () => {

    return useMutation(async formData => {
        return await Http.post('/api/sales-admin-portal/export-unidentified-report', formData, {
            responseType: 'blob'
        })
    });

}

const filterPaymentLists = () => {

    return useMutation(async formData => {
        return await Http.post('/api/real-estate-payments/filter-lists', formData)
    });

}

export default {
    list,
    paymentDetailsForPayMaya,
    setupPayMayaWebhook,
    paymentVerification,
    realestatePaymentactivityLogs,
    exportUnidentifiedReport,
    filterPaymentLists
}