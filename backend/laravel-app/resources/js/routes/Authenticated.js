import React from 'react'
import {Route, Redirect} from 'react-router-dom'
import {connect} from 'react-redux'
import App from "app"

const AuthenticatedRoute = ({component: Component, isAuthenticated, dispatch, ...rest}) => (
    <Route {...rest} render={props => (
        isAuthenticated ? (
            <App dispatch={dispatch} isAuthenticated={isAuthenticated}>
                <Component {...props}  dispatch={dispatch} title={rest.title} name={rest.name}/>
            </App>
        ) : (
            <Redirect to={{
                pathname: '/',
                state: {from: props.location}
            }}/>
        )
    )}/>
);


const mapStateToProps = (state) => {
    return {
        isAuthenticated: state.Auth.isAuthenticated,
    }
};

const mapDispatchToProps = (dispatch) => {
    return {
        dispatch: dispatch
    }
};

export default connect(mapStateToProps, mapDispatchToProps)(AuthenticatedRoute);
