import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import AutoGateLayout from 'layouts/AutoGate'

const AutoGateDashboardComponent = React.lazy( () => import('components/AutoGate/Dashboard'))

import PageNotFound from 'common/PageNotFound'
import { Typography } from 'antd'
import { DashboardOutlined } from '@ant-design/icons' 



export default function Page(props) {
    return (
        <AutoGateLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><DashboardOutlined className="mr-2"/>Auto-Gate Dashboard</Typography.Title>
                    <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                    <Suspense><AutoGateDashboardComponent/></Suspense>
            </div>
        </AutoGateLayout>
    )
}