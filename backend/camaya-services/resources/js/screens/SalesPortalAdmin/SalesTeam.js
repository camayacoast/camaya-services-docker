import React from 'react'
import SalesPortalAdminLayout from 'layouts/SalesPortalAdmin'
import SalesTeamComponent from 'components/SalesPortalAdmin/SalesTeam';

import { Typography } from 'antd'

export default function Page(props) {
    return (
        <SalesPortalAdminLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}>Sales Team</Typography.Title>

                    <SalesTeamComponent/>
            </div>
        </SalesPortalAdminLayout>
    )
}