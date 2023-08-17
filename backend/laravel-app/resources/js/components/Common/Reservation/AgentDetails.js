import React from 'react';

import { Row, Col } from 'antd'

function AgentDetails(props) {

    const reservation = props.reservation;

    return (
        <>
            <Row gutter={[48,48]}>
                <Col xl={3}><strong>Agent</strong></Col>
                <Col xl={5}>
                    <strong>{reservation.agent?.first_name} {reservation.agent?.last_name}</strong><br/>
                    {reservation.agent?.email}<br/>
                    Team: {reservation.agent?.team_member_of ? reservation.agent?.team_member_of?.team.name : 'No team'}<br/>
                    <div>
                        SM: {reservation.sales_manager?.first_name} {reservation.sales_manager?.last_name}
                    </div>
                    <div>
                        SD: {reservation.sales_director?.first_name} {reservation.sales_manager?.last_name}
                    </div>
                </Col>
                <Col xl={4}><strong>Refer-A-Friend</strong></Col>
                <Col xl={12}>
                    <strong>Existing client: </strong>{reservation.referrer?.first_name} {reservation.referrer?.last_name}<br/>
                    <strong>Property purchased: </strong>
                        {
                            reservation.referrer_property_details &&
                            <>{reservation.referrer_property_details?.subdivision} Block {reservation.referrer_property_details?.block} Lot {reservation.referrer_property_details?.lot}</>
                        }
                    <br/>
                </Col>
            </Row>
        </>
    )
}

export default AgentDetails;