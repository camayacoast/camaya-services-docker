import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'

const SalesClientsComponent = React.lazy( () => import('components/SalesPortalAdmin/SalesClients'))

import { Typography } from 'antd'
import { UserOutlined } from '@ant-design/icons' 

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}><UserOutlined className="mr-2"/> Sales Clients</Typography.Title>
                    <SalesClientsComponent/>
            </div>
        </SalesPortalAdminLayout>
    )
}