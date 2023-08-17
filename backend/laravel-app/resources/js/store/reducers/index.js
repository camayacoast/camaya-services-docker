import {combineReducers} from 'redux'
import Auth from './Auth'
import Booking from './Booking'

const rootReducer = combineReducers({Auth, Booking});

export default rootReducer;