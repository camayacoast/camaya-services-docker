import React from 'react'
import {connect} from 'react-redux'

function RegistrationForm(props) {

    return (
        <>Registration</>
    )
}

const mapStateToProps = (state) => {
    return {
        isAuthenticated : state.Auth.isAuthenticated,
    }
};

export default connect(mapStateToProps)(RegistrationForm)