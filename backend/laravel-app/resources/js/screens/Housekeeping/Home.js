import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import HousekeepingLayout from 'layouts/Housekeeping'

const HousekeepingDashboardComponent = React.lazy( () => import('components/Housekeeping/Dashboard'))

import PageNotFound from 'common/PageNotFound'
import { Typography } from 'antd'
import { DashboardOutlined } from '@ant-design/icons' 



export default function Page(props) {
    return (
        <HousekeepingLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><DashboardOutlined className="mr-2"/>Housekeeping Dashboard</Typography.Title>
                    <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                    <Suspense>
                        <HousekeepingDashboardComponent/>
                    </Suspense>
            </div>
        </HousekeepingLayout>
    )
}