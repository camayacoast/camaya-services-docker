import * as ActionTypes from './types'

// Auth
export function authLogin(payload) {
    return {
        type: ActionTypes.AUTH_LOGIN,
        payload
    }
}

export function authLogout(){
    return {
        type: ActionTypes.AUTH_LOGOUT
    }
}

export function authCheck(payload) {
    return {
        type: ActionTypes.AUTH_CHECK,
        payload
    }
}


export function updateBookingTabs(payload) {
    return {
        type: ActionTypes.UPDATE_BOOKING_TABS,
        payload
    }
}

export function updateTrippingTabs(payload) {
    return {
        type: ActionTypes.UPDATE_TRIPPING_TABS,
        payload
    }
}

export function updateTrippingPaneActiveKey(payload) {
    return {
        type: ActionTypes.UPDATE_TRIPPING_PANE_ACTIVE_KEY,
        payload
    }
}

export function updateBookingPaneActiveKey(payload) {
    return {
        type: ActionTypes.UPDATE_BOOKING_PANE_ACTIVE_KEY,
        payload
    }
}