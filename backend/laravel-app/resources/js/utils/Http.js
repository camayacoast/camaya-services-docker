import axios from 'axios'
import {store} from 'store'
import * as actions from 'store/actions'

// const version = 'v1'

axios.defaults.baseURL = process.env.API;
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;
axios.defaults.headers.common.Accept = 'application/json';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// axios.defaults.headers.common['X-CSRF-TOKEN'] = "";
// axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


// Update this interceptor this should ask to user to login again and not logout
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401 ) {
            store.dispatch(actions.authLogout())
        }
        return Promise.reject(error.response.data);
    }
);

export default axios
