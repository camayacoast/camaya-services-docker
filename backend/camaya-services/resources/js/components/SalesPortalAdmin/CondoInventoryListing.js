import React, {useState, useEffect} from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import ActivityLogs from 'components/SalesPortalAdmin/ActivityLogs';
import SalesAdminPortalService from 'services/SalesAdminPortal'
import ImportInventory from 'components/SalesPortalAdmin/ImportInventory';

import { Table, Button, Modal, Form, Input, Select, Row, Col, InputNumber, Alert, notification, Space, Card, message, Descriptions } from 'antd'
const { TextArea } = Input;
import {ImportOutlined, PrinterOutlined, LoadingOutlined, EditOutlined, DeleteOutlined, ExclamationCircleOutlined, UserOutlined} from '@ant-design/icons'

const numberWithCommas = (x) => {
    if (!x) return '';
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


const Page = (props) => {

    const [searchForm] = Form.useForm();
    const [editForm] = Form.useForm();
    const [newRecordForm] = Form.useForm();
    const [priceUpdateForm] = Form.useForm();
    const [subdivisionFilter, setSubdivisionFilter] = useState(null);
    const [subdivisionOptions, setSubdivisionOptions] = useState(null);
    const [selectedSubdivision, setSelectedSubdivision] = useState(null);
    const [selectedPhase, setselectedPhase] = useState(null);
    const [selectedBlock, setSelectedBlock] = useState(null);
    const [selectedLot, setSelectedLot] = useState(null);
    const [dashboardData, setDashboardData] = useState(false); 
    const [activityLogRefetch, setActivityLogRefetch] = useState(false);

    const [searchString, setSearchString] = useState('');
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [editFormData, setEditFormData] = useState({}); 
    const [errorMessages, setErrorMessages] = useState('');
    const [newRecordModalVisible, setNewRecordModalVisible] = useState(false);
    const [newFormData, setNewFormData] = useState({});
    const [priceUpdateModalVisible, setPriceUpdateModalVisible] = useState(false);

    const [priceUpdateSubdivision, setPriceUpdateSubdivision] = useState(null);
    const [selectedUpdateSubdivision, setSelectedUpdateSubdivision] = useState(null);
    const [selectedUpdatePhase, setSelectedUpdatePhase] = useState(null);
    const [selectedUpdateBlock, setSelectedUpdateBlock] = useState(null);
    const [selectedUpdateLot, setSelectedUpdateLot] = useState(null);
    const [priceUpdatePhase, setPriceUpdatePhase] = useState(null);
    const [priceUpdateBlock, setPriceUpdateBlock] = useState(null);
    const [priceUpdateLot, setPriceUpdateLot] = useState(null);
    const [priceUpdateType, setPriceUpdateType] = useState(null);
    const [priceUpdateTotalRecords, setPriceUpdateTotalRecords] = useState([]);
    const [selectedUpdateArea, setSelectedUpdateArea] = React.useState(null);

    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(false);
    const [doRequest, setDoRequest] = useState(true);
    const [tableParams, setTableParams] = useState({
        type: 'condo',
        pagination: {
            current: 1,
            pageSize: 10,
        }
    });

    const subdivisionList = SalesAdminPortalService.subdivisionList('condo');
    const dashboardCounts = SalesAdminPortalService.inventoryDashoardCounts('condo');
    const [lotInventoryListQuery, { IsLoading: lotInventoryListQueryIsLoading, reset: lotInventoryListQueryReset}] = SalesAdminPortalService.viewlotInventoryList(tableParams.pagination.current);
    const [customFilterQuery, {isLoading: customFilterQueryIsLoading, reset: customFilterQueryReset}] = SalesAdminPortalService.inventoryCustomFilter();

    const [activityLogQuery, {isLoading: activityLogQueryIsLoading, reset: activityLogQueryReset}] = SalesAdminPortalService.addInventoryActivityLogs(); 
    const [updateLotDetailsQuery, {isLoading: updateLotDetailsQueryIsLoading, reset: updateLotDetailsQueryReset}] = SalesAdminPortalService.updateLotDetails(); 
    const [deleteLotDetailsQuery, {isLoading: deleteLotDetailsQueryIsLoading, reset: deleteLotDetailsQueryReset}] = SalesAdminPortalService.deleteLotDetails(); 
    const [newLotRecordQuery, {isLoading: newLotRecordQueryIsLoading, reset: newLotRecordQueryReset}] = SalesAdminPortalService.newLotRecord(); 
    const [priceUpdateQuery, {isLoading: priceUpdateQueryIsLoading, reset: priceUpdateQueryReset}] = SalesAdminPortalService.priceUpdate(); 
    const [exportInventoryStatusReportQuery, { isLoading: exportInventoryStatusReportQueryIsLoading, reset: exportInventoryStatusReportQueryReset}] = SalesAdminPortalService.exportInventoryStatusReport();
    const [clientReservationQuery, {isLoading: clientReservationQueryIsLoading, reset: clientReservationQueryReset}] = SalesAdminPortalService.clientReservation(); 

    const paginateData = () => {

        setLoading(true);

        if(lotInventoryListQueryIsLoading) {
            return false;
        }

        lotInventoryListQuery(tableParams, {
            onSuccess: (res) => {

                let response = res.data;
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
                lotInventoryListQueryReset();
            },
        });
        
    }

    useEffect(() => {
        if( doRequest ) {
            paginateData();
        }
    }, [tableParams]);

    useEffect(() => {
        if( typeof dashboardCounts.data != 'undefined' ) {
            setDashboardData(dashboardCounts.data);
        }
    }, [dashboardCounts.data]);

    const handleTableChange = (pagination, filters, sorter) => {
        setDoRequest(true);
        setTableParams({
            ...tableParams,
            pagination,
            filters,
            ...sorter,
        });
    };

    const refetchData = () => {
        searchForm.resetFields();
        setDoRequest(true);
        setTableParams({
            ...tableParams,
            custom_filters : false
        });
    }

    const handleSearchForm = (e, type) => {

        setDoRequest(true);
        let custom_filters = searchForm.getFieldValue();

        if( e != '' && type == 'subdivision' ) {

            if(customFilterQueryIsLoading) {
                return false;
            }

            customFilterQuery({...custom_filters, type: 'condo'}, {
                onSuccess: (res) => {
                    let data = res.data;
                    setSubdivisionFilter(data);
                },
                onError: (e) => {
                    console.log(e)
                    customFilterQueryReset();
                },
            });
        } 

        if( e == '' &&  type == 'subdivision') {
            searchForm.resetFields();
            setTableParams({
                ...tableParams,
                custom_filters : false
            });
        } else {
            setTableParams({
                ...tableParams,
                custom_filters: custom_filters
            });
        }

    }

    const showBuyersDetails = (params) => {

        if (clientReservationQueryIsLoading) {
            return false;
        }

        clientReservationQuery(params, {
            onSuccess: (res) => {
                let data = res.data;
                if( data ) {
                    Modal.info({
                        title: 'Buyer\'s Information',
                        icon: '',
                        okText: 'OK',
                        cancelText: 'Cancel',
                        width: '500px',
                        content: <>
                            <Descriptions column={4} bordered style={{marginTop: '25px'}}>
                                <Descriptions.Item label="Name" span={4}>{data.client.first_name} {data.client.last_name}</Descriptions.Item>
                                <Descriptions.Item label="Email" span={4}>{data.client.email}</Descriptions.Item>
                                <Descriptions.Item label="Contact Number" span={4}>{data.client.information.contact_number}</Descriptions.Item>
                            </Descriptions>
                            
                        </>,
                    });
                } else {
                    message.info("No Reservation Found");
                }
            },
            onError: (e) => {
                console.log(e);
                setErrorMessages(e.message);
            }
        })
        
    }

    const addInventoryActivityLog = (type) => {
        if (activityLogQueryIsLoading) {
            return false;
        }

        activityLogQuery(type, {
            onSuccess: (res) => {
                setActivityLogRefetch(true);
            },
            onError: (e) => {
                console.log(e);
                activityLogQueryReset();
                setErrorMessages(e.message);
            }
        })
    }

    const editFormOnFinish = (values) => {
        // console.log(values);

        if (updateLotDetailsQueryIsLoading) {
            return false;
        }

        updateLotDetailsQuery(values, {
            onSuccess: (res) => {
                editForm.resetFields();
                setEditModalOpen(false);
                notification.success({
                    message: 'Condo record updated!',
                    description: '',
                  });

                addInventoryActivityLog({
                    type: 'condo',
                    action: 'update',
                    description: 'Condo inventory with id' + values.id + ' is updated',
                    details: values
                });

                refetchData();
            },
            onError: (e) => {
                console.log(e);
                setErrorMessages(e.message);
            }
        })
    }

    const handleNewRecordClick = () => {
        setNewRecordModalVisible(true);
    }

    const handleNewRecordFormFinish = (values) => {
        // console.log(values);
        if (newLotRecordQueryIsLoading) {
            return false;
        }

        newLotRecordQuery(values, {
            onSuccess: (res) => {
                // console.log(res);
                newRecordForm.resetFields();
                setNewRecordModalVisible(false);
                notification.success({
                    message: 'Condo record saved!',
                    description:
                      '',
                  });

                addInventoryActivityLog({
                    type: 'condo',
                    action: 'add',
                    description: 'New Condo record added',
                    details: values
                });

                refetchData();

                
            },
            onError: (e) => {
                // console.log(e);
                if (e.message == 'Unauthorized.') {
                    message.error("You don't have access to do this action.");
                }
                setErrorMessages(e.message);
            }
        })
    }

    const handlePriceUpdateFinish = (values) => {

        values.area = selectedUpdateArea;
        const amount = priceUpdateForm.getFieldValue('price_per_sqm');
        const subdivision = priceUpdateForm.getFieldValue('subdivision');

        let ans = confirm(`Are you sure you want to update price to ${amount} of ${subdivision}?`);

        if (ans) {
            if (priceUpdateQueryIsLoading) {
                return false;
            }
    
            priceUpdateQuery(values, {
                onSuccess: (res) => {
                    // console.log(res);
                    priceUpdateForm.resetFields();
                    setPriceUpdateModalVisible(false);
                    notification.success({
                        message: 'Updated prices per sqm!',
                        description:
                          '',
                      });

                    addInventoryActivityLog({
                        type: 'condo',
                        action: 'update',
                        description: 'Inventory price updated',
                        details: values
                    });
    
                    refetchData();
                },
                onError: (e) => {
                    // console.log(e);
                    notification.error({
                        message: e.message,
                        description:
                          '',
                      });
                }
            })
        }
    }

    const handleExportInventoryStatusReport = () => {
        if (exportInventoryStatusReportQueryIsLoading) {
            return false;
        }

        exportInventoryStatusReportQuery({
            id: 1,
            property_type: 'condo',
        }, {
            onSuccess: (res) => {

                // console.log(res);
                // var bin = atob(unescape(res.data));
                // var ab = s2ab(bin); // from example above
                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                //Build a URL from the file
                const fileURL = URL.createObjectURL(file);
                //Download fileURL
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `Inventory Status Report ${moment().format('MMDDYYYY HHmmss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);
            },
            onError: (e) => {
                message.info("Error");
            }
        })
    }

    

    const handleSubdivisionSelect = (e) => {

        if(customFilterQueryIsLoading) {
            return false;
        }

        customFilterQuery({subdivision_search: e, type: 'condo'}, {
            onSuccess: (res) => {
                let data = res.data;
                setSubdivisionOptions(data);
            },
            onError: (e) => {
                console.log(e)
                customFilterQueryReset();
            },
        });

    }
    const handleEditButtonClick = (record) => {

        setEditFormData(record);
        editForm.setFieldsValue(record);

        setEditModalOpen(true);
    }

    const handleDeleteButtonClick = (record) => {
        Modal.confirm({
            title: 'Delete',
            icon: <ExclamationCircleOutlined />,
            content: 'Are you sure you want to delete condo inventory record?',
            okText: 'OK',
            cancelText: 'Cancel',
            onOk: () => {
                if (deleteLotDetailsQueryIsLoading) {
                    return false;
                }
        
                deleteLotDetailsQuery(record, {
                    onSuccess: (res) => {
                        notification.success({
                            message: 'Condo record successfully deleted!',
                            description:
                              '',
                          });

                        addInventoryActivityLog({
                            type: 'Condo',
                            action: 'delete',
                            description: 'Condo inventory with id ' + record.id + ' is deleted',
                            details: record
                        });
        
                        refetchData();

                    },
                    onError: (e) => {
                        console.log(e);
                        deleteLotDetailsQueryReset();
                        setErrorMessages(e.message);
                    }
                })
            }
        });
    }

    React.useEffect( () => {

        // console.log(priceUpdatePhase);

        const phase = priceUpdatePhase == 'NO_PHASE' ? null : priceUpdatePhase;
        const block = priceUpdateBlock == 'NO_BLOCK' ? null : priceUpdateBlock;
        const lot = priceUpdateLot == 'NO_BLOCK' ? null : priceUpdateLot;
        const type = priceUpdateType == 'NO_TYPE' ? null : priceUpdateType;
        const result = [];

        if (priceUpdateSubdivision && priceUpdatePhase && priceUpdateBlock && priceUpdateLot && priceUpdateType) {

            _.map(subdivisionOptions, i => {
                if (i.subdivision == priceUpdateSubdivision && i.phase == phase && i.type == type && i.block == block && i.lot == lot && i.status !== 'reserved' && i.status !== 'sold' && i.status !== 'pending_migration') {
                    result.push(i);
                }
            });

            let tsp_final = null;

            if( result.length > 0 ) {

                setSelectedUpdateArea(result[0].area);

                let tsp_rounded = Math.round(parseFloat(result[0].area) * parseFloat(result[0].price_per_sqm));
                let tsp_floor = Math.floor(parseFloat(result[0].area) * parseFloat(result[0].price_per_sqm));
                tsp_final = (tsp_rounded % 10 > 0) ? tsp_floor : tsp_rounded;
            }

            setPriceUpdateTotalRecords(result);

            priceUpdateForm.setFieldsValue({
                ...priceUpdateForm,
                price_per_sqm: result.length ? tsp_final : null
            });
        }

    }, [priceUpdateSubdivision, priceUpdatePhase, priceUpdateBlock, priceUpdateLot, priceUpdateType]);

    const columns = [
        {
            title: 'Project',
            key: 'subdivision',
            dataIndex: 'subdivision',
            render: (text, record) => `${record.subdivision_name ?? ''} (${record.subdivision})`,
        },

        {
            title: 'Phase',
            key: 'phase',
            dataIndex: 'phase',
            render: (text, record) => (record.phase == '' || record.phase ==  null ? "-" : record.phase),
        },

        {
            title: 'Block',
            key: 'block',
            dataIndex: 'block',
        },

        {
            title: 'Unit',
            key: 'lot',
            dataIndex: 'lot',
            render: (text, record) => (record.lot == '' || record.lot ==  null ? "-" : record.lot),
        },

        {
            title: 'Area',
            key: 'area',
            dataIndex: 'area',
        },
        {
            title: 'Type',
            key: 'type',
            dataIndex: 'type',
            render: (text, record) => (record.type == "" ? "NO TYPE" : record.type),
            filters: [
                { value: 'NO TYPE', text: 'NO TYPE' },
                { value: 'CORNER', text: 'CORNER' },
                { value: 'REGULAR', text: 'REGULAR' },
                { value: 'PREMIUM', text: 'PREMIUM' },
                { value: 'FAIRWAY', text: 'FAIRWAY' },
                { value: 'PREMIUM CORNER', text: 'PREMIUM CORNER' },
            ],
            defaultFilteredValue: [],
            onFilter: (value, record) => value.includes(record.type),
        },

        {
            title: 'Status',
            key: 'status',
            dataIndex: 'status',
            render: (text, record) => <span style={{textTransform:'capitalize'}}>{text}</span>,
            filters: [
                { value: 'for_review', text: 'For review' },
                { value: 'available', text: 'Available' },
                { value: 'reserved', text: 'Reserved' },
                { value: 'sold', text: 'Sold' },
            ],
            defaultFilteredValue: [],
        },

        {
            title: 'Status 2',
            key: 'status2',
            dataIndex: 'status2',
            filters: [
                { value: 'Current', text: 'Current' },
                { value: 'Cash', text: 'Cash' },
                { value: 'Ofc Res', text: 'Ofc Res' },
                { value: 'PD', text: 'PD' },
                { value: 'Special', text: 'Special' },
                { value: 'Not Saleable', text: 'Not Saleable' },
                { value: 'Available', text: 'Available' },
                { value: 'Engr Res', text: 'Engr Res' },
                { value: 'Reservation', text: 'Reservation' },
            ],
            defaultFilteredValue: [],
        },

        {
            title: 'TSP',
            render: (text, record) => {

                let tsp = parseFloat(record.area) * parseFloat(record.price_per_sqm);
                let tsp_rounded = Math.round(parseFloat(record.area) * parseFloat(record.price_per_sqm));
                let tsp_floor = Math.floor(parseFloat(record.area) * parseFloat(record.price_per_sqm));

                let tsp_final = (tsp_rounded % 10 > 0) ? (tsp_rounded - (tsp_rounded % 10)) : tsp_rounded;

                if (record.area && record.price_per_sqm) {
                    return <span className="text-success">&#8369; {numberWithCommas(tsp_final.toFixed(2))}</span>
                } else {
                    return '';
                }
            }
        },
        {
            title: 'Action',
            render: (text, record) => {
                let params = {
                    property_type: record.property_type,
                    subdivision: record.subdivision,
                    block: record.block,
                    lot: record.lot,
                }
                return <>
                    {
                        typeof record.client_number !== 'undefined' && (record.status == 'reserved' || record.status == 'sold') &&
                        <Button style={{marginRight: '5px'}} size="small" onClick={()=>showBuyersDetails(params)}><UserOutlined /></Button>
                    }
                    <Button style={{marginRight: '5px'}} size="small" onClick={()=>handleEditButtonClick(record)}><EditOutlined /></Button> 
                    <Button size="small" disabled={['pending_migration', 'reserved', 'sold'].includes(record.status) ? true : false} onClick={()=>handleDeleteButtonClick(record)}><DeleteOutlined /></Button>
                </>
                
            }
        },
    ];

    return (
        <>
            <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>
                <Space>
                    <Button onClick={() => handleNewRecordClick()}>New Condominium Record</Button>
                    <Button onClick={() => setPriceUpdateModalVisible(true)}>Price Update</Button>
                    <ActivityLogs type="condo" refetch={activityLogRefetch} refetchSetter={setActivityLogRefetch}/>
                </Space>
                <Space>
                    <ImportInventory property_type="condo" refetchData={refetchData} addActivityLog={addInventoryActivityLog} />
                    <Button onClick={() => handleExportInventoryStatusReport()} icon={<PrinterOutlined/>}>Export Inventory Status Report</Button>
                </Space>
            </div>
            <div className="mt-4 mb-4">
                {
                    <Row gutter={[16,16]}>
                        <Col xl={4}>
                            <Card title="Available" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                { dashboardData &&
                                    dashboardData.available
                                }
                            </Card>
                        </Col>
                        <Col xl={4}>
                            <Card title="Reserved" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                { dashboardData &&
                                    dashboardData.reserved
                                }
                            </Card>
                        </Col>
                        <Col xl={4}>
                            <Card title="Sold" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                { dashboardData &&
                                    dashboardData.sold
                                }
                            </Card>
                        </Col>
                        <Col xl={4}>
                            <Card title="Pending migration" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                { dashboardData &&
                                    dashboardData.pending_migration
                                }
                            </Card>
                        </Col>
                        <Col xl={4}>
                            <Card title="Not saleable" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                { dashboardData &&
                                    dashboardData.not_saleable
                                }
                            </Card>
                        </Col>
                    </Row>
                }
            </div>
            <Card size="small">
                <Form form={searchForm}>
                    Search:
                    <Space>
                        <Form.Item name="subdivision_search">
                            <Select style={{ width: 300, marginLeft: 16 }} placeholder="Subdivision" value={selectedSubdivision} onChange={(e) => {
                                handleSearchForm(e, 'subdivision');
                            }}>
                                <Select.Option value=""></Select.Option>
                                {
                                    subdivisionList.data &&
                                        _.uniqBy(subdivisionList.data
                                            .map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
                                        .map( (i, key) => {
                                            return <Select.Option key={key} value={i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
                                        })
                                }
                            </Select>
                        </Form.Item>
                        <Form.Item name="phase_search">
                            <Select style={{width: 150}} placeholder="Phase" value={selectedPhase} onChange={(e) => {
                                handleSearchForm(e, 'phase');
                            }}>
                                <Select.Option value=""></Select.Option>
                                { subdivisionFilter !== null &&
                                    _.uniqBy(subdivisionFilter.map( i => { return { phase: i.phase } }), 'phase')
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.phase}>{i.phase}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                        <Form.Item name="block_search">
                            <Select style={{width: 150}} placeholder="Block" value={selectedBlock} onChange={(e) => {
                                handleSearchForm(e, 'block');
                            }}>
                                <Select.Option value=""></Select.Option>
                                { subdivisionFilter !== null &&
                                    _.uniqBy(subdivisionFilter.map( i => { return { block: i.block } }), 'block')
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.block}>{i.block}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                        <Form.Item name="lot_search">
                            <Select style={{width: 150}} placeholder="Lot" value={selectedLot} onChange={(e) => {
                                handleSearchForm(e, 'lot');
                            }}>
                                <Select.Option value=""></Select.Option>
                                { subdivisionFilter !== null &&
                                    _.uniqBy(subdivisionFilter.map( i => { return { lot: i.lot } }), 'lot')
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.lot}>{i.lot}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                        <Form.Item name="area_search">
                            <Select style={{width: 150}} placeholder="Area" value={selectedLot} onChange={(e) => {
                                handleSearchForm(e, 'area');
                            }}>
                                <Select.Option value=""></Select.Option>
                                { subdivisionFilter !== null &&
                                    _.uniqBy(subdivisionFilter.map( i => { return { area: i.area } }), 'area')
                                    .map( (i, key) => {
                                        return <Select.Option key={key} value={i.area}>{i.area}</Select.Option>
                                    })
                                }
                            </Select>
                        </Form.Item>
                    </Space>
                </Form>
            </Card>
            <Table
                columns={columns}
                rowKey={(record) => record.id}
                dataSource={data}
                pagination={tableParams.pagination}
                loading={loading}
                onChange={handleTableChange}
            />

            {/* Modal */}

            <Modal
                visible={editModalOpen}
                onCancel={()=>setEditModalOpen(false)}
                onOk={()=>editForm.submit()}
                title={"Edit condo details"}
                forceRender
            >
                <Form form={editForm} layout="vertical" onFinish={editFormOnFinish}>
                    <Form.Item name="id" style={{display: 'none'}}><Input/></Form.Item>
                    <Row gutter={[8, 8]}>
                        <Col xl={24}>
                            <Form.Item name="subdivision" label="Project">
                                <Select>
                                    {
                                        subdivisionList.data &&
                                            _.uniqBy(subdivisionList.data
                                                .map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
                                            .map( (i, key) => {
                                                return <Select.Option key={key} value={i.name ? i.subdivision + '++' + i.name : i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
                                            })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="phase" label="Phase">
                                <Input placeholder="Phase"/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="lot" label="Unit" rules={[{ required: true }]}>
                                <InputNumber placeholder="Unit"/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="block" label="Bldg" rules={[{ required: true }]}>
                                <Input placeholder="Bldg"/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item 
                                disabled={['reserved', 'sold'].includes(editFormData.status) && editFormData.has_reservation_agreement === true} 
                                name="area" label="Area" rules={[{ required: true }]}
                            >
                                <InputNumber 
                                    onChange={(e)=>setEditFormData({ ...editFormData, area: e})} 
                                    placeholder="Area"
                                    disabled={['reserved', 'sold'].includes(editFormData.status) && editFormData.has_reservation_agreement === true}
                                />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="price_per_sqm" label="Price per sqm (PHP)">
                                <InputNumber 
                                    disabled={['reserved', 'sold'].includes(editFormData.status) && editFormData.has_reservation_agreement === true} 
                                    onChange={(e)=>setEditFormData({ ...editFormData, price_per_sqm: e})} style={{width: '100%'}} 
                                />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="type" label="Type" rules={[{ required: true }]}>
                                <Input placeholder="Type"/>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="status" label="Status">
                                <Select disabled={['available', 'reserved', 'sold'].includes(editFormData.status) && editFormData.has_reservation_agreement === true}>
                                    <Select.Option value="for_review">For review</Select.Option>
                                    <Select.Option value="available">Available</Select.Option>
                                    <Select.Option value="reserved">Reserved</Select.Option>
                                    <Select.Option value="sold">Sold</Select.Option>
                                    <Select.Option value="not_saleable">Not Saleable</Select.Option>
                                    <Select.Option value="pending_migration">Pending Migration</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="status2" label="Status 2" rules={[{ required: true }]}>
                                <Input placeholder="Status 2"/>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="color" label="Color" rules={[{ required: true }]}>
                                <Input placeholder="Color"/>
                            </Form.Item>
                        </Col>

                        <Col xl={24}>
                            <Form.Item name="remarks" label="Remarks">
                                <TextArea style={{borderRadius: '12px'}} rows={4} placeholder="Remarks" />
                            </Form.Item>
                        </Col>
                        
                    </Row>
                </Form>

                <div>Total Selling Price: <span className="text-success">&#8369; {numberWithCommas(Math.round(parseFloat(editFormData.price_per_sqm * editFormData.area)))}</span><br/><small className="text-secondary">Price per sqm x Area</small></div>

                {
                    errorMessages != '' && <Alert style={{marginTop: 16}} message={errorMessages} type="warning" />
                }
            </Modal>

            <Modal
                visible={newRecordModalVisible}
                onCancel={() => setNewRecordModalVisible(false)}
                footer={null}
                title="New condominium record"
            >
                <Form form={newRecordForm}
                    initialValues={{
                        status: 'for_review',
                        property_type: 'condo',
                    }}
                    onFinish={handleNewRecordFormFinish}
                >
                    <Row gutter={[8, 8]}>
                        <Col xl={24}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="subdivision" label="Project">
                                <Select style={{width: '100%'}}>
                                    {
                                        subdivisionList.data &&
                                            _.uniqBy(subdivisionList.data
                                                .map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
                                            .map( (i, key) => {
                                                return <Select.Option key={key} value={i.name ? i.subdivision + '++' + i.name : i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
                                            })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="phase" label="Phase">
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="block" label="Block">
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="lot" label="Unit">
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="area" label="Area">
                                <Input onChange={(e)=>setNewFormData({ ...newFormData, area: e.target.value})} placeholder="Area"/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="type" label="Type">
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={24}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="total_selling_price" label="Total Selling Price (PHP)">
                                <InputNumber onChange={(e)=>setNewFormData({ ...newFormData, total_selling_price: e})} style={{width: '100%'}} />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="status" label="Status">
                                <Select>
                                    <Select.Option value="for_review">For review</Select.Option>
                                    <Select.Option value="not_saleable">Not Saleable</Select.Option>
                                </Select>
                            </Form.Item>
                            <Form.Item name="property_type" label="Property Type" style={{display: 'none'}}>
                                <Input defaultValue='condo' value='condo' type="hidden"/>
                            </Form.Item>
                        </Col>
                        <Col xl={24}>
                            <Button type="primary" htmlType="submit" style={{width: '100%', marginTop: 48}}>Save</Button>
                        </Col>
                    </Row>
                </Form>

                {
                    errorMessages != '' && <Alert style={{marginTop: 16}} message={errorMessages} type="warning" />
                }
            </Modal>

            <Modal
                visible={priceUpdateModalVisible}
                onCancel={() => setPriceUpdateModalVisible(false)}
                footer={null}
                title="Price update"
            >
                <Form form={priceUpdateForm} layout="vertical" onFinish={handlePriceUpdateFinish}>
                    <Row gutter={[8, 8]}>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="subdivision" label="Project">
                                <Select style={{width: '100%'}} onChange={(e) => {

                                    handleSubdivisionSelect(e);

                                    setPriceUpdateSubdivision(e);
                                    setSelectedUpdateSubdivision(e);

                                    setPriceUpdatePhase(null);
                                    setPriceUpdateBlock(null);
                                    setPriceUpdateLot(null);
                                    setPriceUpdateType(null);

                                    setSelectedUpdatePhase(null);
                                    setSelectedUpdateBlock(null);
                                    setSelectedUpdateLot(null);

                                    priceUpdateForm.setFieldsValue({
                                        ...priceUpdateForm,
                                        price_per_sqm: null,
                                        phase: null,
                                        block: null,
                                        lot: null,
                                        type: null,
                                    });
                                }}>
                                    {
                                        subdivisionList.data &&
                                            _.uniqBy(subdivisionList.data
                                                .map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
                                            .map( (i, key) => {
                                                return <Select.Option key={key} value={i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
                                            })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="phase" label="Phase">
                                <Select style={{width: '100%'}} disabled={!priceUpdateSubdivision && !selectedUpdatePhase} onChange={(e) => {
                                    setPriceUpdatePhase(e);
                                    setSelectedUpdatePhase(e)

                                    setPriceUpdateBlock(null);
                                    setPriceUpdateLot(null);
                                    setPriceUpdateType(null);

                                    setSelectedUpdateBlock(null);
                                    setSelectedUpdateLot(null);

                                    priceUpdateForm.setFieldsValue({
                                        ...priceUpdateForm,
                                        price_per_sqm: null,
                                        block: null,
                                        lot: null,
                                        type: null,
                                    });
                                }}>
                                    { subdivisionOptions !== null &&
                                        _.uniqBy(subdivisionOptions.map( i => { 
                                            return { 
                                                subdivision: i.subdivision,
                                                phase: i.phase,
                                            } 
                                        }), 'phase')
                                        .map( (i, key) => {
                                            if( typeof i !== 'undefined' && i.subdivision == selectedUpdateSubdivision ) {
                                                return <Select.Option key={key} value={i.phase}>{i.phase}</Select.Option>
                                            }
                                        })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="block" label="Bldg">
                                <Select style={{width: '100%'}} disabled={!priceUpdateSubdivision} onChange={(e) => {
                                        setPriceUpdateBlock(e);
                                        setSelectedUpdateBlock(e);

                                        setPriceUpdateLot(null);
                                        setPriceUpdateType(null);

                                        setSelectedUpdateLot(null);

                                        priceUpdateForm.setFieldsValue({
                                            ...priceUpdateForm,
                                            price_per_sqm: null,
                                            lot: null,
                                            type: null,
                                        });
                                    }}>
                                    { subdivisionOptions !== null &&
                                        _.uniqBy(subdivisionOptions.map( i => { 
                                            return { 
                                                subdivision: i.subdivision,
                                                phase: i.phase,
                                                block: i.block 
                                            } 
                                        }), 'block')
                                        .map( (i, key) => {
                                            if( typeof i !== 'undefined' && i.subdivision == selectedUpdateSubdivision &&  i.phase == selectedUpdatePhase ) {
                                                return <Select.Option key={key} value={i.block}>{i.block}</Select.Option>
                                            }
                                        })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="lot" label="Unit">
                                <Select style={{width: '100%'}} disabled={!priceUpdateSubdivision} onChange={(e) => {
                                    setPriceUpdateLot(e)
                                    setSelectedUpdateLot(e);

                                    setPriceUpdateType(null);

                                    priceUpdateForm.setFieldsValue({
                                        ...priceUpdateForm,
                                        price_per_sqm: null,
                                        type: null,
                                    });

                                }}>
                                    { subdivisionOptions !== null &&
                                        _.uniqBy(subdivisionOptions.map( i => { 
                                            return { 
                                                subdivision: i.subdivision,
                                                phase: i.phase,
                                                block: i.block,
                                                lot: i.lot 
                                            } }), 'lot')
                                        .map( (i, key) => {
                                            if( typeof i !== 'undefined' && i.subdivision == selectedUpdateSubdivision &&  i.phase == selectedUpdatePhase &&  i.block == selectedUpdateBlock ) {
                                                return <Select.Option key={key} value={i.lot}>{i.lot}</Select.Option>
                                            }
                                        })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="type" label="Type">
                                <Select style={{width: '100%'}} disabled={!priceUpdateSubdivision} onChange={(e) => setPriceUpdateType(e)}>
                                    { subdivisionOptions !== null &&
                                        subdivisionOptions.map( (i, key) => {
                                            if( typeof i !== 'undefined' && i.subdivision == selectedUpdateSubdivision &&  i.phase == selectedUpdatePhase &&  i.block == selectedUpdateBlock &&  i.lot == selectedUpdateLot ) {
                                                return <Select.Option key={key} value={i.type}>{i.type}</Select.Option>
                                            }
                                        })
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="price_per_sqm" label="Total Selling Price">
                                <InputNumber min="0" style={{width: '100%'}} />
                            </Form.Item>

                            <Form.Item name="property_type" label="Property Type" style={{display: 'none'}} initialValue='condo'>
                                <Input defaultValue='condo' value='condo' type="hidden"/>
                            </Form.Item>
                        </Col>
                        <Col xl={24}>
                            <Alert message={`Current price per sqm (prices will be updated): ${_.uniq(_.map(priceUpdateTotalRecords, i => i.price_per_sqm))}`} />
                            <Alert message={`Total lot record${priceUpdateTotalRecords.length > 1 ? 's' : ''} to update: ${priceUpdateTotalRecords.length}`} />
                        </Col>
                        

                        <Col xl={24}>
                            <Button disabled={!priceUpdateTotalRecords} htmlType="submit" type="primary" block>Save</Button>
                        </Col>
                    </Row>
                </Form>
            </Modal>
        </>
    )
}

export default Page;