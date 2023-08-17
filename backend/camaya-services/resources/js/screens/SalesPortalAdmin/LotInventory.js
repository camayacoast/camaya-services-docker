import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'

// const LotInventoryList = React.lazy( () => import('components/SalesPortalAdmin/LotInventoryList'))
const LotInventoryList = React.lazy( () => import('components/SalesPortalAdmin/LotInventoryListing'))

import { Typography } from 'antd'

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}>Lot Inventory</Typography.Title>
                    <LotInventoryList/>
            </div>
        </SalesPortalAdminLayout>
    )
}