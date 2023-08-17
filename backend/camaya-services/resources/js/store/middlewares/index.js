import {applyMiddleware} from 'redux'

import ReduxThunk from 'redux-thunk'

import AuthMiddleware from './AuthMiddleware'
// import CustomMiddleware from './CustomMiddleware'

export default applyMiddleware(
            ReduxThunk,
            AuthMiddleware,
            // CustomMiddleware
        );