import React from "react";

import Loading from './Loading'


const Fallback = (props) => (
    <div>
        <Loading />
        <div>Loading {props.title} Page</div>
    </div>
)

export default Fallback;