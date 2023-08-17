import React from 'react'
import {Route} from 'react-router-dom'
import {connect} from 'react-redux'
import App from 'app'


const PublicRoute = ({component: Component, dispatch, ...rest}) => (
    <Route {...rest} render={props => (
        <App dispatch={dispatch}>
            <Component {...props} {...rest}/>
        </App>
    )}/>
);


export default connect()(PublicRoute);
