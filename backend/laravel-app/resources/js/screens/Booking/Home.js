import React, {Suspense} from 'react'
import moment from 'moment'
import { NavLink, Switch, Route } from 'react-router-dom'
import BookingLayout from 'layouts/Booking'
import PageNotFound from 'common/PageNotFound'
import Loading from 'common/Loading'
import DashboardComponent from 'components/Booking/Dashboard'
import NewBookingComponent from 'components/Booking/New'
import ViewBookingComponent from 'components/Booking/View'
import SearchBookingComponent from 'components/Booking/SearchBooking'

const MyBookingsComponent = React.lazy(() => import('components/Booking/MyBookings'))
const ActiveBookingsComponent = React.lazy(() => import('components/Booking/ActiveBookings'))
const UpcomingBookingsComponent = React.lazy(() => import('components/Booking/UpcomingBookings'))
const PastBookingsComponent = React.lazy(() => import('components/Booking/PastBookings'))

import { Typography, Tabs, Button, Row, Col, Tooltip, Modal, Table, Space, Tag } from 'antd'
import { BookOutlined, PlusOutlined, SearchOutlined, FileSearchOutlined } from '@ant-design/icons'
import { connect } from 'react-redux'
import * as stateAction from 'store/actions'

const { TabPane } = Tabs;


function Page(props) {

    const tagColor2 = {
        draft: 'purple',
        pending: 'orange',
        confirmed: 'green',
        cancelled: 'red',
    };
    
    const columns = [
        {
          title: 'Reference #',
          dataIndex: 'reference_number',
          key: 'reference_number',
        },
        {
            title: 'Status',
            dataIndex: 'status',
            filters: [
                { text: 'draft', value: 'draft' },
                { text: 'pending', value: 'pending' },
                { text: 'confirmed', value: 'confirmed' },
                { text: 'cancelled', value: 'cancelled' },
            ],
            defaultFilteredValue: ['draft', 'pending', 'confirmed'],
            onFilter: (value, record) => record.status.includes(value),
            render: (text, record) => (
                <Tag color={tagColor2[text]}>{text}</Tag>
            ),
        },
        {
            title: 'Customer',
            dataIndex: 'customer',
            render: (text, record) => `${record.first_name} ${record.last_name}`,
        },
        {
            title: 'Pax',
            dataIndex: 'pax',
            render: (text, record) => `adult: ${record.adult_pax} kid: ${record.kid_pax} infant: ${record.infant_pax}`,
        },
        {
            title: 'Date of visit',
            dataIndex: 'date_of_visit',
            render: (text, record) => `${moment(record.start_datetime).format('MMM D, YYYY')} ${moment(record.end_datetime).isAfter(moment(record.start_datetime)) ? " ~ "+moment(record.end_datetime).format('MMM D, YYYY') : ''}`,
        },
        {
            title: 'Booked at',
            dataIndex: 'created_at',
            key: 'created_at',
            render: (text, record) => moment(record.created_at).fromNow(),
            
        },
        {
            title: 'Action',
            key: 'action',
            render: (text, record) => (
              <Space size="middle">
                <Button type="link" onClick={() => bookingPaneEdit(record.reference_number, 'view', record.status)}>View</Button>
              </Space>
            ),
        },
    ];

    const tagColor = {
        draft: 'text-purple',
        pending: 'text-warning',
        confirmed: 'text-success',
        cancelled: 'text-danger',
    };

    
    const [bookingPanes, setBookingPanes] = React.useState([]);
    const [bookingTabs, setBookingTabs] = React.useState(props.booking_tabs);
    const [bookingPaneActiveKey, setBookingPaneActiveKey] = React.useState(props.pane_active_key);
    const [counter, setCounter] = React.useState(1);
    const [searchBookingModalVisible, setsearchBookingModalVisible] = React.useState(false);

    const updateBookingTabs = (booking, type) => {

        console.log(booking, type);

        setBookingTabs(prev => {
            let tabs = [...prev];

            const exists = _.find(tabs, { reference_number: booking.reference_number });

            if (!exists && type == 'add') {
                tabs = [...prev, booking];
            } else if (type == 'remove') {
                tabs = _.filter(tabs, (e) => e.reference_number != booking.reference_number);
            } else if (type == 'update') {
                const remove_tab = _.filter([...prev], (e) => e.reference_number != booking.reference_number);
                tabs = [...remove_tab, booking];
            }

            return tabs;
        });
        
    }

    const bookingPaneEdit = (targetKey, action, status, searchResult = []) => {

        // console.log(bookingPanes);

        switch (action) {
            case 'add':
                const activeKey = `newTab${counter}`;

                setCounter(counter => counter + 1);
                setBookingPaneActiveKey(activeKey);
                setBookingPanes(prevPanes => [...prevPanes, {
                    title: `* New Booking ${counter}`,
                    content: <NewBookingComponent counter={counter} paneKey={activeKey} updateBookingTabs={updateBookingTabs} setBookingPaneActiveKey={setBookingPaneActiveKey} setBookingPanes={setBookingPanes} />,
                    key: activeKey
                }]);
            break;

            case 'remove':
                updateBookingTabs({reference_number: targetKey}, 'remove');
                let newActiveKey = bookingPaneActiveKey;
                const targetPaneIndex = _.findIndex(bookingPanes, { key: targetKey });

                bookingPanes.splice(targetPaneIndex, 1);
                newActiveKey = bookingPanes[bookingPanes.length-1].key;

                setBookingPaneActiveKey(newActiveKey);

                setBookingPanes([...bookingPanes]);
            break;
            
            case 'view':

                updateBookingTabs({reference_number: targetKey, status: status}, 'add');

                setBookingPaneActiveKey(targetKey);

                setBookingPanes(prevPanes => {

                    const paneExists = _.findIndex(prevPanes, { key: targetKey });

                    if (paneExists < 0) {

                        const newPanes = [...prevPanes, {
                            title: <><span className={tagColor[status]}>&bull;</span>{` View ${targetKey}`}</>,
                            content: <ViewBookingComponent bookingPaneEdit={bookingPaneEdit} referenceNumber={targetKey} />,
                            key: `${targetKey}`
                        }];

                        return newPanes;

                    }

                    return [...prevPanes];
                });
            break;

            case 'update':

                updateBookingTabs({reference_number: targetKey, status: status}, 'update');

                setBookingPaneActiveKey(targetKey);

                setBookingPanes(prevPanes => {

                    const paneExists = _.findIndex(prevPanes, { key: targetKey });

                    prevPanes[paneExists] = {
                        title: <><span className={tagColor[status]}>&bull;</span>{` View ${targetKey}`}</>,
                        content: <ViewBookingComponent updateBookingTabs={updateBookingTabs} referenceNumber={targetKey} />,
                        key: `${targetKey}`
                    };

                    return [...prevPanes];
                });
            break;

            case 'search':
                    
                    setBookingPaneActiveKey(targetKey);

                    Modal.destroyAll();

                    setBookingPanes(prevPanes => {

                        // const paneExists = _.findIndex(prevPanes, { key: targetKey });

                        // if (paneExists < 0) {

                            const newPanes = [...prevPanes, {
                                title: <><FileSearchOutlined /> Search {targetKey}</>,
                                // content: <SearchResultBookingComponent />,
                                // content: <>Search</>,
                                content: 
                                <>
                                    <Typography.Title level={4} className="my-4">Search Result Bookings</Typography.Title>
                                    <Table 
                                        // loading={myBookingsQuery.status === 'loading'}
                                        columns={columns}
                                        dataSource={searchResult}
                                        rowKey="reference_number"
                                        rowClassName="table-row"
                                        size="small"
                                        // onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
                                    />
                                </>,
                                key: `${targetKey}`
                            }];

                            return newPanes;

                        // }

                        // return [...prevPanes];
                    });
                break;
        }
        // console.log(targetPaneIndex, targetKey, bookingPanes);

    }

    React.useEffect( () => {

        let viewBooking = [];

        bookingTabs && bookingTabs.map( (booking, key) => {
            viewBooking.push({
                title: <><span className={tagColor[booking.status]}>&bull;</span>{` View ${booking.reference_number}`}</>,
                content: <ViewBookingComponent bookingPaneEdit={bookingPaneEdit} referenceNumber={booking.reference_number} />,
                key: `${booking.reference_number}`
            });
        });

        const panes = [
            {
                title: <><BookOutlined style={{color: "#1890ff"}}/>My Bookings</>,
                className: 'mybooking',
                content: (
                    <Suspense fallback={<Loading/>}><MyBookingsComponent bookingPaneEdit={bookingPaneEdit}/></Suspense>
                ),
                key: 'my-bookings',
                closable: false,
            },
            {
                title: <><BookOutlined style={{color: "#52c41a"}}/>Active Bookings</>,
                content: (
                    <Suspense fallback={<Loading/>}><ActiveBookingsComponent bookingPaneEdit={bookingPaneEdit}/></Suspense>
                ),
                key: 'active-bookings',
                closable: false,
            },
            {
                title: <><BookOutlined style={{color: "#fa8c16"}}/>Upcoming Bookings</>,
                content: (
                    <Suspense fallback={<Loading/>}><UpcomingBookingsComponent bookingPaneEdit={bookingPaneEdit}/></Suspense>
                ),
                key: 'upcoming-bookings',
                closable: false,
            },
            {
                title: <><BookOutlined/>Past Bookings</>,
                content: (
                    <Suspense fallback={<Loading/>}><PastBookingsComponent bookingPaneEdit={bookingPaneEdit}/></Suspense>
                ),
                key: 'past-bookings',
                closable: false,
            },
            ...viewBooking
        ];
        // console.log('booking panes changes', bookingPanes);
        setBookingPanes(panes);

        if (!_.includes(['my-bookings', 'active-bookings', 'upcoming-bookings', 'past-bookings', ..._.map(bookingTabs, e => e.reference_number)], props.pane_active_key)) {
            setBookingPaneActiveKey('my-bookings');
        } else {
            setBookingPaneActiveKey(props.pane_active_key);
        }
         
    }, []);

    React.useEffect( () => {
        props.dispatch(stateAction.updateBookingTabs(bookingTabs.filter( (a) => Object.keys(a).length !== 0)));
    }, [bookingTabs]);

    React.useEffect( () => {
        props.dispatch(stateAction.updateBookingPaneActiveKey(bookingPaneActiveKey));
    }, [bookingPaneActiveKey]);

    const SearchBooking = () => {
        return (
            <Tooltip title="search">
                <Button style={{marginRight: 8}} type="primary" shape="circle" icon={<SearchOutlined />} onClick={()=>setsearchBookingModalVisible(true)} />
            </Tooltip>
        )
    }

    return <BookingLayout {...props}>
        <div className="fadeIn">

            <Modal
                title={<><SearchOutlined className="text-primary mr-2" />Search Booking</>}
                visible={searchBookingModalVisible}
                onCancel={()=>setsearchBookingModalVisible(false)}
                footer={null}
                >
                    <SearchBookingComponent bookingPaneEdit={bookingPaneEdit} setsearchBookingModalVisible={setsearchBookingModalVisible}/>
            </Modal>

            <Typography.Title level={2}>Home</Typography.Title>

                {/* <Row gutter={[12,12]}>
                    <Col xl={12}>
                        <DashboardComponent />
                    </Col>
                </Row> */}
                
                <Tabs
                    animated={false}
                    type="editable-card"
                    onChange={(key) => setBookingPaneActiveKey(key)}
                    activeKey={bookingPaneActiveKey}
                    onEdit={bookingPaneEdit}
                    addIcon={<><PlusOutlined/> New Booking</>}
                    tabBarExtraContent={{
                        left: <SearchBooking/>
                    }}
                >
                    {bookingPanes.map(pane => (
                    <TabPane tab={pane.title} key={pane.key} closable={pane.closable}>
                        {pane.content}
                    </TabPane>
                    ))}
                </Tabs>
        </div>
    </BookingLayout>
}

const mapStateToProps = (state) => {
    return {
        booking_tabs: state.Booking.tabs,
        pane_active_key: state.Booking.paneActiveKey
    }
}

const mapDispatchToProps = (dispatch) => {
    return {
        dispatch: dispatch
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(Page);