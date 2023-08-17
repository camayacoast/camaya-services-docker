import Http from 'utils/Http'

import * as action from 'store/actions'
import ability from 'utils/casl/ability'

const checkAuth = (token) => {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('token')}`;

            Http.get(`/api/user`)
            .then(res => {
                // console.log(res.data.permissions);
                ability.update(res.data.permissions);
                dispatch(action.authCheck(res.data));
                return resolve(res.data);
            })
            .catch((res) => {
                dispatch(action.authCheck(res));
                if (res.error == 'token_expired') {
                    // Ask user to re-login again
                    dispatch(action.authCheck(res));
                }
                return reject(res);
            })
        })
            
    )
}

const tryLogin = (credentials) => {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post(`/api/login`, credentials)
            .then(res => {
                if (res.data.token) {
                    console.log(res.data.permissions);
                    ability.update(res.data.permissions);
                    dispatch(action.authLogin(res.data));
                }
                return resolve(res.data);
            })
            .catch((res) => {
                return reject(res);
            })
        })
            
    )
}

const register = (credentials) => {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post(`/api/register`, credentials)
            .then(res => {
                return resolve(res.data);
            })
            .catch((res) => {
                return reject(res);
            })
        })
            
    )
}

const resetPassword = (data) => {

    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post(`/api/password/reset`, data)
            .then(res => {
                return resolve(res.data);
            })
            .catch((res) => {
                return reject(res);
            })
        })
            
    )
}

const changePassword = data => {
    return dispatch => (
        new Promise((resolve, reject) => {
            Http.post(`/api/password/change`, data)
            .then(res => {
                return resolve(res.data);
            })
            .catch((res) => {
                return reject(res);
            })
        })
            
    )
}

const logout = () => {
    return dispatch => (
        dispatch(action.authLogout())
    )
}

export default {
        checkAuth,
        tryLogin,
        logout,
        register,
        resetPassword,
        changePassword
    }