import React from "react";
import {withRouter} from "react-router-dom";
import {Result, Button} from 'antd';

const Fallback = (props) => (
    <div className="fadeIn" style={{display: 'flex', flexDirection: 'row', justifyContent: 'center', alignItems: 'center', height: '100%'}}>
        <Result
            status="404"
            title="404"
            subTitle="Sorry, the page you visited does not exist."
            extra={<Button onClick={() => props.history.goBack()}type="primary">Get Back</Button>}
        />
    </div>
)

export default withRouter(Fallback);