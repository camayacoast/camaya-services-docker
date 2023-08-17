import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import AutoGateLayout from 'layouts/AutoGate'

const AFParkingMonitoringDashboardComponent = React.lazy( () => import('components/AutoGate/AFParkingMonitoring'))

import { Typography } from 'antd'
import { CarOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <AutoGateLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2}><CarOutlined className="mr-2"/>AF Parking Monitoring</Typography.Title>
                <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                <Suspense><AFParkingMonitoringDashboardComponent/></Suspense>
            </div>
        </AutoGateLayout>
    )
}