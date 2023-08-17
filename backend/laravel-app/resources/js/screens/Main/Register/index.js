import React, {useState} from "react";
import RegistrationForm from 'components/Registration/Form';

import { Modal, Button, PageHeader, Row, Col, Carousel, Grid, Typography} from 'antd';
const { useBreakpoint } = Grid;


function Page(props) {

    const screens = useBreakpoint();

   return (
    <div style={{width: '100%', height: '100vh', overflow:'hidden'}}>
        <Row justify="center" style={{ padding: 16 }}>
            <Col xl={8} xs={0} style={{display: 'flex', justifyContent: 'center', flexDirection: 'column', alignItems: 'center'}}>
                ...
            </Col>
            <Col xl={16} xs={24}>
                <div style={{display: 'flex', width: '100%', justifyContent: 'center', alignItems: 'center', height: screens.xl == true ? '100vh' : '80vh', overflow:'scroll'}}>
                    <div style={{width: '1000px', height: '100%', padding: 50}}>
                        <PageHeader
                            onBack={() => props.history.goBack()}
                            title="Sign up"
                            subTitle={<div></div>}
                        />
                        <RegistrationForm {...props} />
                    </div>
                </div>
            </Col>
       </Row>
    </div>
   )
}

export default Page;