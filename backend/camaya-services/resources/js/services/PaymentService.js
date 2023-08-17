import Http from 'utils/Http'
import { useQuery } from 'react-query'

const list = () => {

        return useQuery("payments", async () => {
            const { data } = await Http.get(`/api/payments`);
            return data;
        });

}

const item = (id) => {
    return useQuery(["payment", id], async () => {
            const { data } = await Http.get(`/api/payment/${id}`);
            return data;
        }, {
            enabled: id,
        });
}

export default {
    list,
    item
}