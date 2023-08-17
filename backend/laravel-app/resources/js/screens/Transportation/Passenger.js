import React from 'react'
import TransportationLayout from 'layouts/Transportation'
import PassengerComponent from 'components/Transportation/Passenger'

import { Typography} from 'antd'


export default function Page(props) {

    return <TransportationLayout {...props}>
        <div className="fadeIn">
            <Typography.Title level={2}>Passengers</Typography.Title>

            <PassengerComponent />
        </div>
    </TransportationLayout>
}