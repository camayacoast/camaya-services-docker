import React, {Suspense} from 'react'
import moment from 'moment-timezone'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'

// const CondoInventoryList = React.lazy( () => import('components/SalesPortalAdmin/CondoInventoryList'));
const CondoInventoryList = React.lazy( () => import('components/SalesPortalAdmin/CondoInventoryListing'));

import { Typography } from 'antd'

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}>Condominium Inventory</Typography.Title>
                    <CondoInventoryList/>
            </div>
        </SalesPortalAdminLayout>
    )
}