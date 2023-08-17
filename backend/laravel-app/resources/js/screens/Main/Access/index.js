import React from 'react'
import MainLayout from 'layouts/Main'
import RoleList from 'components/Roles/List'

import { Typography, Card } from 'antd'

function Page(props) {
    
    return (
        <MainLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2} className="mb-4">Access</Typography.Title>

                <RoleList/>
            </div>
        </MainLayout>
    )
    
}

export default Page;