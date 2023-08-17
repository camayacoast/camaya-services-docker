const CustomMiddleware = store => next => action => {
    console.log("Custom Middleware triggered:", action);
    next(action);
}

export default CustomMiddleware;