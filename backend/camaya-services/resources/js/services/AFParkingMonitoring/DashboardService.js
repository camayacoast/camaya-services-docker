import Http from 'utils/Http'
import { useQuery, useMutation } from 'react-query'

const data = () => {

  return useQuery("af-parking-monitoring-dashboard-data", async () => {
    const { data } = await Http.get(`/api/af-parking-monitoring/dashboard`);
    return data;
  });
  
}

const mode = async (mode, callback) => {

  await Http.get(`/api/af-parking-monitoring/mode/${mode}`);
  callback();
  
}


export default {
    data,
    mode,
}