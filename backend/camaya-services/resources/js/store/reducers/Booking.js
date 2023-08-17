import * as ActionTypes from 'store/actions/types'

const initialState = {
    tabs : [],
    trippingTabs: [],
    paneActiveKey: 'my-bookings',
    trippingPaneActiveKey: 'active-bookings',
};

const Booking = (state = initialState,{type, payload = null}) => {
    switch (type) {
        case ActionTypes.UPDATE_BOOKING_TABS:
            return updateTabs(state, payload);
        case ActionTypes.UPDATE_BOOKING_PANE_ACTIVE_KEY:
                return updatePaneActiveKey(state, payload);
        case ActionTypes.UPDATE_TRIPPING_TABS:
            return updateTrippingTabs(state, payload);
        case ActionTypes.UPDATE_TRIPPING_PANE_ACTIVE_KEY:
            return updateTrippingPaneActiveKey(state, payload);
        default:
            return state;
    }
};

const updateTabs = (state, payload) => {

    state = Object.assign({}, state, {
        tabs: payload,
    });

    return state;
};

const updatePaneActiveKey = (state, payload) => {

    state = Object.assign({}, state, {
        paneActiveKey: payload,
    });

    return state;
};

const updateTrippingTabs = (state, payload) => {

    state = Object.assign({}, state, {
        trippingTabs: payload,
    });

    return state;
};

const updateTrippingPaneActiveKey = (state, payload) => {

    state = Object.assign({}, state, {
        trippingPaneActiveKey: payload,
    });

    return state;
};

export default Booking;