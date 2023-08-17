import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const updateUsableAt = () => {

        return useMutation(async formData => {
            return await Http.put('/api/booking/passes/update/usable-at', formData)
        });

}

const updateExpiresAt = () => {

    return useMutation(async formData => {
        return await Http.put('/api/booking/passes/update/expires-at', formData)
    });

}

const deletePass = () => {

    return useMutation(async formData => {
        return await Http.put('/api/auto-gate/passes/delete', formData)
    });

}



export default {
    updateUsableAt,
    updateExpiresAt,
    deletePass
}