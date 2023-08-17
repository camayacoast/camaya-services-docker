import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import GolfLayout from 'layouts/Golf'

const HomeComponent = React.lazy( () => import('components/Golf/Home'))

import { Typography } from 'antd'
import { DashboardOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <GolfLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><DashboardOutlined className="mr-2"/>Golf Dashboard</Typography.Title>
                    <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                    <HomeComponent/>
            </div>
        </GolfLayout>
    )
}