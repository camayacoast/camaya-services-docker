import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import AFParkingLayout from 'layouts/AFParkingMonitoring'

const DashboardComponent = React.lazy( () => import('components/AFParkingMonitoring/Dashboard'))

import PageNotFound from 'common/PageNotFound'
import { Typography } from 'antd'
import { CarOutlined } from '@ant-design/icons' 



export default function Page(props) {
    return (
        <AFParkingLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><CarOutlined className="mr-2"/>AF Parking Monitoring</Typography.Title>
                    <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                    <Suspense><DashboardComponent/></Suspense>
            </div>
        </AFParkingLayout>
    )
}