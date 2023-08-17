import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import GuestService from 'services/Booking/GuestService'
import BookingService from 'services/Booking'
import { QRCode } from 'react-qrcode-logo'
import TicketIcon from 'assets/ticket-alt-solid.svg'
import {queryCache} from 'react-query';
import ViewBookingComponent from 'components/Booking/View'

import { Table, Button, Typography, Modal, Row, Col, Card, Select, DatePicker, Tag, Form, Input, notification } from 'antd'
import Icon, { EditOutlined, QrcodeOutlined, UserOutlined } from '@ant-design/icons'



function Page(props) {

    // States
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
    const [bookingToView, setbookingToView] = React.useState(null);
    const [selectedDate, setselectedDate] = React.useState(moment());
    const [searchString, setSearchString] = React.useState(null);
    const [viewGuestModalVisible, setViewGuestModalVisible] = React.useState(false);

    // Get
    const guestListQuery = GuestService.list(selectedDate);
    // Post, Put
    const [updateGuestQuery, {isLoading: updateGuestQueryIsLoading, error: updateGuestQueryError}] = BookingService.updateGuest();
    
    // Forms
    const [viewGuestForm] = Form.useForm();

    React.useEffect(()=> {
        if (bookingToView) {
            setviewBookingModalVisible(true);
        }
    },[bookingToView]);

    React.useEffect(()=> {
        // console.log(dashboardDataQuery.data);
        guestListQuery.refetch();
    },[selectedDate]);

    const searchGuest = (search) => {
        setSearchString(search.toLowerCase());
    }

    const viewGuestModal = (record) => {
        viewGuestForm.resetFields();
        viewGuestForm.setFieldsValue(record);
        setViewGuestModalVisible(true);
    }

    const onViewGuestFormFinish = (values) => {
        if (updateGuestQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        updateGuestQuery(values, {
            onSuccess: (res) => {
                // console.log(res.data);      
                queryCache.invalidateQueries("guests");
                
                setViewGuestModalVisible(false);

                notification.success({
                    message: `Guest Updated!`,
                    description:
                        ``,
                });
            },
        });        
    }

    return (
        <>
            { bookingToView && 
                <Modal
                    visible={viewBookingModalVisible}
                    width="100%"
                    style={{ top: 16 }}
                    onCancel={()=> { setviewBookingModalVisible(false); setbookingToView(null); }}
                    footer={null}
                >
                    <ViewBookingComponent referenceNumber={bookingToView} />
                </Modal>
            }
            {/* <Typography.Title level={5}>Today's guests</Typography.Title> */}
            <div>
                {/* Jump to date: <DatePicker/> */}
                Jump to date: <DatePicker allowClear={false} value={selectedDate} onChange={(e)=>setselectedDate(e)} className="mx-2" />
            </div>

            <Input style={{width: 400}} type="text" placeholder="Search guest by name, guest or booking ref #" size="large" className="my-3" onChange={(e) => searchGuest(e.target.value)} />

            <Table
                size="small"
                dataSource={guestListQuery.data &&
                    guestListQuery.data
                    .filter(item => {
                        if (item && searchString) {
                            const searchValue =  item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase() + ' ' + item.reference_number.toLowerCase() + ' ' + item.booking_reference_number.toLowerCase();
                            return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                        }
                        return true;
                    })
                }
                loading={guestListQuery.isLoading}
                rowKey="id"
                columns={[
                    { 
                        title: 'Booking',
                        children: [
                            {
                                title: 'Booking Reference #',
                                dataIndex: 'booking_reference_number',
                                key: 'booking_reference_number',
                                render: (text, record) => <Button type="link" onClick={()=>setbookingToView(record.booking_reference_number)}>{text}</Button>
                            },
                            {
                                title: 'Booking type',
                                dataIndex: 'booking_type',
                                key: 'booking_type',
                                render: (text, record) => <>{record.booking.type == 'DT' ? 'Day Tour' : 'Overnight'}</>
                            },
                            {
                                title: 'Booking status',
                                dataIndex: 'booking_status',
                                key: 'booking_status',
                                render: (text, record) => <>{record.booking.status}</>
                            },
                            {
                                title: 'Primary customer',
                                dataIndex: 'primary_customer',
                                key: 'primary_customer',
                                render: (text, record) => <>
                                    <UserOutlined/> <span style={{textTransform:'uppercase'}}>{record.booking.customer.first_name} {record.booking.customer.last_name}</span>
                                    { record.booking.customer.user_type ? <Tag className="ml-2">{record.booking.customer.user_type}</Tag> :  ''}
                                </>
                            },
                        ]
                    },
                    { 
                        title: 'Guest',
                        children: [
                            {
                                title: 'Guest Reference #',
                                dataIndex: 'reference_number',
                                key: 'reference_number',
                                render: (text, record) => <><QrcodeOutlined style={{verticalAlign:'middle', marginRight: 8, fontSize:'2rem'}} onClick={()=>
                                                                Modal.confirm({
                                                                    icon: null,
                                                                    width: 1000,
                                                                    content: 
                                                                        <div>
                                                                            <div style={{display: 'flex', flexDirection:'row', justifyContent: 'space-between', alignItems: 'center'}}>
                                                                                <QRCode size={100} value={record.reference_number} logoWidth={25} logoImage={process.env.APP_URL+"/images/camaya-logo.jpg"} />
                                                                                <strong style={{fontSize: '2rem'}}>{record.reference_number}</strong>
                                                                                <div style={{fontSize: '2rem'}}>{record.first_name} {record.last_name}</div>
                                                                            </div>
                                                                            <div className="mt-5">
                                                                                <Typography.Title level={4}>Access passes</Typography.Title>
                                                                                {
                                                                                    record.passes && record.passes.map( (pass, key) => {
                                                                                        return  <Card size="small" key={key} extra={<>...</>} hoverable={true} className="mb-2 card-shadow" headStyle={{background:'#1177fa', color: 'white'}} title={<span style={{textTransform:'capitalize'}}><Icon component={TicketIcon} className="mr-2" />{pass.type.replace(/_/g, ' ')}</span>}>
                                                                                                    <Row gutter={[32, 32]} className="m-0">
                                                                                                        <Col xl={8}>
                                                                                                            {pass.pass_code}
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Access Code</small></div>
                                                                                                        </Col>
                                                                                                        <Col xl={8}>
                                                                                                            {/* {pass.status} */}
                                                                                                            <Select defaultValue={pass.status} onChange={(e) => console.log(e)}>
                                                                                                                <Select.Option value="created">created</Select.Option>
                                                                                                                <Select.Option value="consumed">consumed</Select.Option>
                                                                                                                <Select.Option value="used">used</Select.Option>
                                                                                                                <Select.Option value="voided">voided</Select.Option>
                                                                                                            </Select>
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Status</small></div>
                                                                                                        </Col>
                                                                                                        
                                                                                                        <Col xl={8}>
                                                                                                            <span className="text-success">{pass.count || <>&#8734;</>}</span>
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Remaining count</small></div>
                                                                                                        </Col>
                                                                                                        
                                                                                                        <Col xl={6}>
                                                                                                            {pass.category}
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Category</small></div>
                                                                                                        </Col>
                                                                                                        <Col xl={6}>
                                                                                                            {pass.interfaces.join(', ')}
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Interfaces</small></div>
                                                                                                        </Col>
                                                                                                        <Col xl={6}>
                                                                                                            {/* {pass.usable_at} */}
                                                                                                            <DatePicker defaultValue={moment(pass.usable_at)} allowClear={false} showTime onChange={(e) => console.log(e)} onOk={(e) => console.log(e)} />
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Usable at</small></div>
                                                                                                        </Col>
                                                                                                        <Col xl={6}>
                                                                                                            {/* {pass.expires_at} */}
                                                                                                            <DatePicker defaultValue={moment(pass.expires_at)} allowClear={false} showTime onChange={(e) => console.log(e)} onOk={(e) => console.log(e)} />
                                                                                                            <div><small style={{fontSize: '0.55rem'}} className="text-secondary">Expires at</small></div>
                                                                                                        </Col>
                                                                                                    </Row>
                                                                                                </Card>
                                                                                    })
                                                                                }
                                                                            </div>
                                                                        </div> 
                                                                })
                                                            }/>{record.reference_number}
                                                            </>
                            },
                            {
                                title: 'First name',
                                dataIndex: 'first_name',
                                key: 'first_name',
                                render: (text) => <strong style={{textTransform:'uppercase'}}>{text}</strong>
                            },
                            {
                                title: 'Last name',
                                dataIndex: 'last_name',
                                key: 'last_name',
                                render: (text) => <strong style={{textTransform:'uppercase'}}>{text}</strong>
                            },
                            {
                                title: 'Type',
                                dataIndex: 'type',
                                key: 'type',
                            },
                            {
                                title: 'Status',
                                dataIndex: 'status',
                                key: 'status',
                            },
                        ]
                    },

                    //
                    {
                        title: 'Action',
                        render: (text, record) => <Button icon={<EditOutlined/>} onClick={()=>viewGuestModal(record)} />
                    },
                ]}
            />
            <Modal 
                title={<Typography.Title level={4}>Guests</Typography.Title>}
                visible={viewGuestModalVisible}
                width={400}
                onOk={() => viewGuestForm.submit()}          
                onCancel={()=>setViewGuestModalVisible(false)}
            >
                <Form
                    form={viewGuestForm}
                    onFinish={onViewGuestFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                    // initialValues={}
                >
                    <Row>
                        <Form.Item name="id" noStyle>
                            <Input type="hidden" />
                        </Form.Item>
                        <Col xl={24}>
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
                        <Col xl={24}>
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
                        <Col xl={12}>
                            <Form.Item
                                name="age"
                                label="Age"                                       
                                rules={[
                                    {
                                        required: true
                                    }
                                ]}
                            >
                                <Input/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="type"
                                label="Type" 
                                rules={[
                                    {                                        
                                        validator: (rule, value, callback) => {
                                            try {
                                                const age = viewGuestForm.getFieldValue('age');
                                                if (age <= 2 && value !== 'infant') throw new Error('Please select correct type');
                                                if (age >= 3 && age <= 11 && value !== 'kid') throw new Error('Please select correct type');
                                                if (age >= 12 && value !== 'adult') throw new Error('Please select correct type');
                                                callback();
                                            } catch(e) {
                                                callback(e.message)
                                            }
                                        }
                                    }
                                ]}                                      
                            >
                                <Select>
                                    {['adult', 'kid', 'infant'].map((value, index) => <option key={index} value={value}>{_.capitalize(value)}</option>)}
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="nationality"
                                label="Nationality"                                       
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