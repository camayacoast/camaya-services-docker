import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import GuestService from 'services/Booking/GuestService'
import { QRCode } from 'react-qrcode-logo'
import TicketIcon from 'assets/ticket-alt-solid.svg'
import ViewBookingComponent from 'components/Booking/View'

import { Table, Button, Typography, Row, Col, Card, Select, DatePicker, Modal, Input } from 'antd'
import Icon, { EditOutlined, QrcodeOutlined } from '@ant-design/icons'


function Page(props) {

    // States
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);
    const [bookingToView, setbookingToView] = React.useState(null);
    const [selectedDate, setselectedDate] = React.useState(moment());
    const [searchString, setSearchString] = React.useState(null);

    const guestListQuery = GuestService.hotelGuestList(selectedDate);

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
                rowKey="reference_number"
                columns={[
                    {
                        title: 'Room #',
                        render: (text, record) => {
                            return _.map(record.booking.room_reservations, i => {
                                return `${i.room.property.code} ${i.room.number}`
                            }).join(', ')
                        }
                    },
                    {
                        title: 'Booking Reference #',
                        dataIndex: 'booking_reference_number',
                        key: 'booking_reference_number',
                        render: (text, record) => <Button type="link" onClick={()=>setbookingToView(record.booking_reference_number)}>{text}</Button>
                    },
                    {
                        title: 'Check-in date',
                        render: (text, record) => moment(record.booking.start_datetime).format('MMM D, YYYY')
                    },
                    {
                        title: 'Check-out date',
                        render: (text, record) => moment(record.booking.end_datetime).format('MMM D, YYYY')
                    },
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
                        title: 'Status',
                        dataIndex: 'status',
                        key: 'status',
                    },
                    {
                        title: 'Market segmentation',
                        render: (text, record) => record.market_segmentation.join(', ')
                    },

                    //
                    // {
                    //     title: 'Action',
                    //     dataIndex: 'action',
                    //     key: 'action',
                    //     render: () => <Button icon={<EditOutlined/>} onClick={()=>console.log('edit action')} />
                    // },
                ]}
            />
        </>
    )
}

export default Page;