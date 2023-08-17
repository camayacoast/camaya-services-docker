import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const list = (booking_refno) => {

    return useQuery("products", async () => {
        const { data } = await Http.get(`/api/booking/invoices/${booking_refno}`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const newPayment = (formData) => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/booking/invoice/new-payment', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateDiscount = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/invoice/update/discount', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const updateInclusionDiscount = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/invoice/update/inclusion-discount', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}

const voidPayment = () => {

    return useMutation(async formData => {
        try {
            return await Http.put('/api/booking/invoice/void-payment', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });

}




export default {
    list,
    newPayment,
    updateDiscount,
    updateInclusionDiscount,
    voidPayment
}