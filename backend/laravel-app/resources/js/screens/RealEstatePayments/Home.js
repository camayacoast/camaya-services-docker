import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import RealEstatePaymentsLayout from 'layouts/RealEstatePayments'

const HomeComponent = React.lazy( () => import('components/RealEstatePayments/Home'))

import { Typography } from 'antd'
import { DashboardOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <RealEstatePaymentsLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><DashboardOutlined className="mr-2"/>Real Estate Payments Dashboard</Typography.Title>
                    <Typography.Text>Today is {moment().format('dddd, MMMM D, YYYY')}</Typography.Text>
                    <HomeComponent/>
            </div>
        </RealEstatePaymentsLayout>
    )
}