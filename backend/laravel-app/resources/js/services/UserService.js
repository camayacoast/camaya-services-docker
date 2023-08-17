import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const list = () => {

        return useQuery("users", async () => {
            const { data } = await Http.get(`/api/admin/users`);
            return data;
        }, {
            refetchOnWindowFocus: false,
            // refetchOnMount: false,
        });

}

const allAgentList = () => {
    
    // 'POC Agent', 'Property Consultant', 'Sales Director', 'Sales Manager'

    return useQuery("agent-users", async () => {
        const { data } = await Http.get(`/api/admin/all-agent-users`);
        return data;
    }, {
        refetchOnWindowFocus: false,
        // refetchOnMount: false,
    });

}

const create = () => {

        return useMutation(async formData => {
            return await Http.post('/api/admin/create', formData)
        });

}

const updateUserType = () => {

    return useMutation(async formData => {
        return await Http.put('/api/admin/user/update-user-type', formData)
    });

}

const updateUserRole = () => {

    return useMutation(async formData => {
        return await Http.put('/api/admin/user/update-user-role', formData)
    });

}

const changePassword = () => {

    return useMutation(async formData => {
        return await Http.put('/api/admin/user/change-password', formData)
    });

}

const resetPassword = () => {

    return useMutation(async formData => {
        return await Http.post('/api/admin/user/reset-password', formData)
    });

}

const updateUserFirstName = () => {

    return useMutation(async formData => {
        return await Http.put('/api/admin/user/update-user-first-name', formData)
    });

}

const updateUserLastName = () => {

    return useMutation(async formData => {
        return await Http.put('/api/admin/user/update-user-last-name', formData)
    });

}

// const item = (id) => {
//     return useQuery(["payment", id], async () => {
//             const { data } = await Http.get(`/api/payment/${id}`);
//             return data;
//         }, {
//             enabled: id,
//         });
// }

export default {
    list,
    create,
    updateUserType,
    updateUserRole,
    changePassword,
    resetPassword,
    updateUserFirstName,
    updateUserLastName,
    allAgentList,
    // item
}