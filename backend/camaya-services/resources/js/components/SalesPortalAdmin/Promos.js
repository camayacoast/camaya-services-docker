import React from 'react'

import { Table, Button, Modal, Form, Input, Select, Row, Col, Typography, Tag, InputNumber, Alert, notification, Space, Card, message, Descriptions } from 'antd'

import {ImportOutlined, PrinterOutlined, LoadingOutlined, EditOutlined, DeleteOutlined, ExclamationCircleOutlined, UserOutlined, PlusOutlined} from '@ant-design/icons'

const { Text } = Typography;
const { TextArea } = Input;

import SalesAdminPortalServices from 'services/SalesAdminPortal'

const Page = (props) => {
    
    const [addPromoQuery, {isLoading: addPromoQueryIsLoading, reset: addPromoQueryReset}] = SalesAdminPortalServices.addRealestatePromo();
    const [updatePromoQuery, {isLoading: updatePromoQueryIsLoading, reset: updatePromoQueryReset}] = SalesAdminPortalServices.updateRealestatePromo();
    const [deletePromoQuery, {isLoading: deletePromoQueryIsLoading, reset: deletePromoQueryReset}] = SalesAdminPortalServices.deleteRealestatePromo();

    const [searchString, setSearchString] = React.useState('');
    const [addModalVisible, setAddModalVisible] = React.useState(false);
    const [editModalOpen, setEditModalOpen] = React.useState(false);
    const [editFormData, setEditFormData] = React.useState({}); 
    const [sortedInfo, setSortedInfo] = React.useState({});

    const [editForm] = Form.useForm();
    const [addForm] = Form.useForm();

    const PromoList = SalesAdminPortalServices.realestatePromos();

    const validateMessages = {
        required: "${label} is required!",
        max: "$(label) allowed only ${max} characters!"
    };

    const getFilter = (type) => {
        if (PromoList.data) {
            let data = _.sortBy(_.uniqBy(_.map(PromoList.data, i => { 
                if (i[type]) {
                    return { value: i[type], text: i[type] }
                } else {
                    return false;
                }
            }), 'value'), 'value');

            return data;
        } else {
            return [];
        }
    }

    const handleSearch = (e) => {
        setSearchString(e.target.value.toLowerCase());
    }

    const handleAddButtonClick = () => {
        setAddModalVisible(true);
    }

    const handleAddFormFinish = (values) => {
        Modal.confirm({
            title: 'Add',
            icon: <ExclamationCircleOutlined />,
            content: 'Are you sure you want to add this promo?',
            okText: 'OK',
            cancelText: 'Cancel',
            onOk: () => {
                
                if (addPromoQueryIsLoading) {
                    return false;
                }
        
                addPromoQuery(values, {

                    onSuccess: (res) => {

                        if( res.status == 200 ) {
                            message.success(res.data.message);
                            setAddModalVisible(false);
                            addForm.resetFields();
                            PromoList.refetch();
                        } else {
                            message.info(res.data.message);
                        }
                    },

                    onError: (e) => {
                        message.error(e.message);
                        addPromoQueryReset();
                    }

                });

            }
        });
    }

    const handleEditButtonClick = (record) => {
        setEditFormData(record);
        editForm.setFieldsValue(record);
        setEditModalOpen(true);
    }

    const editPromo = (values) => {
        Modal.confirm({
            title: 'Update',
            icon: <ExclamationCircleOutlined />,
            content: 'Are you sure you want to update this promo?',
            okText: 'OK',
            cancelText: 'Cancel',
            onOk: () => {
                
                if (updatePromoQueryIsLoading) {
                    return false;
                }
        
                updatePromoQuery(values, {

                    onSuccess: (res) => {

                        if( res.status == 200 ) {
                            message.success(res.data.message);
                            setEditModalOpen(false);
                            PromoList.refetch();
                        } else {
                            message.info(res.data.message);
                        }
                    },

                    onError: (e) => {
                        message.error(e.message);
                        updatePromoQueryReset();
                    }

                });

            }
        });
    }

    const deletePromo = (record) => {

        Modal.confirm({
            title: 'Delete',
            icon: <ExclamationCircleOutlined />,
            content: 'Are you sure you want to delete this promo?',
            okText: 'OK',
            cancelText: 'Cancel',
            onOk: () => {
                
                if (deletePromoQueryIsLoading) {
                    return false;
                }
        
                deletePromoQuery(record, {

                    onSuccess: (res) => {
                        if( res.status == 200 ) {
                            message.success(res.data.message);
                            PromoList.refetch();
                        } else {
                            message.info(res.data.message);
                        }
                    },

                    onError: (e) => {
                        message.error(e.message);
                        deletePromoQueryReset();
                    }

                });

            }
        });
    }

    const columns = [
        {
            title: 'Name',
            key: 'name',
            dataIndex: 'name',
            render: (text, record) => {
                return (record.name == null || record.name == '') ?
                        <Text type="secondary" style={{color: '#dfdfdf'}}>No promo name</Text> : record.name
            },
            sorter: (a, b) => {
                // set Z on null and empty to display records at the end of the line
                let nameA = (a.name == null || a.name == '') ? 'z' : a.name;
                let nameB = (b.name == null || b.name == '') ? 'z' : b.name;
                return nameA.localeCompare(nameB)
            },
            className: 'vAlignTop',
        },
        {
            title: 'Promo Code',
            key: 'promo_type',
            dataIndex: 'promo_type',
            render: (text, record) => record.promo_type,
            sorter: (a, b) => {
                let typeA = (a.promo_type == null || a.promo_type == '') ? 'z' : a.promo_type;
                let typeB = (b.promo_type == null || b.promo_type == '') ? 'z' : b.promo_type;
                return typeA.localeCompare(typeB)
            },
            className: 'vAlignTop',
        },
        {
            title: 'Description',
            key: 'description',
            dataIndex: 'description',
            render: (text, record) => {
                return (record.description == null || record.description == '') ?
                        <Text type="secondary" style={{color: '#dfdfdf'}}>No promo description</Text> : record.description
            },
            width: '400px',
            className: 'vAlignTop',
        },
        {
            title: 'Status',
            key: 'status',
            dataIndex: 'status',
            render: (text, record) => {
                return (record.status == 'Active') ? <Tag color="green">Active</Tag> : <Tag color="red">Inactive</Tag>;
            },
            filters: getFilter('status'),
            onFilter: (value, record) => value == record.status,
            sorter: (a, b) => {
                let statusA = (a.status == null || a.status == '') ? 'z' : a.status;
                let statusB = (b.status == null || b.status == '') ? 'z' : b.status;
                return statusA.localeCompare(statusB)
            },
            className: 'vAlignTop',
        },
        {
            title: 'Action',
            render: (text, record) => {
                return <>
                    <Button style={{marginRight: '5px'}} size="small" onClick={()=>handleEditButtonClick(record)}><EditOutlined /></Button>
                    <Button size="small" onClick={() => deletePromo(record)}><DeleteOutlined /></Button>
                </>
                
            },
            className: 'vAlignTop',
        }
    ];

    return (
        <>
            <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>
                <Input style={{width: 300}} type="search" placeholder="Search promo" onChange={(e)=>handleSearch(e)} />
                <Button size='small' onClick={() => handleAddButtonClick()}><PlusOutlined />Add Promo</Button>
            </div>
            <Table
                rowKey="id"
                columns={columns}
                // dataSource={PromoList.data}
                dataSource={PromoList.data ? PromoList.data.filter(item => {
                    if (item && searchString) {
                        let item_name = (item.name == null || item.name == '') ? '' : item.name.toLowerCase();
                        let item_promo_type = (item.promo_type == null || item.promo_type == '') ? '' : item.promo_type.toLowerCase();
                        const searchValue =  item_name + " " + item_promo_type;
                        return searchString ? searchValue.indexOf(searchString.toLowerCase()) !== -1 : true;
                    }
                    return true;
                }) : []}
            />

            {/* Edit form */}
            <Modal
                visible={editModalOpen}
                onCancel={()=>setEditModalOpen(false)}
                onOk={()=>editForm.submit()}
                title={"Edit Promo Details"}
                forceRender
            >
                <Form form={editForm} layout="vertical" onFinish={editPromo} validateMessages={validateMessages}>
                    <Form.Item name="id" style={{display: 'none'}}><Input/></Form.Item>
                    <Row gutter={[8, 8]}>
                        
                        <Col xl={24}>
                            <Form.Item name="name" label="Name" max={250} rules={[{max: 250}]}>
                                <Input placeholder="Name" maxLength={250}/>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="promo_type" label="Promo Code" max={50} rules={[{required: true, max: 50}]}>
                                <Input placeholder="Promo Code" maxLength={50}/>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="status" label="Status">
                                <Select>
                                    <Select.Option value="Active">Active</Select.Option>
                                    <Select.Option value="Inactive">Inactive</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>

                        <Col xl={24}>
                            <Form.Item name="description" label="Description">
                                <TextArea style={{borderRadius: '12px'}} rows={4} placeholder="Description" />
                            </Form.Item>
                        </Col>
                        
                    </Row>
                </Form>
            </Modal>
            {/* End of edit form */}

            {/* Add form */}
            <Modal
                visible={addModalVisible}
                onCancel={() => setAddModalVisible(false)}
                onOk={()=>addForm.submit()}
                title="Add New Promo"
            >
                <Form form={addForm} layout="vertical" onFinish={handleAddFormFinish} validateMessages={validateMessages}>
                    <Row gutter={[8, 8]}>
                        
                    <Col xl={24}>
                            <Form.Item name="name" label="Name" max={250} rules={[{max: 250}]}>
                                <Input placeholder="Name" maxLength={250}/>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="promo_type" label="Promo Code" max={50} rules={[{required: true, max: 50}]}>
                                <Input placeholder="Promo Code" maxLength={50}/>
                            </Form.Item>
                        </Col>

                        <Col xl={12}>
                            <Form.Item name="status" label="Status" initialValue='Active'>
                                <Select>
                                    <Select.Option value="Active">Active</Select.Option>
                                    <Select.Option value="Inactive">Inactive</Select.Option>
                                </Select>
                            </Form.Item>
                        </Col>

                        <Col xl={24}>
                            <Form.Item name="description" label="Description">
                                <TextArea style={{borderRadius: '12px'}} rows={4} placeholder="Description" />
                            </Form.Item>
                        </Col>

                    </Row>
                </Form>
            </Modal>
            {/* End of add form */}
        </>
    )
}

export default Page;