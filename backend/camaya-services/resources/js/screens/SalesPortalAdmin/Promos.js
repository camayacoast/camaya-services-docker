import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'

const PromoComponent = React.lazy( () => import('components/SalesPortalAdmin/Promos'))

import { Typography } from 'antd'
import { DashboardOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}>Promos</Typography.Title>
                    <PromoComponent/>
            </div>
        </SalesPortalAdminLayout>
    )
}