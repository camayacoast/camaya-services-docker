import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'
import { Typography, Table, Form, DatePicker, Button, Row, Col, Space, Modal, Select } from 'antd'
import { DownloadOutlined, PrinterOutlined, ReloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import TranportationReportService from 'services/Transportation/Report'

function Page(props) {
    const [form] = Form.useForm();
    const reportQuery = TranportationReportService.ferryPassengersManifestoConcierge;
    const downloadReport = TranportationReportService.ferryPassengersManifestoDownloadConcierge;
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [printManifestModalVisible, setPrintManifestModalVisible] = useState(false);
    const [statusForPrinting, setStatusForPrinting] = useState([]);
    const [tripNumberToPrint, setTripNumberToPrint] = React.useState('');
    const reportList = reportQuery(startDate, endDate);

    const columns = [
      {
          title: 'Trip number',
          dataIndex: 'trip_number',
          key: 'trip_number',
          render: (trip_number) => <Button onClick={() => {
            setTripNumberToPrint(trip_number)
            setPrintManifestModalVisible(true);
          }}>{trip_number}</Button>
      },
      {
          title: 'Trip date',
          dataIndex: 'trip_date',
          key: 'trip_date'
      },
      {
          title: 'ETD',
          dataIndex: 'start_time',
          key: 'start_time'
      },
      {
          title: 'ETA',
          dataIndex: 'end_time',
          key: 'end_time'
      },
      {
          title: 'Active seat',
          dataIndex: 'active_seat',
          key: 'active_seat',
          render: (text, record) => <>{record.transportation.active_seats_count} (open: {record.transportation.active_seats_count - record.allocated_seat})</>
      },
      {
          title: 'Alloc. seat',
          dataIndex: 'allocated_seat',
          key: 'allocated_seat'
      },
      {
          title: 'Total used',
          dataIndex: 'total_used',
          key: 'total_used',
          render: (text, record) => <>
              {/* {_.sumBy(record.seat_segments, 'used')} */}
              {`${record.passengers_total} `}
              <Space className="ml-2"><small>(boarded:{record.boarded})</small> <small>(checked-in:{record.checked_in})</small></Space>
          </>
      },
      {
          title: 'Origin',
          dataIndex: 'origin',
          key: 'origin',
      },
      {
          title: 'Destination',
          dataIndex: 'destination',
          key: 'destination'
      },
      {
          title: 'Transportation',
          dataIndex: 'transportation_name',
          key: 'transportation_name',
          render: (text, record) => <>{record.transportation.name}</>
      },
      {
          title: 'Status',
          dataIndex: 'status',
          key: 'status',          
      },
  ];

    const onFilter = (values) => {
        if (! values.date) {
          setStartDate('');
          setEndDate('');

          return;
        }

        setStartDate(values.date[0].format('YYYY-MM-DD'));
        setEndDate(values.date[1].format('YYYY-MM-DD'));        
      }
      
    const onDownload = () => {
      const formData = {
        tripNumbers: tripNumberToPrint,
        status: statusForPrinting,
      };

      const date = `${startDate}-${endDate}`;

      downloadReport(formData, date);
    }

    const onReload = () => {
        if (! startDate || ! endDate) {
            return;
        }

        queryCache.invalidateQueries(['reports', 'ferry-passengers-manifesto', startDate, endDate]);
    }

    // useEffect(() => {
    //   if(! reportList?.data) {
    //     setTripNumberToPrint([]);
    //     return;
    //   } 

    //   setTripNumberToPrint(_.map(reportList?.data, 'trip_number'))
    // }, [reportList.data]);

    return (
        <>
            <Typography.Title level={4}>Ferry Passengers Manifest</Typography.Title>

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
                            {/* <Button type="primary"
                                className="ml-3"
                                disabled={tripNumberToPrint.length ? false : true}
                                icon={<DownloadOutlined />}
                                onClick={() => setPrintManifestModalVisible(true)}>
                                Download
                            </Button> */}
                            </Space>
                        </Form.Item>
                    </Form>
                </Col>
            </Row>

            <Table
                loading={reportList.status === 'loading'}
                columns={columns}
                dataSource={reportList?.data || []}
                rowKey="id"
                scroll={{ x: 'max-content' }}
                bordered
            />

            <Modal
                visible={printManifestModalVisible}
                title="Print manifest"
                onCancel={()=>setPrintManifestModalVisible(false)}
                footer={null}
            >
                <p>Select status to print</p>
                <Select value={statusForPrinting} onChange={e=>setStatusForPrinting(e)} placeholder="Select status" mode="multiple" className="mt-2" style={{width: '100%'}}>
                    <Select.Option value="arriving">Confirmed Booking</Select.Option>
                    <Select.Option value="pending">Pending</Select.Option>
                    <Select.Option value="checked_in">Checked-in</Select.Option>
                    <Select.Option value="boarded">Boarded</Select.Option>
                    <Select.Option value="no_show">No show</Select.Option>
                    <Select.Option value="cancelled">Cancelled</Select.Option>
                </Select>
                <div style={{textAlign: 'right'}}>
                    <Button type="primary" className="mt-4" style={{marginLeft:'auto'}} onClick={onDownload}>
                        <PrinterOutlined /> Print
                    </Button>
                </div>
            </Modal>
        </>
    )
}

export default Page;