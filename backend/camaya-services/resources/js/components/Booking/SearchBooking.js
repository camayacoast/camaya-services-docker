import React from 'react'
import moment from 'moment'
import BookingService from 'services/Booking'
import CustomerService from 'services/Booking/Customer'

import { Form, Input, Button, Tabs, notification, message, Select, DatePicker } from 'antd'
const { TabPane } = Tabs;
const { Option } = Select;

function Page(props) {

    const [isSearching, setisSearching] = React.useState(false);
    const [customerId, setCustomerId] = React.useState(null);
    const [defaultTabKey, setDefaultTabKey] = React.useState('by-booking-reference-number');
    const [searchBookingForm] = Form.useForm();

    const customerListQuery = CustomerService.list();
    const allBookingTagsQuery = BookingService.getAllBookingTags();
    const [searchBookingQuery, {isLoading: searchBookingQueryIsLoading, error: searchBookingQueryError}] = BookingService.searchBookings();

    React.useEffect( () => {
        // console.log(allBookingTagsQuery);
    },[]);

    const onFinish = (values) => {
        console.log(values);
        setisSearching(true);

        if (!values.booking_reference_number &&
            (
                !values.booking_creation_date &&
                !values.booking_source &&
                !values.booking_status &&
                !values.booking_type &&
                !values.customer &&
                !values.date_of_arrival &&
                !values.date_of_departure &&
                !values.mode_of_transportation &&
                !values.user_type &&
                !values.portal
            )
        ) {
            message.info('Indicate details to search.');
            setisSearching(false);
            return false;
        }        

        // Run query
        searchBookingQuery(values, {
            onSuccess: (res) => {
    
                console.log(res);

                setisSearching(false);
        
                if (!values.booking_reference_number) {
                    if (res.data.length > 0) {
                        props.bookingPaneEdit(moment().format('D-MMM-YYYY h:mm:ss A'), 'search', null, res.data);

                        notification.success({
                            message: 'Search Bookings',
                            description:
                                ``,
                        });
                    } else {
                        message.info("No bookings found");
                    }
                } else {
                    props.bookingPaneEdit(res.data.reference_number, 'view', res.data.status);

                    notification.success({
                        message: 'Search Booking',
                        description:
                            ``,
                    });
                }

                // Reset Forms
                searchBookingForm.resetFields();

                props.setsearchBookingModalVisible(false);
    
            },
            onError: (res) => {
                setisSearching(false);
                console.log(res);
            }
        });
        
    }

    const onSearch = (val) => {
        // console.log('search:', val);
    }

    const layout = {
        labelCol: { span: 10 },
        wrapperCol: { span: 12 },
    }

    const SearchBookingForm = () => (
        <Form
            // layout="horizontal"
            {...layout}
            onFinish={e => onFinish(e)}
            form={searchBookingForm}
            autoComplete="off"
            // initialValues={}
        >
            { !isSearching ?
            <Tabs animated={false} defaultActiveKey={defaultTabKey} onChange={ e => setDefaultTabKey(e)}>
                <TabPane tab="By booking reference number" key="by-booking-reference-number">
                    <Form.Item label="Booking reference number" name="booking_reference_number">
                        <Input placeholder="Booking reference number" />
                    </Form.Item>
                </TabPane>
                <TabPane tab="By booking details" key="by-booking-details">
                    <Form.Item label="Customer" name="customer">
                        <Select
                            showSearch
                            style={{ width: '100%' }}
                            placeholder="Select a customer"
                            optionFilterProp="children"
                            onSearch={onSearch}
                            onChange={(e) => setCustomerId(e)}
                            size="large"
                            filterOption={(input, option) =>
                                option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                            }
                            dropdownRender={menu => (<div>
                                {menu}
                                {/* <Button type="link" block onClick={showNewCustomerModal}>New Customer</Button> */}
                                </div>
                            )}
                            allowClear={true}
                        >
                            { customerListQuery.data &&
                                customerListQuery.data.map( (item, key) => (
                                    <Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Option>
                                ))
                            }
                        </Select>   
                    </Form.Item>
                    {/* <Form.Item label="User type" name="user_type">
                        <Select>
                            <Option value="customer">Customer</Option>
                        </Select>
                    </Form.Item> */}
                    <Form.Item label="Date of booking" name="booking_creation_date">
                        <DatePicker />
                    </Form.Item>
                    <Form.Item label="Date of arrival" name="date_of_arrival">
                        <DatePicker />
                    </Form.Item>
                    <Form.Item label="Date of departure" name="date_of_departure">
                        <DatePicker />
                    </Form.Item>
                    <Form.Item label="Booking status" name="booking_status">
                        <Select>
                            <Option value="pending">Pending</Option>
                            <Option value="confirmed">Confirmed</Option>
                            <Option value="cancelled">Cancelled</Option>
                        </Select>
                    </Form.Item>
                    <Form.Item label="Booking type" name="booking_type">
                        <Select>
                            <Option value="DT">Day Tour</Option>
                            <Option value="ON">Overnight</Option>
                        </Select>
                    </Form.Item>
                    <Form.Item label="Transportation" name="mode_of_transportation">
                        <Select>
                            <Option value="undecided">Undecided</Option>
                            <Option value="own_vehicle">Own vehicle</Option>
                            <Option value="camaya_transportation">Camaya transportation</Option>
                            <Option value="camaya_vehicle">Camaya vehicle</Option>
                            <Option value="van_rental">Van rental</Option>
                            <Option value="company_vehicle">Company vehicle</Option>
                        </Select>
                    </Form.Item>
                    <Form.Item label="Source" name="booking_source">
                        <Select mode="multiple">
                            <Option value="call">Call</Option>
                            <Option value="viber">Viber</Option>
                            <Option value="facebook_page">Facebook page</Option>
                            <Option value="other">Other</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item label="Portal" name="portal">
                        <Select mode="multiple">
                            <Option value="admin">Admin</Option>
                            <Option value="website">Website</Option>
                            <Option value="agent_portal">Agent Portal</Option>
                        </Select>
                    </Form.Item>

                    <Form.Item label="Tags" name="booking_tags">
                        <Select mode="multiple">
                            {
                                allBookingTagsQuery.data && allBookingTagsQuery.data.map( (item, key) => {
                                    return <Select.Option key={key} value={item}>{item}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>
                </TabPane>
            </Tabs>
            : <>Searching ...</>
            }
            <Button block size="large" loading={isSearching} className="mt-5" onClick={() => searchBookingForm.submit()}>{!isSearching ? 'Search' : ' '}</Button>
        </Form>
    )

    return (
        <div>
            <SearchBookingForm/>
        </div>
    )
}

export default Page;