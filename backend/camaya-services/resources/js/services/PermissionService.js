import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const list = () => {

        return useQuery("permissions", async () => {
            const { data } = await Http.get(`/api/admin/permissions`);
            return data;
        }, {
            refetchOnWindowFocus: false,
            // refetchOnMount: false,
        });

}

const listByRole = (role) => {

        return useQuery(["permissions-by-role", role], async () => {
            const { data } = await Http.get(`/api/admin/permissions-by-role/${role}`);
            return data;
        }, {
            enabled: role,
            refetchOnWindowFocus: false,
            // refetchOnMount: false,
        });

}

const changeRolePermissions = () => {

        return useMutation(async formData => {
            try { 
                return await Http.post('/api/admin/change-role-permissions', formData)
            } catch (e) {
                return Promise.reject(e);
            }
        });

}

const create = () => {

    return useMutation(async formData => {
        try {
            return await Http.post('/api/admin/permission/create', formData)
        } catch (e) {
            return Promise.reject(e);
        }
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
    listByRole,
    changeRolePermissions,
    create,
    // item
}