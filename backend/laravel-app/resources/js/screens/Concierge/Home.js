import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import ConciergeLayout from 'layouts/Concierge'

const SnackPackDashboardComponent = React.lazy( () => import('components/Concierge/Dashboard/SnackPack'))

import PageNotFound from 'common/PageNotFound'
import { Typography } from 'antd'
import { DashboardOutlined } from '@ant-design/icons' 



export default function Page(props) {
    return (
        <ConciergeLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><DashboardOutlined className="mr-2"/>Concierge Dashboard</Typography.Title>
                    <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                    <Suspense><SnackPackDashboardComponent/></Suspense>
            </div>
        </ConciergeLayout>
    )
}