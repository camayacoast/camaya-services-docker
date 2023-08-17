
const AuthMiddleware = store => next => action => {

    if (action.type === 'AUTH_CHECK') {
        
        const {Auth} = store.getState();

        if (!Auth.isAuthenticated) {
            // Redirect to login
        }
    }

    next(action);
}

export default AuthMiddleware;