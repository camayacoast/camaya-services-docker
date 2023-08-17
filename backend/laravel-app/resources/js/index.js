require('./bootstrap');

import React from "react";
import {render} from "react-dom";

import {Provider} from 'react-redux';
import {PersistGate} from 'redux-persist/integration/react';
import {store, persistor} from 'store';

import { AbilityContext } from 'utils/casl/ability-context'
import ability from 'utils/casl/ability'

import AuthService from "services/AuthService";

// Check if authenticated
const token = localStorage.getItem('token');

if (token) { 
    store.dispatch(AuthService.checkAuth(token));
}

import Routes from 'routes';

render(
    <Provider store={store}>
        <PersistGate loading={<div>Loading ...</div>} persistor={persistor}>
            <AbilityContext.Provider value={ability}>
                <Routes />
            </AbilityContext.Provider>
        </PersistGate>
    </Provider>,
    document.getElementById('root')
);