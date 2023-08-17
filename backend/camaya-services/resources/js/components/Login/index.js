import React from 'react';
import LoginForm from 'components/Login/Form';

import {Row, Col, Typography} from 'antd';

function Page(props) {

    React.useEffect(() => {
        return () => {
        };
    }, []);

  return (
    <>
        <Row align="middle" justify="center" style={{height: '100vh'}} gutter={[0, 0]}>
            <Col xl={5} lg={10} sm={10} xs={22}>
                <Typography.Title level={3} align="center">
                    {process.env.APP_NAME}
                    <small style={{display: 'block', opacity: '0.5'}}>Control Panel</small>
                </Typography.Title>
            </Col>
            <Col xl={5} lg={10} sm={10} xs={22}>
                <LoginForm {...props} />
            </Col>
        </Row>
    </>
  )
}

export default Page;