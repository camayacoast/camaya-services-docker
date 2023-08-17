import React from 'react'
import CollectionsLayout from 'layouts/Collections'

// import TransactedAccountComponent from 'components/Collections/TransactedAccounts'
import TransactedAccountComponent from 'components/Collections/TransactedAccountList'

import { Typography } from 'antd'

export default function Page(props) {
    return (
        <CollectionsLayout {...props}>
            <div className="fadeIn">
                    <Typography.Title level={2}>Transacted Accounts</Typography.Title>
                    <TransactedAccountComponent/>
            </div>
        </CollectionsLayout>
    )
}