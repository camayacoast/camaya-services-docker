import React, {useEffect, useState} from "react";
import { Link } from 'react-router-dom'
import { Table, Button, Input, Modal, message, Tooltip, Form } from 'antd'
import { EyeOutlined, FileAddOutlined, PrinterOutlined, ImportOutlined } from '@ant-design/icons';
import moment from 'moment';

import SalesAdminPortalService from 'services/SalesAdminPortal'

const Page = (props) => {

    const [searchForm] = Form.useForm();
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [doRequest, setDoRequest] = useState(true);
    const [tableParams, setTableParams] = useState({
        pagination: {
            current: 1,
            pageSize: 10,
        }
    });

    const [reservationListQuery, { IsLoading: reservationListQueryIsLoading, reset: reservationListQueryReset}] = SalesAdminPortalService.viewReservationList(tableParams.pagination.current);
    const [exportReservationDataQuery, { isLoading: exportReservationDataQueryIsLoading, reset: exportReservationDataQueryReset}] = SalesAdminPortalService.exportReservationData();

    const paginateData = () => {

        setLoading(true);

        if(reservationListQueryIsLoading) {
            return false;
        }

        reservationListQuery(tableParams, {
            onSuccess: (res) => {

                let response = res.data;
                let api_endpoint = response.path;
                let first_page_url = response.first_page_url;
                let last_page_url = response.last_page_url;
                let next_page_url = response.next_page_url;
                let prev_page_url = response.prev_page_url;
                let current_page = response.current_page;
                let per_page = response.per_page;
                let total_page = response.total;
                let data = response.data;

                setData(data);
                setLoading(false);
                setDoRequest(false);

                setTableParams({
                    ...tableParams,
                    pagination: {
                      ...tableParams.pagination,
                      total: total_page,
                    },
                  });

            },
            onError: (e) => {
                console.log(e)
                reservationListQueryReset();
            },
        });
        
    }

    useEffect(() => {
        if( doRequest ) {
            paginateData();
        }
    }, [tableParams]);

    const handleTableChange = (pagination, filters, sorter) => {
        setDoRequest(true);
        setTableParams({
            pagination,
            filters,
            ...sorter,
        });
        searchForm.resetFields();
    };

    const handleExportReservationData = () => {

        if (exportReservationDataQueryIsLoading) {
            return false;
        }

        exportReservationDataQuery({id: 1}, {
            onSuccess: (res) => {
                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                const fileURL = URL.createObjectURL(file);
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `Reservation Data Report ${moment().format('YYYY-MM-DD HH:mm:ss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);
            },
            onError: (e) => {
                message.info("Error on export data");
            }
        })
    }

    const handleSearch = (values) => {
        let value = values.search_input;

        setDoRequest(true);
        setTableParams({
            ...tableParams,
            search: value,
        });
        
    }

    const resetTable = (e) => {
        let value = e.target.value;

        if( value == '' ) {
            setDoRequest(true);
            setTableParams({
                ...tableParams,
                search: false,
            });
        }
    }

    const columns = [
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
            dataIndex: 'client_number',
            key: 'client_number',
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
            defaultFilteredValue: [],
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
            dataIndex: 'reservation_date_created',
            key: 'reservation_date_created',
            responsive: ['md'],
            render: (text) => <small>{moment(text).format('DD MMM YYYY')}</small>,
            sorter: (a, b) => moment(a.reservation_date_created).unix() - moment(b.reservation_date_created).unix(),
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

    return (
        <>
            <div className="mt-4 mb-4" style={{textAlign: 'right'}}>
                {/* <Button disabled icon={<ImportOutlined/>}>Import Reservation Data</Button> */}
                <Link to="/sales-admin-portal/new-reservation">
                    <Button icon={<FileAddOutlined/>}>Create New Reservation</Button>
                </Link> &nbsp;
                <Button onClick={()=>handleExportReservationData()} icon={<PrinterOutlined/>}>Export Reservation Data</Button>
                
            </div>
            <div className="mt-4 mb-4" >
                <Form form={searchForm} onFinish={handleSearch}>
                    <Form.Item name="search_input">
                        <Input style={{width: 300}} type="search" placeholder="Search reservation" onChange={(e) => resetTable(e)} />
                    </Form.Item>
                </Form>
            </div>
            <Table
                columns={columns}
                rowKey={(record) => record.reservation_id}
                dataSource={data}
                pagination={tableParams.pagination}
                loading={loading}
                onChange={handleTableChange}
            />
        </>
    )
}

export default Page;