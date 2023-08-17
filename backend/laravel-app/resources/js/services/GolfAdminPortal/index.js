import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'


const payments = () => {

    return useQuery("golf-payments", async () => {
        const { data } = await Http.get(`/api/golf-admin-portal/payments`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const updatePaymentRecord = () => {
    

        return useMutation(async formData => {
            return await Http.post('/api/golf-admin-portal/update-payment-record', formData)
        });

}

const saveSource = () => {

    return useMutation(async formData => {
        return await Http.post('/api/golf-admin-portal/save-payment-transaction-source', formData)
    });

}



export default {
    payments,
    updatePaymentRecord,
    saveSource,
}