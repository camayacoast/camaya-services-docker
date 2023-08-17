import React, {useState} from 'react';
import { Button, Modal, Table } from 'antd';
import moment from 'moment';
moment.tz.setDefault('Asia/Manila');

import SalesAdminPortalService from 'services/RealEstatePayments/PaymentService';

function ActivityLogs(props) {

    const [showLogsModal, setShowLogsModal] = useState(false);
    const [activityLogsData, setActivityLogsData] = useState([]);
    const activityLogs = SalesAdminPortalService.realestatePaymentactivityLogs();

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
            <Button size={(typeof props.size !== 'undefined') ? props.size : 'middle'} type="default" style={{marginLeft: '10px'}} onClick={() => showAcitivityLogsModal()}>Activity Logs</Button>
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
                            render: (text, record) => <>{moment(record.created_at).format('YYYY-MM-DD h:mm:ss A')}</>
                        },
                        {
                            dataIndex: 'action',
                            rowKey: 'action',
                            title: 'Action',
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
                            render: (text, record) => <>{record.causer ? <>({record.causer.id}) {record.causer.first_name} {record.causer.last_name}</> : ''}</>
                        }
                    ]}
                />
            </Modal>
        </>
    )
}

export default ActivityLogs;