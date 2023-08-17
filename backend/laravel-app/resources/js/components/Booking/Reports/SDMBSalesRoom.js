import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space, Modal } from 'antd'
import { DownloadOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'
import ViewBookingComponent from 'components/Booking/View'

import BookingReportService from 'services/Booking/ReportService'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = BookingReportService.sdmbSalesRoom;
    const downloadReport = BookingReportService.sdmbSalesRoomDownload;
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const reportList = reportQuery(startDate, endDate);
    const [bookingToView, setbookingToView] = React.useState(null);
    const [viewBookingModalVisible, setviewBookingModalVisible] = React.useState(false);


    React.useEffect(()=> {
        if (bookingToView) {
            setviewBookingModalVisible(true);
        }
    },[bookingToView]);
    

    const numberWithCommas = (x) => {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    const columns = [
        {
            title: 'Sales Director',
            render: (record) => `${record.sales_director?.first_name} ${record.sales_director?.last_name}`,  
            className: 'border-bottom',
        },
        {
            title: 'Agent',
            render: (record) => `${record.agent?.first_name} ${record.agent?.last_name}`,  
            className: 'border-bottom',
            // align: 'center',
        },
        {
            title: 'Guest',
            dataIndex: 'customer',
            render: (customer) => `${customer.first_name} ${customer.last_name}`,  
            className: 'border-bottom',
            // align: 'center',
        },
        {
            title: 'Booking Code',
            dataIndex: 'reference_number',
            // key: 'reference_number',
            render: (text, record) => <Button type="link" onClick={()=>setbookingToView(record.reference_number)}>{text}</Button>,
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Check-in',
            render: (text, record) => moment(record.check_in).format('MMM D, YYYY'),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Check-out',
            render: (text, record) => moment(record.check_out).format('MMM D, YYYY'),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'No. of Nights',
            dataIndex: 'no_of_nights',
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Hotel',
            dataIndex: 'hotel',
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'No. of Room',
            dataIndex: 'no_of_rooms',
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Room Type',
            render: (record) => _.uniq(record.room_reservations_no_filter.map( i => i.room_type.code )).join(', '),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Rate',
            render: (record) =>  '₱ ' + numberWithCommas(parseFloat(record.inclusions_grand_total ?? 0) + parseFloat(record.inclusions_grand_total_on_package ?? 0)),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Extra Pax',
            // render: (record) => _.find(record.inclusions, i => i.code == 'EXTRAPAX')?.quantity ? _.sumBy(_.filter(record.inclusions, i => i.code == 'EXTRAPAX'), 'quantity') : 0,
            dataIndex: 'extra_pax',
            // render: (record) => '₱ ' + numberWithCommas(record.extra_pax),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Extra Pax Fee',
            // render: (record) => '₱ ' + numberWithCommas(parseFloat( _.find(record.inclusions, i => i.code == 'EXTRAPAX')?.price ?? 0) * (_.find(record.inclusions, i => i.code == 'EXTRAPAX')?.quantity ? _.sumBy(_.filter(record.inclusions, i => i.code == 'EXTRAPAX'), 'quantity') : 0)),
            render: (record) => '₱ ' + numberWithCommas(record.extra_pax_fee),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Total',
            render: (record) => '₱ ' + numberWithCommas(record.grand_total),
            // render: (record) => '₱ ' + numberWithCommas ( record.no_of_nights * 
            //                             ( parseFloat(record.inclusions_grand_total ?? 0) 
            //                             + parseFloat(record.inclusions_grand_total_on_package ?? 0) 
            //                             + ((_.find(record.inclusions, i => i.code == 'EXTRAPAX')?.quantity ? _.find(record.inclusions, i => i.code == 'EXTRAPAX')?.quantity * _.find(record.inclusions, i => i.code == 'EXTRAPAX')?.price : 0))),
            
                                        // + parseFloat( _.find(record.inclusions, i => i.code == 'EXTRAPAX')?.price ?? 0) * (_.find(record.inclusions, i => i.code == 'EXTRAPAX')?.quantity ? _.sumBy(_.filter(record.inclusions, i => i.code == 'EXTRAPAX'), 'quantity') : 0))),
            className: 'border-bottom',
            align: 'center',
        },
        {
            title: 'Remarks',
            dataIndex: 'remarks',
            className: 'border-bottom',
            align: 'left',
        },
    ];

    const onFilter = (values) => {
        if (!values.date) {
            setStartDate('');
            setEndDate('');

            return;
        }

        setStartDate(values.date[0].format('YYYY-MM-DD'));
        setEndDate(values.date[1].format('YYYY-MM-DD'));
    }

    const onDownload = () => {
        downloadReport(startDate, endDate);
    }

    const onReload = () => {
        if (! startDate || ! endDate) {
            return;
        }

        queryCache.invalidateQueries(['reports', 'sdmb-sales-room', startDate, endDate]);
    }

    return (
        <>
            <Typography.Title level={4}>Sales Accommodation Report</Typography.Title>

            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
                        onFinish={onFilter}
                        layout="inline"
                    >
                        <Form.Item name="date" label="Select Date">
                            <DatePicker.RangePicker />
                        </Form.Item>
                        <Form.Item>
                            <Space>
                                <Button type="primary" htmlType="submit">
                                    View Report
                                </Button>
                                <Button type="primary" icon={<ReloadOutlined />} onClick={onReload} />
                                <Button type="primary"
                                    disabled={reportList?.data
                                        && reportList?.data.length
                                        ? false
                                        : true
                                    }
                                    icon={<DownloadOutlined />}
                                    onClick={onDownload}>
                                    Download
                                </Button>
                            </Space>
                        </Form.Item>
                    </Form>
                </Col>
            </Row>

            <Table
                bordered
                columns={columns}
                dataSource={reportList?.data || []}
                loading={reportList.status === 'loading'}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                // summary={summary}
            />

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
        </>
    )
}

export default Page;