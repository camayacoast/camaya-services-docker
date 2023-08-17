import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { Table, Button, Input, Modal, message, Typography, Tooltip, Upload, Form } from 'antd'
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

    const [reservationListQuery, { IsLoading: reservationListQueryIsLoading, reset: reservationListQueryReset}] = SalesAdminPortalService.viewAccountList(tableParams.pagination.current);

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
            title: 'Client #',
            dataIndex: 'client_number',
            key: 'client_number',
            responsive: ['md'],
            render: (text, record) => <Link to={`view-account/${record.reservation_number}`}><small>{record.client_number}</small></Link>,
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
        },
        {
            title: 'Payment terms',
            responsive: ['md'],
            render: (text, record) => {
                let cashType = record.number_of_cash_splits > 0 ? 'SPLIT ' : '';
                return <small>{record.payment_terms_type == 'cash' ? cashType + 'CASH' : 'IN-HOUSE'}</small>
            }
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
            render: (text, record) => <Link to={`view-account/${record.reservation_number}`}><Button size="small" type="primary" icon={<EyeOutlined/>}></Button></Link>
        },
    ];

    return(
        <>
            <div className="mt-4 mb-4" >
                <Form form={searchForm} onFinish={handleSearch}>
                    <Form.Item name="search_input">
                        <Input style={{width: 300}} type="search" placeholder="Search account" onChange={(e) => resetTable(e)} />
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