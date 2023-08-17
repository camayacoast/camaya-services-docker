import React from 'react'
import moment from 'moment'
import CustomerService from 'services/Booking/Customer'
import { queryCache } from 'react-query'
import { Table, Button, Typography, Space, message, Popconfirm, Form, Modal, Row, Col, Input, notification } from 'antd'
import Icon, { EditOutlined, LinkOutlined, PrinterOutlined} from '@ant-design/icons'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;


function Page(props) {


    const customerListQuery = CustomerService.list();

    // Post, Put
    const [linkCustomerToUserQuery, {isLoading: linkCustomerToUserQueryIsLoading}] = CustomerService.linkToUser();
    const [updateCustomerAddressQuery, {isLoading: updateCustomerAddressQueryIsLoading}] = CustomerService.updateAddress();
    const [updateCustomerQuery, {isLoading: updateCustomerQueryIsLoading}] = CustomerService.update();

    const [viewCustomerModalVisible, setViewCustomerModalVisible] = React.useState(false);
    const [searchQuery, setSearchQuery] = React.useState("");
    const [viewCustomerForm] = Form.useForm();


    const handleLinkToUser = (id) => {
        console.log(id);

        linkCustomerToUserQuery({id:id}, {
            onSuccess: (res) => {
                console.log(res);
                queryCache.setQueryData(['customers'], prev => [...prev.filter(i=>i.id != res.data.customer.id), res.data.customer]);
                message.info('Linked customer to user.');
            },
            onError: (e) => {
                message.info(e.message);
            }
        })
    }

    const handleAddressChange = (id, address) => {
        console.log(id, address);

        updateCustomerAddressQuery({
            id: id,
            address: address,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Change address successful!");
                queryCache.setQueryData(['customers'], prev =>
                    {
                        const findIndex = _.findIndex(prev, i => i.id == res.data.id);

                        prev[findIndex] = res.data;

                        return [...prev];
                    }
                );
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const viewCustomerModal = (record) => {
        viewCustomerForm.resetFields();
        viewCustomerForm.setFieldsValue(record);
        setViewCustomerModalVisible(true);
    }

    const onViewCustomerFormFinish = (values) => {
        if (updateCustomerQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        updateCustomerQuery(values, {
            onSuccess: (res) => {
                // console.log(res.data);
                queryCache.invalidateQueries("customers");

                setViewCustomerModalVisible(false);

                notification.success({
                    message: `Customer Updated!`,
                    description:
                        ``,
                });
            },
            onError: (res) => {
                if (res.error !== 'CUSTOMER_EMAIL_EXIST') {
                    viewCustomerForm.setFields([{
                        name: 'email',
                        errors: ['Email already taken']
                    }]);
                } else {
                    notification.error({
                        message: `This customer does not exist!`,
                        description:
                            ``,
                    });
                    setViewCustomerModalVisible(false);
                    queryCache.invalidateQueries("customers");
                }                
            },
        });
    }

    const handleSearch = (search) => {
        if (search != "") {
          const _searchQuery = search != '' ? search.toLowerCase() : '';
          
          setSearchQuery(_searchQuery);
        } else {
          setSearchQuery("");
        }
      }

    return (
        <>
            <Typography.Title level={5}>Customers
                <div>
                    <Input
                    style={{width: '240px'}}
                    placeholder={`Search`}              
                    onPressEnter={(e) => handleSearch(e.target.value)}
                    onChange={(e) => handleSearch(e.target.value)}
                    />
                    <ExcelFile filename={`customers_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/> {customerListQuery.data && customerListQuery.data.length ? customerListQuery.data
                    .filter(item => {
                        if (item && searchQuery) {
                            const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.email.toLowerCase()  + ' ' + (item.user_type ? item.user_type : '').toLowerCase();
                            return searchQuery ? searchValue.indexOf(searchQuery) !== -1 : true;
                        }
                        return true;
                    }).length : ''}</Button>}>
                        <ExcelSheet data={customerListQuery.data ?
                                customerListQuery.data
                                .filter(item => {
                                    if (item && searchQuery) {
                                        const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.email.toLowerCase()  + ' ' + (item.user_type ? item.user_type : '').toLowerCase();
                                        return searchQuery ? searchValue.indexOf(searchQuery) !== -1 : true;
                                    }
                                    return true;
                                })
                                : []} name="customers">
                            <ExcelColumn label="First name" value="first_name"/>
                            <ExcelColumn label="Last name" value="last_name"/>
                            <ExcelColumn label="Email" value="email"/>
                            <ExcelColumn label="Contact number" value="contact_number"/>
                            <ExcelColumn label="Address" value="address"/>
                            <ExcelColumn label="User type" value="user_type"/>
                            <ExcelColumn label="Bookings count" value="bookings_count"/>
                        </ExcelSheet>
                    </ExcelFile>
                </div>
            </Typography.Title>
            <Table
                size="small"
                dataSource={customerListQuery.data ?
                    customerListQuery.data
                    .filter(item => {
                        if (item && searchQuery) {
                            const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.email.toLowerCase()  + ' ' + (item.user_type ? item.user_type : '').toLowerCase();
                            return searchQuery ? searchValue.indexOf(searchQuery) !== -1 : true;
                        }
                        return true;
                    })
                    :
                    []
                }
                loading={customerListQuery.isLoading}
                rowKey="id"
                columns={[
                    // {
                    //     title: 'ID',
                    //     dataIndex: 'id',
                    //     key: 'id',
                    // },
                    {
                        title: 'First name',
                        dataIndex: 'first_name',
                        key: 'first_name',
                    },
                    {
                        title: 'Last name',
                        dataIndex: 'last_name',
                        key: 'last_name',
                    },
                    {
                        title: 'Email',
                        dataIndex: 'email',
                        key: 'email',
                    },
                    {
                        title: 'Contact number',
                        dataIndex: 'contact_number',
                        key: 'contact_number',
                    },
                    {
                        title: 'Address',
                        dataIndex: 'address',
                        key: 'address',
                        render: (text, record) => <Typography.Paragraph editable={{ onChange: (e) =>  handleAddressChange(record.id, e) }}>{record.address ? record.address : ''}</Typography.Paragraph>
                    },
                    {
                        title: 'Registered',
                        dataIndex: 'registered',
                        key: 'registered',
                        render: (text,record) => <>{record.user ? <span>Yes ({record.user_type})</span> : 'No'}</>,
                    },
                    {
                        title: 'Bookings count',
                        dataIndex: 'bookings_count',
                        key: 'bookings_count',
                        render: (text,record) => <span className="text-primary">{record.bookings_count || 0}</span>,
                        sorter: (a, b) => a.bookings_count - b.bookings_count,
                    },

                    //
                    {
                        title: 'Action',
                        dataIndex: 'action',
                        key: 'action',
                        render: (text, record) => <Space>
                                    <Button icon={<EditOutlined/>} onClick={()=>viewCustomerModal(record)} />
                                    { (record.email_match && !record.user) &&

                                        <Popconfirm title="Are you sure？" okText="Yes" cancelText="No" onConfirm={()=>handleLinkToUser(record.id)}>
                                            <Button icon={<LinkOutlined />}>Link to user</Button>
                                        </Popconfirm>

                                    }
                                </Space>
                    },
                ]}
            />

            <Modal
                title={<Typography.Title level={4}>Customers</Typography.Title>}
                visible={viewCustomerModalVisible}
                width={600}
                onOk={() => viewCustomerForm.submit()}
                onCancel={()=>setViewCustomerModalVisible(false)}
            >
                <Form
                    form={viewCustomerForm}
                    onFinish={onViewCustomerFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                    // initialValues={}
                >
                    <Row>
                        <Form.Item name="id" noStyle>
                            <Input type="hidden" />
                        </Form.Item>
                        <Col xl={8}>
                            <Form.Item
                                name="first_name"
                                label="First Name"
                                rules={[
                                    {
                                        required: true
                                    }
                                ]}
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item
                                name="middle_name"
                                label="Middle Name"
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item
                                name="last_name"
                                label="Last Name"
                                rules={[
                                    {
                                        required: true
                                    }
                                ]}
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={16}>
                            <Form.Item
                                name="address"
                                label="Address"
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item
                                name="nationality"
                                label="Nationality"
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={16}>
                            <Form.Item
                                name="email"
                                label="Email"
                                rules={[
                                    {
                                        required: true
                                    }
                                ]}
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={8}>
                            <Form.Item
                                name="contact_number"
                                label="Contact Number"
                                rules={[
                                    {
                                        required: true
                                    }
                                ]}
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                    </Row>
                </Form>

            </Modal>
        </>
    )
}

export default Page;