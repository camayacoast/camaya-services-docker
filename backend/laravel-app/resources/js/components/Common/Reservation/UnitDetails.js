import React from 'react';

import { Row, Col, Divider, Descriptions } from 'antd'

function UnitDetails(props) {

    const reservation = props.reservation;

    return (
        <>
            <Row gutter={[48,48]}>
                <Col xl={12}>
                    <Divider orientation="left">Unit details</Divider>
                    <Descriptions bordered colon={false} size="small">
                        <Descriptions.Item span={3} label={<strong>Property type</strong>}>{reservation?.property_type}</Descriptions.Item>
                        <Descriptions.Item span={3} label={<strong>{reservation?.property_type === 'Lot' ? 'Subdivision' : 'Project' }</strong>}>{reservation?.subdivision}</Descriptions.Item>
                        <Descriptions.Item span={3} label={<strong>Block</strong>}>{reservation?.block}</Descriptions.Item>
                        <Descriptions.Item span={3} label={<strong>{reservation?.property_type === 'Lot' ? 'Lot' : 'Unit' }</strong>}>{reservation?.lot}</Descriptions.Item>
                        <Descriptions.Item span={3} label={<strong>Area</strong>}>{reservation?.area}</Descriptions.Item>
                        <Descriptions.Item span={3} label={<strong>Source</strong>}>{reservation?.source}</Descriptions.Item>
                    </Descriptions>
                </Col>
                <Col xl={12}>
                    <Divider orientation="left">Co-buyers</Divider>
                    {
                        reservation.co_buyers?.map( (item, key) => {
                            return <div key={key} style={{marginBottom: 8}}>
                                    <strong>{item.details?.first_name} {item.details?.last_name}</strong><br/>
                                        {item.details?.email}
                                </div>
                        })
                    }
                </Col>
            </Row>
        </>
    )
}

export default UnitDetails;