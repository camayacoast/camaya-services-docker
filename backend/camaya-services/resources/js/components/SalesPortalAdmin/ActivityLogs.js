import React, {useState} from 'react';
import { Button, Modal, Table } from 'antd';
import moment from 'moment';
moment.tz.setDefault('Asia/Manila');

import SalesAdminPortalService from 'services/SalesAdminPortal'

function ActivityLogs(props) {

    const [showLogsModal, setShowLogsModal] = useState(false);
    const [activityLogsData, setActivityLogsData] = useState([]);
    const activityLogs = SalesAdminPortalService.inventoryActivityLogs(props.type);

    if( props.refetch ) {
        activityLogs.refetch();
        props.refetchSetter(false);
    }

    const showAcitivityLogsModal = () => {
        setShowLogsModal(true);
        setActivityLogsData(activityLogs.data);
    }

    return (
        <>
            <Button onClick={() => showAcitivityLogsModal()}>Activity Logs</Button>
            <Modal
                visible={showLogsModal}
                onCancel={()=>setShowLogsModal(false)}
                footer={null}
                width={1200}
                title="Activity Logs"
            >
                <Table
                    size="small"
                    dataSource={activityLogsData}
                    rowKey="id"
                    columns={[
                        {
                            dataIndex: 'created_at',
                            rowKey: 'created_at',
                            title: 'Date',
                            width: '200px',
                            render: (text, record) => <>{moment(record.created_at).format('YYYY-MM-DD h:mm:ss A')}</>
                        },
                        {
                            dataIndex: 'action',
                            rowKey: 'action',
                            title: 'Action',
                            width: '100px',
                        },
                        {
                            dataIndex: 'description',
                            rowKey: 'description',
                            title: 'Description',
                        },
                        {
                            dataIndex: 'causer',
                            rowKey: 'causer',
                            title: 'Made by',
                            width: '200px',
                            render: (text, record) => <>{record.causer ? <>({record.causer.id}) {record.causer.first_name} {record.causer.last_name}</> : ''}</>
                        }
                    ]}
                />
            </Modal>
        </>
    )
}

export default ActivityLogs;