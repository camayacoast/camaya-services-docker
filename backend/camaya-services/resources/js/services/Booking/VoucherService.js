import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const create = () => {

        return useMutation(async formData => {
            return await Http.post('/api/booking/voucher/create', formData)
        });

}

const list = () => {

    return useQuery("vouchers", async () => {
        const { data } = await Http.get(`/api/booking/vouchers`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const deleteImage = () => {
    return useMutation(async id => {
        try {
            return await Http.delete(`/api/booking/voucher/image/${id}`);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const imageUploadRemove = () => {
    return useMutation(async formData => {
        try {
            return await Http.post('/api/booking/voucher/image-upload-remove', formData);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

const generatedList = () => {

    return useQuery("generated-vouchers", async () => {
        const { data } = await Http.get(`/api/booking/voucher/generated-vouchers`);
        return data;
    },{
        refetchOnWindowFocus: false,
    });

}

const generate = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/voucher/generate', formData)
    });

}

const changeStatus = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/voucher/change-status', formData)
    });

}

const changePaymentStatus = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/voucher/change-payment-status', formData)
    });

}

const changePaidAt = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/voucher/change-paid-at', formData)
    });

}

const changeModeOfPayment = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/voucher/change-mode-of-payment', formData)
    });

}

const updateVoucherStub = () => {

    return useMutation(async formData => {
        return await Http.post('/api/booking/voucher/update-voucher-stub', formData)
    });

}

const resendVoucherConfirmation = () => {
    return useMutation(async data => {
        try {
            return await Http.post('/api/booking/resend-voucher-confirmation', data);
        } catch (error) {
            return Promise.reject(error);
        }
    });
}

export default {
    create,
    list,
    deleteImage,
    imageUploadRemove,
    generatedList,
    generate,
    changeStatus,
    changePaymentStatus,
    changePaidAt,
    changeModeOfPayment,
    updateVoucherStub,
    resendVoucherConfirmation,
}