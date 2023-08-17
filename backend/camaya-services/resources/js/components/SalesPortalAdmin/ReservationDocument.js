import React from 'react'
import { Link } from 'react-router-dom'
import { Table, Button, Input, Modal, message, Typography, Tooltip, Space } from 'antd'
import { EyeOutlined, FileAddOutlined, PrinterOutlined, ImportOutlined, CheckCircleOutlined } from '@ant-design/icons';

import SalesAdminPortalService from 'services/SalesAdminPortal'
import moment from 'moment';

export default function Page(props) {

    const reservationListQuery = SalesAdminPortalService.reservationList();

    const [exportReservationDataQuery, { isLoading: exportReservationDataQueryIsLoading, reset: exportReservationDataQueryReset}] = SalesAdminPortalService.exportReservationData();

    const [searchString, setSearchString] = React.useState('');

    const columns = [
        // {
        //     title: 'ID',
        //     dataIndex: 'id',
        //     key: 'id',
        // },
        {
            title: 'Ref #',
            dataIndex: 'reservation_number',
            key: 'reservation_number',
            render: (text, record) => {
                return (
                    record.status == 'draft' ?
                    <Link to={`edit-reservation/${record.reservation_number}`}><small>{text}</small></Link> : 
                    <Link to={`view-reservation/${record.reservation_number}`}><small>{text}</small></Link>
                );
            },
        },
        {
            title: 'Client #',
            // dataIndex: 'client',
            // key: 'client',
            responsive: ['md'],
            render: (text, record) => <>{record.client_number}</>,
            sorter: (a, b) => a.client_number - b.client_number,
        },
        {
            title: 'Client',
            // dataIndex: 'client',
            // key: 'client',
            render: (text, record) => {
                return <Tooltip placement="topLeft" title={<>{record.client?.first_name} {record.client?.last_name}</>}>
                            <div style={{width: 220, whiteSpace: 'nowrap', textOverflow: 'ellipsis', overflow: 'hidden'}}>
                                <strong>{record.client?.first_name} {record.client?.last_name}</strong><br/>
                                <small className="text-secondary">{record.client && !record.client.information ? 'BIS Incomplete' : ''}</small>
                            </div>
                        </Tooltip>
            }
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
            render: (text) => <small>{text.toUpperCase()}</small>,
            filters: [
                {
                  text: 'APPROVED',
                  value: 'approved',
                },
                {
                    text: 'FOR REVIEW',
                    value: 'for_review',
                },
                {
                    text: 'REVIEWED',
                    value: 'reviewed',
                },
                {
                    text: 'PENDING',
                    value: 'pending',
                },
                {
                    text: 'FOR CLIENT # ENCODING',
                    value: 'for_client_number_encoding',
                },
                {
                    text: 'CANCELLED',
                    value: 'cancelled',
                },
                {
                    text: 'VOID',
                    value: 'void',
                },
                {
                    text: 'DRAFT',
                    value: 'draft',
                },
              ],
            defaultFilteredValue: ['approved', 'for_review', 'reviewed', 'pending', 'for_client_number_encoding', 'draft'],
            onFilter: (value, record) => record.status.indexOf(value) === 0,
        },
        {
            title: 'Payment terms',
            responsive: ['md'],
            render: (text, record) => <small>{record.payment_terms_type == 'cash' ? 'CASH' : 'IN-HOUSE'}</small>
        },
        {
            title: 'Property',
            // dataIndex: 'property',
            // key: 'property',
            responsive: ['md'],
            render: (text, record) => <small>{record.subdivision} - Block {record.block} - Lot {record.lot}</small>
        },
        {
            title: 'Approval status',
            responsive: ['md'],
            render: () => <Button type="link" size="small" onClick={() => {
                Modal.info({
                    title: 'Approval history',
                    content: <span>No history yet.</span>
                })
            }}><small>Approval history</small></Button>
        },
        {
            title: 'Date created',
            dataIndex: 'created_at',
            key: 'created_at',
            responsive: ['md'],
            render: (text) => <small>{moment(text).format('DD MMM YYYY')}</small>,
            sorter: (a, b) => moment(a.created_at).unix() - moment(b.created_at).unix(),
        },
        {
            title: 'Action',
            render: (text, record) => {
                return (
                    record.status == 'draft' ?
                    <Link to={`edit-reservation/${record.reservation_number}`}>
                        <Button size="small" type="primary" icon={<EyeOutlined/>}></Button>
                    </Link> : 
                    <Link to={`view-reservation/${record.reservation_number}`}>
                        <Button size="small" type="primary" icon={<EyeOutlined/>}></Button>
                    </Link>
                );
            }
        },
    ];

    // const data = [
    //     {
    //         id: 1,
    //         reference_number: 'RA-YHCKDF',
    //         status: 'Pending',
    //         client: 'Rick Sanchez',
    //         property: 'TGR - Phase 1 - Block 2 - Lot 22',
    //     },
    //     {
    //         id: 2,
    //         reference_number: 'RA-YHBDWE',
    //         status: 'Pending',
    //         client: 'Rick Sanchez',
    //         property: 'TGR - Phase 1 - Block 2 - Lot 23',
    //     },
    //     {
    //         id: 3,
    //         reference_number: 'RA-YHEWEQ',
    //         status: 'Pending',
    //         client: 'Rick Sanchez',
    //         property: 'TGR - Phase 1 - Block 2 - Lot 24',
    //     },
    // ]

    const handleSearch = (e) => {
        // console.log(e.target.value);
        setSearchString(e.target.value.toLowerCase());
    }

    const handleExportReservationData = () => {
        if (exportReservationDataQueryIsLoading) {
            return false;
        }

        exportReservationDataQuery({
            id: 1
        }, {
            onSuccess: (res) => {

                console.log(res);
                // var bin = atob(unescape(res.data));
                // var ab = s2ab(bin); // from example above
                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                //Build a URL from the file
                const fileURL = URL.createObjectURL(file);
                //Download fileURL
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `Reservation Data Report ${moment().format('YYYY-MM-DD HH:mm:ss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);
            },
            onError: (e) => {
                message.info("Error");
            }
        })
    }


    return (
        <div>
            <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>
                <Link to="/sales-admin-portal/new-reservation"><Button icon={<FileAddOutlined/>}>Create New Reservation</Button></Link>
                <Button disabled icon={<ImportOutlined/>}>Import Reservation Data</Button>
                <Button onClick={()=>handleExportReservationData()} icon={<PrinterOutlined/>}>Export Reservation Data</Button>
                <Input style={{width: 300}} type="search" placeholder="Search reservation" onChange={(e)=>handleSearch(e)} />
            </div>
            
            { !reservationListQuery.isLoading ? <Table
                key="loaded-table"
                size="small"
                columns={columns}
                rowKey="id"
                loading={reservationListQuery.isLoading}
                defaultExpandAllRows={true}
                // expandable={{
                //     defaultExpandAllRows: true,
                //     expandedRowRender: record => <Space><div>RF <CheckCircleOutlined /></div><div>DP <CheckCircleOutlined /></div></Space>,
                //     rowExpandable: record => record.reservation_number !== 'Not Expandable',
                // }}
                // dataSource={reservationListQuery.data && reservationListQuery.data}
                dataSource={reservationListQuery.data ? reservationListQuery.data.filter(item => {
                    if (item && searchString) {
                        const searchValue =  item.reservation_number.toLowerCase() + " " + item.client_number + " " + item.client?.first_name.toLowerCase() + " " + item.client?.last_name.toLowerCase() + " " + item.subdivision.toLowerCase() + " - block " + item.block + " - lot " + item.lot;
                        return searchString ? searchValue.indexOf(searchString.toLowerCase()) !== -1 : true;
                    }
                    return true;
                }) : []}
            />
            : 'Loading data...'
            }
        </div>
    )
    
}