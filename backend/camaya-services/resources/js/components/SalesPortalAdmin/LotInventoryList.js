import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import {subdivisions} from 'common/subdivisions.json'

import ActivityLogs from 'components/SalesPortalAdmin/ActivityLogs';
import SalesAdminPortalService from 'services/SalesAdminPortal'

import { Table, Button, Modal, Form, Input, Select, Row, Col, InputNumber, Alert, notification, Space, Card, message, Descriptions } from 'antd'

const {TextArea} = Input;

import {ImportOutlined, PrinterOutlined, LoadingOutlined, EditOutlined, DeleteOutlined, ExclamationCircleOutlined, UserOutlined} from '@ant-design/icons'

import ImportInventory from 'components/SalesPortalAdmin/ImportInventory';

const numberWithCommas = (x) => {
    if (!x) return '';
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

export default function Page(props) {

    // States
    const [searchString, setSearchString] = React.useState('');
    const [editModalOpen, setEditModalOpen] = React.useState(false);
    const [editFormData, setEditFormData] = React.useState({}); 
    const [errorMessages, setErrorMessages] = React.useState('');
    const [newRecordModalVisible, setNewRecordModalVisible] = React.useState(false);
    const [newFormData, setNewFormData] = React.useState({});
    const [priceUpdateModalVisible, setPriceUpdateModalVisible] = React.useState(false);
    const [selectedSubdivision, setSelectedSubdivision] = React.useState(null);
    const [selectedBlock, setSelectedBlock] = React.useState(null);
    const [selectedLot, setSelectedLot] = React.useState(null);
    const [priceUpdateSubdivision, setPriceUpdateSubdivision] = React.useState(null);
    const [activityLogRefetch, setActivityLogRefetch] = React.useState(false);

    const [selectedUpdateSubdivision, setSelectedUpdateSubdivision] = React.useState(null);
    const [selectedUpdatePhase, setSelectedUpdatePhase] = React.useState(null);
    const [selectedUpdateBlock, setSelectedUpdateBlock] = React.useState(null);
    const [selectedUpdateLot, setSelectedUpdateLot] = React.useState(null);
    const [selectedUpdateArea, setSelectedUpdateArea] = React.useState(null);
    const [priceUpdatePhase, setPriceUpdatePhase] = React.useState(null);
    const [priceUpdatePhases, setPriceUpdatePhases] = React.useState([]);
    const [priceUpdateBlock, setPriceUpdateBlock] = React.useState(null);
    const [priceUpdateBlocks, setPriceUpdateBlocks] = React.useState([]);
    const [priceUpdateLot, setPriceUpdateLot] = React.useState(null);
    const [priceUpdateLots, setPriceUpdateLots] = React.useState([]);
    const [priceUpdateType, setPriceUpdateType] = React.useState(null);
    const [priceUpdateTypes, setPriceUpdateTypes] = React.useState([]);
    const [priceUpdatePricePerSqm, setPriceUpdatePricePerSqm] = React.useState(0);
    const [priceUpdateTotalRecords, setPriceUpdateTotalRecords] = React.useState([]);
    const [filteredData, setFilteredData] = React.useState([]);
    const [tableFilters, setTableFilters] = React.useState({});

    const [dashboardData, setDashboardData] = React.useState({});    

    // Forms
    const [editForm] = Form.useForm();
    const [newRecordForm] = Form.useForm();
    const [priceUpdateForm] = Form.useForm();

    // Queries
    const lotInventoryList = SalesAdminPortalService.lotInventoryList('lot');
    const [activityLogQuery, {isLoading: activityLogQueryIsLoading, reset: activityLogQueryReset}] = SalesAdminPortalService.addInventoryActivityLogs(); 
    const [updateLotDetailsQuery, {isLoading: updateLotDetailsQueryIsLoading, reset: updateLotDetailsQueryReset}] = SalesAdminPortalService.updateLotDetails(); 
    const [deleteLotDetailsQuery, {isLoading: deleteLotDetailsQueryIsLoading, reset: deleteLotDetailsQueryReset}] = SalesAdminPortalService.deleteLotDetails(); 
    const [newLotRecordQuery, {isLoading: newLotRecordQueryIsLoading, reset: newLotRecordQueryReset}] = SalesAdminPortalService.newLotRecord(); 
    const [priceUpdateQuery, {isLoading: priceUpdateQueryIsLoading, reset: priceUpdateQueryReset}] = SalesAdminPortalService.priceUpdate(); 
    const [exportInventoryStatusReportQuery, { isLoading: exportInventoryStatusReportQueryIsLoading, reset: exportInventoryStatusReportQueryReset}] = SalesAdminPortalService.exportInventoryStatusReport();
    const [clientReservationQuery, {isLoading: clientReservationQueryIsLoading, reset: clientReservationQueryReset}] = SalesAdminPortalService.clientReservation(); 

    const getSubdivisionList = () => {
        if (lotInventoryList.data) {
            return _.uniqBy(lotInventoryList.data.map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
            .map( (i, key) => {
                return { value: i.subdivision, text: `${i.subdivision} ${i.name ?? ''}`}
            })
        } else {
            return [];
        }
    }

    const getAreaList = () => {
        if (lotInventoryList.data) {
            return _.sortBy(_.uniqBy(_.map(lotInventoryList.data, i => { return { value: i.area, text: i.area} }), 'value'), 'value');
        } else {
            return [];
        }
    }

    const getFilter = (type) => {
        if (lotInventoryList.data) {
            let data = _.sortBy(_.uniqBy(_.map(lotInventoryList.data, i => { 
                if (i[type]) {

                    if( selectedSubdivision !== null ) {
                        
                        return selectedSubdivision == i['subdivision'] ? 
                            { value: i[type], text: i[type], subdivision: i['subdivision'], block: i['block'] }
                            : false;

                    } else {
                        return { value: i[type], text: i[type], subdivision: i['subdivision'], block: i['block'] }
                    }


                    // return { value: i[type], text: i[type], subdivision: i['subdivision'], block: i['block'] }
                } else {
                    return { value: '0', text: 'No Phase'}
                }
            }), 'value'), 'value');

            return data;
        } else {
            return [];
        }
    }

    React.useEffect( () => {
        // Dashboard data

        if (lotInventoryList.data) {

            const status_count = _.countBy(lotInventoryList.data, 'status');

            const data = {
                status: status_count,
            }

            setDashboardData(data);
            setFilteredData(lotInventoryList.data);
        }
    }, [lotInventoryList.data]);
    

    const columns = [
        {
            // title: 'ID',
            // key: 'id',
            // dataIndex: 'id'
        },
        {
            title: 'Subdivision',
            key: 'subdivision',
            dataIndex: 'subdivision',
            render: (text, record) => `${record.subdivision_name ?? ''} (${record.subdivision})`, //<>{_.find(subdivisions, i => i.code == text ).subdivision} ({text})</>
            filters: getSubdivisionList(),
            width: '285px',
            defaultFilteredValue: [],
            onFilter: (value, record) => record.subdivision.includes(value),
        },

        {
            title: 'Phase',
            key: 'phase',
            dataIndex: 'phase',
            render: (text, record) => (record.phase == '' || record.phase ==  null ? "-" : record.phase),
            filters: selectedSubdivision ? _.filter(getFilter('phase'), i => (i.subdivision == selectedSubdivision)) : getFilter('phase'),
            // filters: getFilter('phase'),
            defaultFilteredValue: [],
            // filterSearch: true,
            onFilter: (value, record) => value == (record.phase == '' || record.phase ==  null ? "0" : record.phase), //record.area.includes(value),
        },

        {
            title: 'Block',
            key: 'block',
            dataIndex: 'block',
            filters: selectedSubdivision ? _.filter(getFilter('block'), i => (i.subdivision == selectedSubdivision)) : getFilter('block'),
            defaultFilteredValue: [],
            // filterSearch: true,
            onFilter: (value, record) => value == record.block, //record.area.includes(value),
        },

        {
            title: 'Lot', 
            key: 'lot',
            dataIndex: 'lot',
            render: (text, record) => (record.lot == '' || record.lot ==  null ? "-" : record.lot),
            filters: (selectedSubdivision) ? _.filter(getFilter('lot'), i => {
                        if (selectedBlock && (tableFilters && tableFilters.block && (tableFilters.block.length || tableFilters.block != null))) {
                            return i.subdivision == selectedSubdivision && (i.block == selectedBlock || tableFilters.block?.includes(i.block));
                        } else {
                            return (i.subdivision == selectedSubdivision);
                        }
                    }
                ) : getFilter('lot'),
            defaultFilteredValue: [],
            // filterSearch: true,
            onFilter: (value, record) => value == (record.lot == '' || record.lot ==  null ? "0" : record.lot)
        },

        {
            title: 'Area',
            key: 'area',
            dataIndex: 'area',
            filters: getAreaList(),
            defaultFilteredValue: [],
            filterSearch: true,
            onFilter: (value, record) => value == record.area, //record.area.includes(value),
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
            // onFilter: (value, record) => console.log(value),
        },

        {
            title: 'Price per sqm',
            key: 'price_per_sqm',
            dataIndex: 'price_per_sqm',
            filters: getFilter('price_per_sqm'),
            defaultFilteredValue: [],
            // filterSearch: true,
            onFilter: (value, record) => value == record.price_per_sqm, //record.area.includes(value),
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
            onFilter: (value, record) => record.status.includes(value),
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
            // filterSearch: true,
            onFilter: (value, record) => value == record.status2, //record.area.includes(value),
        },

        {
            title: 'TSP',
            render: (text, record) => {

                if (record.area && record.price_per_sqm) {
                    return <span className="text-success">&#8369; {numberWithCommas((parseFloat(record.area) * parseFloat(record.price_per_sqm)).toFixed(2))}</span>
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
                        typeof record.status !== 'undefined' && (record.status == 'reserved' || record.status == 'sold') &&
                        <Button style={{marginRight: '5px'}} size="small" onClick={()=>showBuyersDetails(params)}><UserOutlined /></Button>
                    }
                    <Button style={{marginRight: '5px'}} size="small" onClick={()=>handleEditButtonClick(record)}><EditOutlined /></Button>
                    <Button size="small" disabled={['pending_migration', 'reserved', 'sold'].includes(record.status) ? true : false} onClick={()=>handleDeleteButtonClick(record)}><DeleteOutlined /></Button>
                </>
                
            }
        },
    ];

    const handleSearch = (e) => {
        // console.log(e.target.value);
        setSearchString(e.target.value.toLowerCase());
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

    const handleEditButtonClick = (record) => {

        setEditFormData(record);
        editForm.setFieldsValue(record);

        setEditModalOpen(true);
    }

    const handleDeleteButtonClick = (record) => {
        Modal.confirm({
            title: 'Delete',
            icon: <ExclamationCircleOutlined />,
            content: 'Are you sure you want to delete lot inventory record?',
            okText: 'OK',
            cancelText: 'Cancel',
            onOk: () => {
                if (deleteLotDetailsQueryIsLoading) {
                    return false;
                }
        
                deleteLotDetailsQuery(record, {
                    onSuccess: (res) => {
                        notification.success({
                            message: 'Lot record successfully deleted!',
                            description:
                              '',
                          });

                        addInventoryActivityLog({
                            type: 'lot',
                            action: 'delete',
                            description: 'Lot inventory is deleted',
                            details: record
                        });
        
                        lotInventoryList.refetch();
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
                ActivityLogQueryReset();
                setErrorMessages(e.message);
            }
        })
    }

    // React.useEffect( () => {
    // }, [selectedSubdivision, selectedBlock, selectedLot]);

    React.useEffect( () => {
 
        setSelectedBlock('');
        setSelectedLot('');

    }, [selectedSubdivision]);

    React.useEffect( () => {

        setSelectedLot('');

    }, [selectedBlock]);

    React.useEffect( () => {

        if (lotInventoryList.data) {
            setFilteredData( () => 
                        lotInventoryList.data
                        .map( i => ({...i, type: i.type == "" ? "NO TYPE" : i.type}))
                        .filter(item => {
                            if (tableFilters.block?.length) {
                                return tableFilters.block.includes(item.block);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.area?.length) {
                                return tableFilters.area.includes(item.area);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.lot?.length) {
                                return tableFilters.lot.includes(item.lot);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.phase?.length) {
                                return tableFilters.phase.includes(item.phase);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.type?.length) {
                                return tableFilters.type.includes(item.type);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.price_per_sqm?.length) {
                                return tableFilters.price_per_sqm.includes(item.price_per_sqm);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.status?.length) {
                                return tableFilters.status.includes(item.status);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.status2?.length) {
                                return tableFilters.status2.includes(item.status2);
                            }
                            return true;
                        })
                        .filter(item => {
                            if (tableFilters.subdivision?.length) {
                                return tableFilters.subdivision.includes(item.subdivision);
                            }
                            return true;
                        })
                        .filter( item => {
                            if (item && selectedSubdivision) {
                                if (selectedSubdivision == item.subdivision) {
                                    // console.log(selectedBlock, item.block);
                                    if (selectedBlock && selectedBlock == item.block) {
                                        if ((selectedBlock && selectedLot) && selectedLot == item.lot) {
                                            return item;
                                        } else if (!selectedLot) {
                                            return item;
                                        }
                                    } else if (!selectedBlock) {
                                        return item;
                                    }
                                }
                            } else {
                                return item;
                            }
                        })
            );
        }

    }, [selectedBlock, selectedLot, selectedSubdivision])

    React.useEffect( () => {

        if (!editModalOpen) {
            editForm.resetFields();
            setErrorMessages('');
        }

    }, [editModalOpen]);

    React.useEffect( () => {

        if (priceUpdateSubdivision && priceUpdatePhase && priceUpdateBlock && priceUpdateLot && priceUpdateType) {

            // lotInventoryList get phases
            // console.log(_.uniq(_.filter(lotInventoryList.data, i => i.subdivision == priceUpdateSubdivision).map( i => i.phase), 'phase'));

            setPriceUpdatePhases(_.uniq(_.filter(lotInventoryList.data, i => i.subdivision == priceUpdateSubdivision).map( i => i.phase), 'phase'));
            setPriceUpdateBlocks(_.uniq(_.filter(lotInventoryList.data, i => i.subdivision == priceUpdateSubdivision).map( i => i.block), 'block'));
            setPriceUpdateLots(_.uniq(_.filter(lotInventoryList.data, i => i.subdivision == priceUpdateSubdivision).map( i => i.lot), 'lot'));
            setPriceUpdateTypes(_.uniq(_.filter(lotInventoryList.data, i => i.subdivision == priceUpdateSubdivision).map( i => i.type), 'type'));

        } else {
            setPriceUpdatePhases([]);
            setPriceUpdateBlocks([]);
            setPriceUpdateLots([]);
            setPriceUpdateTypes([]);
        }

    }, [priceUpdateSubdivision]);

    React.useEffect( () => {

        // console.log(priceUpdatePhase);

        const phase = priceUpdatePhase == 'NO_PHASE' ? null : priceUpdatePhase;
        const block = priceUpdateBlock == 'NO_BLOCK' ? null : priceUpdateBlock;
        const lot = priceUpdateLot == 'NO_BLOCK' ? null : priceUpdateLot;
        const type = priceUpdateType == 'NO_TYPE' ? null : priceUpdateType;

        if (priceUpdateSubdivision && priceUpdatePhase && priceUpdateBlock && priceUpdateLot && priceUpdateType) {

            const result = _.filter(lotInventoryList.data, i => {
                if (i.subdivision == priceUpdateSubdivision && i.phase == phase && i.type == type && i.block == block && i.lot == lot && i.status !== 'reserved' && i.status !== 'sold' && i.status !== 'pending_migration') {
                    return i;
                }
            });

            // console.log(result);

            setPriceUpdateTotalRecords(result);

            priceUpdateForm.setFieldsValue({
                ...priceUpdateForm,
                price_per_sqm: result.length ? result[0].price_per_sqm : null
            });
        }

    }, [priceUpdateSubdivision, priceUpdatePhase, priceUpdateBlock, priceUpdateLot, priceUpdateType]);

    const editFormOnFinish = (values) => {
        // console.log(values);

        if (updateLotDetailsQueryIsLoading) {
            return false;
        }

        updateLotDetailsQuery(values, {
            onSuccess: (res) => {
                console.log(res);
                setEditModalOpen(false);
                notification.success({
                    message: 'Lot record updated!',
                    description:
                      '',
                  });

                addInventoryActivityLog({
                    type: 'lot',
                    action: 'update',
                    description: 'Lot inventory is updated',
                    details: values
                });

                lotInventoryList.refetch();
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
                setNewRecordModalVisible(false);
                notification.success({
                    message: 'Lot record saved!',
                    description:
                      '',
                  });

                addInventoryActivityLog({
                    type: 'lot',
                    action: 'add',
                    description: 'New lot record added',
                    details: values
                });

                lotInventoryList.refetch();
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

        // const operation = priceUpdateForm.getFieldValue('operation');
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
                        type: 'lot',
                        action: 'update',
                        description: 'Inventory price updated',
                        details: values
                    });
    
                    lotInventoryList.refetch();
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
            property_type: 'lot',
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

    return (
        <>
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
                            ]} name="subdivision" label="Subdivision">
                                <Select style={{width: '100%'}} onChange={(e) => {
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
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( i => { 
                                                return { subdivision: i.subdivision, name: i.subdivision_name} 
                                            }), 'subdivision')
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
                                    {/* {
                                        priceUpdatePhases.map( (i, key) => {
                                            return <Select.Option key={key} value={i ? i : 'NO_PHASE'}>{i ? i : 'No phase'}</Select.Option>
                                        })
                                        
                                    } */}
                                    {
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( (i) => {
                                                if( i.subdivision == selectedUpdateSubdivision ){
                                                    return i;
                                                }
                                            }), 'phase').map( (i, key ) => {
                                                if( typeof i !== 'undefined' ) {
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
                            ]} name="block" label="Block">
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
                                    {/* {
                                        priceUpdateBlocks.map( (i, key) => {
                                            return <Select.Option key={key} value={i ? i : 'NO_BLOCK'}>{i ? i : 'No block'}</Select.Option>
                                        })
                                    } */}
                                    {
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( (i) => {
                                                if( i.phase == selectedUpdatePhase && i.subdivision == selectedUpdateSubdivision ){
                                                    return i;
                                                }
                                            }), 'block').map( (i, key ) => {
                                                if( typeof i !== 'undefined' ) {
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
                            ]} name="lot" label="Lot">
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
                                    {/* {
                                        priceUpdateLots.map( (i, key) => {
                                            return <Select.Option key={key} value={i ? i : 'NO_LOT'}>{i ? i : 'No Lot'}</Select.Option>
                                        })
                                    } */}
                                    {
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( (i) => {
                                                if( i.block == selectedUpdateBlock && i.phase == selectedUpdatePhase && i.subdivision == selectedUpdateSubdivision ){
                                                    return i;
                                                }
                                            }), 'lot').map( (i, key ) => {
                                                if( typeof i !== 'undefined' ) {
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
                                    {/* {
                                        priceUpdateTypes.map( (i, key) => {
                                            return <Select.Option key={key} value={i ? i : 'NO_TYPE'}>{i ? i : 'No type'}</Select.Option>
                                        })
                                    } */}
                                    {
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( (i) => {
                                                if( i.lot == selectedUpdateLot && i.block == selectedUpdateBlock && i.phase == selectedUpdatePhase && i.subdivision == selectedUpdateSubdivision){
                                                    return i;
                                                }
                                            }), 'type').map( (i, key ) => {
                                                if( typeof i !== 'undefined' ) {
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
                            ]} name="price_per_sqm" label="Price per sqm">
                                <InputNumber min="0" style={{width: '100%'}} />
                            </Form.Item>

                            <Form.Item name="property_type" label="Property Type" style={{display: 'none'}} initialValue='lot'>
                                <Input defaultValue='lot' value='lot' type="hidden"/>
                            </Form.Item>
                        </Col>
                        {/* <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="operation" label="Increase/Descrease">
                                <Select style={{width: '100%'}}>
                                    <Select.Option value="increase">Increase</Select.Option>
                                    <Select.Option value="decrease">Decrease</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="amount" label="Amount per sqm">
                                <InputNumber min="0" style={{width: '100%'}} />
                            </Form.Item>
                        </Col> */}

                        {/* <Col xl={24}>
                            <Alert message="Selected subdivision will increase/decrease their prices per sqm." />
                        </Col> */}
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
            <Modal
                visible={newRecordModalVisible}
                onCancel={() => setNewRecordModalVisible(false)}
                footer={null}
                title="New lot record"
            >
                <Form form={newRecordForm}
                    initialValues={{
                        status: 'for_review',
                        property_type: 'lot',
                    }}
                    onFinish={handleNewRecordFormFinish}
                >
                    <Row gutter={[8, 8]}>
                        <Col xl={24}>
                            <Form.Item rules={[
                                {
                                    required: true
                                }
                            ]} name="subdivision" label="Subdivision">
                                <Select style={{width: '100%'}}>
                                    {
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
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
                            ]} name="lot" label="Lot">
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
                            ]} name="price_per_sqm" label="Price per sqm (PHP)">
                                <InputNumber onChange={(e)=>setNewFormData({ ...newFormData, price_per_sqm: e})} style={{width: '100%'}} />
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
                                <Input defaultValue='lot' value='lot' type="hidden"/>
                            </Form.Item>
                        </Col>
                        <Col xl={24}>
                            <div>Total Selling Price: <span className="text-success">&#8369; {numberWithCommas(parseFloat(newFormData.price_per_sqm * newFormData.area))}</span><br/><small className="text-secondary">Price per sqm x Area</small></div>
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
            <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>
                <Space>
                    <Button onClick={() => handleNewRecordClick()}>New Lot Record</Button>
                    <Button onClick={() => setPriceUpdateModalVisible(true)}>Price Update</Button>
                    <ActivityLogs type="lot" refetch={activityLogRefetch} refetchSetter={setActivityLogRefetch}/>
                </Space>
                <Space>
                    <ImportInventory property_type="lot" lotInventoryList={lotInventoryList} addActivityLog={addInventoryActivityLog} />
                    <Button onClick={() => handleExportInventoryStatusReport()} icon={<PrinterOutlined/>}>Export Inventory Status Report</Button>
                </Space>
            </div>
            <Modal
                visible={editModalOpen}
                onCancel={()=>setEditModalOpen(false)}
                onOk={()=>editForm.submit()}
                title={"Edit lot details"}
                forceRender
            >
                <Form form={editForm} layout="vertical" onFinish={editFormOnFinish}>
                    <Form.Item name="id" style={{display: 'none'}}><Input/></Form.Item>
                    <Row gutter={[8, 8]}>
                        <Col xl={12}>
                            <Form.Item name="subdivision" label="Subdivision">
                                <Select>
                                    {
                                        lotInventoryList.data &&
                                            _.uniqBy(lotInventoryList.data.map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
                                            .map( (i, key) => {
                                                return <Select.Option key={key} value={i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
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
                            <Form.Item name="lot" label="Lot" rules={[{ required: true }]}>
                                <InputNumber placeholder="Lot"/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="block" label="Block" rules={[{ required: true }]}>
                                <Input placeholder="Block"/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item name="area" label="Area" rules={[{ required: true }]}>
                                <InputNumber 
                                    onChange={(e)=>setEditFormData({ ...editFormData, area: e})} 
                                    placeholder="Area"
                                    disabled={['reserved', 'sold'].includes(editFormData.status)}
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
                                <Select disabled={['available', 'reserved', 'sold'].includes(editFormData.status)}>
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

                        <Col xl={12}>
                            <Form.Item name="price_per_sqm" label="Price per sqm (PHP)">
                                <InputNumber disabled={['reserved', 'sold'].includes(editFormData.status)} onChange={(e)=>setEditFormData({ ...editFormData, price_per_sqm: e})} style={{width: '100%'}} />
                            </Form.Item>
                        </Col>

                        <Col xl={24}>
                            <Form.Item name="remarks" label="Remarks">
                                <TextArea style={{borderRadius: '12px'}} rows={4} placeholder="Remarks" />
                            </Form.Item>
                        </Col>
                        
                    </Row>
                </Form>

                <div>Total Selling Price: <span className="text-success">&#8369; {numberWithCommas(parseFloat(editFormData.price_per_sqm * editFormData.area))}</span><br/><small className="text-secondary">Price per sqm x Area</small></div>

                {
                    errorMessages != '' && <Alert style={{marginTop: 16}} message={errorMessages} type="warning" />
                }
            </Modal>

            <div className="mt-4">
                {
                        <Row gutter={[16,16]}>

                            <Col xl={4}>
                                <Card title="Available" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                {
                                    lotInventoryList.isLoading || lotInventoryList.isFetching ?
                                        <LoadingOutlined/>
                                    :
                                        (typeof dashboardData?.status?.available != 'undefined') ? numberWithCommas(dashboardData?.status?.available) : 0
                                }
                                </Card>
                            </Col>
                            <Col xl={4}>
                                <Card title="Reserved" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                {
                                    lotInventoryList.isLoading || lotInventoryList.isFetching ?
                                        <LoadingOutlined/>
                                    :
                                        (typeof dashboardData?.status?.reserved != 'undefined') ? numberWithCommas(dashboardData?.status?.reserved) : 0
                                }
                                </Card>
                            </Col>
                            <Col xl={4}>
                                <Card title="Sold" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                {
                                    lotInventoryList.isLoading || lotInventoryList.isFetching ?
                                        <LoadingOutlined/>
                                    :
                                        (typeof dashboardData?.status?.sold != 'undefined') ? numberWithCommas(dashboardData?.status?.sold) : 0
                                }
                                </Card>
                            </Col>
                            <Col xl={4}>
                                <Card title="Pending migration" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                {
                                    lotInventoryList.isLoading || lotInventoryList.isFetching ?
                                        <LoadingOutlined/>
                                    :
                                        (typeof dashboardData?.status?.pending_migration != 'undefined') ? numberWithCommas(dashboardData?.status?.pending_migration) : 0
                                }
                                </Card>
                            </Col>
                            <Col xl={4}>
                                <Card title="Not saleable" size="small" headStyle={{background: '#1177fa', color: 'white'}}>
                                {
                                    lotInventoryList.isLoading || lotInventoryList.isFetching ?
                                        <LoadingOutlined/>
                                    :
                                        (typeof dashboardData?.status?.not_saleable != 'undefined') ? numberWithCommas(dashboardData?.status?.not_saleable) : 0
                                }
                                </Card>
                            </Col>

                        </Row>
                }
            </div>

            <Card size="small">
                Search:
                <Space>
                    <Select style={{ width: 300, marginLeft: 16 }} placeholder="Subdivision" value={selectedSubdivision} onChange={e => {
                        setSelectedSubdivision((e == '' ) ? null : e)
                    }}>
                        <Select.Option value=""></Select.Option>
                        {
                            lotInventoryList.data &&
                                _.uniqBy(lotInventoryList.data
                                    .map( i => { return { subdivision: i.subdivision, name: i.subdivision_name} }), 'subdivision')
                                .map( (i, key) => {
                                    return <Select.Option key={key} value={i.subdivision}>{i.subdivision} {i.name ? `- ${i.name}` : ''}</Select.Option>
                                })
                        }
                    </Select>
                    <Select style={{width: 150}} onChange={e => setSelectedBlock(e)} value={selectedBlock}>
                        <Select.Option value=""></Select.Option>
                        {
                            _.uniqBy(_.filter(lotInventoryList.data, i => i.subdivision == selectedSubdivision).filter( item => {
                                if (item && selectedSubdivision) {
                                    if (selectedSubdivision == item.subdivision) {
                                        // console.log(selectedBlock, item.block);
                                        if (selectedBlock && selectedBlock == item.block) {
                                            if ((selectedBlock && selectedLot) && selectedLot == item.lot) {
                                                return item;
                                            } else if (!selectedLot) {
                                                return item;
                                            }
                                        } else if (!selectedBlock) {
                                            return item;
                                        }

                                        
                                    }
                                } else {
                                    return item;
                                }
                            }), 'block')
                            .map( (i, key) => {
                                return <Select.Option key={key} value={i.block}>Block {i.block}</Select.Option>
                            })
                        }
                    </Select>
                    <Select style={{width: 150}} onChange={e => setSelectedLot(e)} value={selectedLot}>
                        <Select.Option value=""></Select.Option>
                        {
                            _.filter(lotInventoryList.data, i => (i.subdivision == selectedSubdivision && i.block == selectedBlock))
                            .map( (i, key) => {
                                return <Select.Option key={key} value={i.lot}>Lot {i.lot}</Select.Option>
                            })
                        }
                    </Select>
                </Space>
            </Card>


            <Table
                rowKey="id"
                columns={columns}
                dataSource={filteredData}
                summary={(currentData) => {
                    return (
                        <div className="p-2 text-left">
                            <span><strong>Total:</strong> {numberWithCommas(filteredData.length)}</span><br/>
                        </div>
                    )
                }}
                onChange={
                    (pagination, filters, sorter, extra) => {

                        console.log(filters);

                        setTableFilters(filters);

                         setFilteredData(() =>
                            lotInventoryList.data
                            .map( i => ({...i, type: i.type == "" ? "NO TYPE" : i.type}))
                            .filter(item => {
                                if (filters.block?.length) {
                                    return filters.block.includes(item.block.toString());
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.area?.length) {
                                    return filters.area.includes(item.area.toString());
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.lot?.length) {
                                    let item_lot = item.lot;
                                    if( item.lot != null && typeof item.lot != 'object' && item.lot != '' ) {
                                        item_lot = item_lot.toString();
                                    } else {
                                        item_lot = '0';
                                    }
                                    return filters.lot.includes(item_lot);
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.phase?.length) {
                                    let item_phase = item.phase;
                                    if( item.phase != null && typeof item.phase != 'object' && item.phase != '' ) {
                                        item_phase = item_phase.toString();
                                    } else {
                                        item_phase = '0';
                                    }
                                    return filters.phase.includes(item_phase);
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.type?.length) {
                                    return filters.type.includes(item.type);
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.price_per_sqm?.length) {
                                    return filters.price_per_sqm.includes(item.price_per_sqm);
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.status?.length) {
                                    return filters.status.includes(item.status);
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.status2?.length) {
                                    return filters.status2.includes(item.status2);
                                }
                                return true;
                            })
                            .filter(item => {
                                if (filters.subdivision?.length) {
                                    return filters.subdivision.includes(item.subdivision);
                                }
                                return true;
                            })
                            .filter( item => {
                                if (item && selectedSubdivision) {
                                    if (selectedSubdivision == item.subdivision) {
                                        // console.log(selectedBlock, item.block);
                                        if (selectedBlock && selectedBlock == item.block) {
                                            if ((selectedBlock && selectedLot) && selectedLot == item.lot) {
                                                return item;
                                            } else if (!selectedLot) {
                                                return item;
                                            }
                                        } else if (!selectedBlock) {
                                            return item;
                                        }

                                        
                                    }
                                } else {
                                    return item;
                                }
                            })
                        );
                    } 
               } 
            />
        </>
    )
}