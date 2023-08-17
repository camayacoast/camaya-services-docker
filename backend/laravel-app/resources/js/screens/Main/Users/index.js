import React from 'react'
import MainLayout from 'layouts/Main'
import Users from 'components/Users/List'

import { Typography, Card } from 'antd'

function Page(props) {
    
    return (
        <MainLayout {...props}>
            <div className="fadeIn">
                <Typography.Title level={2} className="mb-4">Users</Typography.Title>
                <Users />
            </div>
        </MainLayout>
    )
    
}

export default Page;