import React, { useState, useEffect } from 'react'
import moment from 'moment-timezone'

import ReportService from 'services/Booking/ReportService'
import PackageService from 'services/Booking/Package'
import ProductService from 'services/Booking/Product'
import DashboardService from 'services/Booking/DashboardService'

import { Typography, Table, Select, Button, message, Tag, Col, DatePicker, Row, Form, Space, } from 'antd'
import { PrinterOutlined, ReloadOutlined, DownloadOutlined } from '@ant-design/icons'
import { queryCache } from 'react-query'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function Page(props) {

    const [form] = Form.useForm();

    const [bookingsWithInclusionsQuery, { isLoading: bookingsWithInclusionsQueryIsLoading, reset: bookingsWithInclusionsQueryReset }] = ReportService.bookingsWithInclusions();
    const packageListQuery = PackageService.list();
    const productListQuery = ProductService.list();

    const dashboardDataQuery = DashboardService.arrivalForecastReport(startDate, endDate);

    // States
    const [result, setResult] = React.useState([]);
    const [packagesToSearch, setPackagesToSearch] = React.useState([]);
    const [productsToSearch, setProductsToSearch] = React.useState([]);

    const [startDate, setStartDate] = React.useState(moment());
    const [endDate, setEndDate] = React.useState(moment());
    const [records, setRecords] = useState([]);

    React.useEffect( () => {

    },[]);

    const columns = [        
        // {
        //     title: 'Date Covered',
        //     render: (text, record) => 
        //         <>
        //             { moment(record.startDate).format('YYYY-MM-DD') - moment(record.endDate).format('YYYY-MM-DD')}
        //         </>,
        //     align: 'center',
        //     className: 'border-bottom',
        // },
        {
            title: 'Package/Product Availed',
            // render: (text, record) => 
            // <>
            //     {record.customer.first_name} {record.customer.last_name} {record.customer.user_type ? <Tag>{record.customer.user_type}</Tag> : ''}
            // </>
            align: 'center',
            className: 'border-bottom',
        },
        {
            title: 'Revenue Summary',
            // render: (text, record) => numberWithCommas('₱ '+_.sum(record.invoices.map(item => {
            //         return parseFloat(item.total_cost);
            //     }
            // ))),
            align: 'center',
            className: 'border-bottom',
        },   
        {
            title: 'Maya',
            align: 'center',
            className: 'border-bottom',
        }, 
        {
            title: 'GCash',
            align: 'center',
            className: 'border-bottom',
        },
        {
            title: 'PayPal',
            align: 'center',
            className: 'border-bottom',
        }, 
        {
            title: 'Bank Deposit/Transfer',
            align: 'center',
            className: 'border-bottom',
        }, 
        {
            title: 'Cash',
            align: 'center',
            className: 'border-bottom',
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

        queryCache.invalidateQueries(['reports', 'revenue-report', startDate, endDate]);
    }

    const handlePackageToSearchChange = (e) => {
        setPackagesToSearch(e);
    }

    const handleProductToSearchChange = (e) => {
        setProductsToSearch(e);
    }


    const handleSearch = () => {
        bookingsWithInclusionsQuery({
            packagesToSearch: packagesToSearch,
            productsToSearch: productsToSearch,
        }, {
            onSuccess: (res) => {
                console.log(res);
                if (res.data.length <= 0) {
                    message.info("No booking found!");
                } else {
                    setResult(res.data);
                }
            },
            onError: (e) => console.log(e),
        })
    }

    const handleDateRangeChange = (dates) => {
        console.log('change', dates);

        setStartDate(null);
        setEndDate(null);

        if (dates && dates.length == 2) {
            setStartDate(dates[0]);
            setEndDate(dates[1]);
        }
    }

    const handleJump = () => {
        if (startDate && endDate) {
            dashboardDataQuery.refetch();
        }
    }
    

    return (
        <>
            <Typography.Title level={4}>Revenue Report Per Mode of Payment Summary</Typography.Title>

            {/* Jump to date: <DatePicker.RangePicker onChange={handleDateRangeChange} value={[startDate, endDate]}/> */}
            {/* <Button className="ml-2" onClick={() => handleJump()}>Go</Button> */}
            {/* { (dashboardDataQuery.isLoading || dashboardDataQuery.isFetching) && <><LoadingOutlined className="ml-2 mr-2"/>Loading report</> } */}
        
            <Row justify="center" className="my-3">
                <Col>
                    <Form
                        form={form}
                        onFinish={onFilter}
                        layout="inline"
                    >
                        <Form.Item name="date" label="Select Date">
                            <DatePicker.RangePicker onChange={handleDateRangeChange} value={[startDate, endDate]}/>
                        </Form.Item>
                        <Form.Item>
                            <Space>
                                <Button type="primary" htmlType="submit">
                                    View Report
                                </Button>
                                <Button type="primary" icon={<ReloadOutlined />} onClick={onReload} />
                                <Button type="primary"
                                    disabled={records
                                        && records.length
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

            {/* <Select mode="multiple" style={{width: '100%'}} onChange={handlePackageToSearchChange} placeholder="Select all package that you want to extract" className="mt-2">
                {
                    packageListQuery.data && packageListQuery.data.map( (pkg, key) => {
                        return <Select.Option key={key} value={pkg.code}>{pkg.name}</Select.Option>
                    })
                }
            </Select> */}

            {/* <Select className="mt-2" mode="multiple" style={{width: '100%'}} onChange={handleProductToSearchChange} placeholder="Select all product that you want to extract">
                {
                    productListQuery.data && productListQuery.data.map( (prd, key) => {
                        return <Select.Option key={key} value={prd.code}>{prd.name}</Select.Option>
                    })
                }
            </Select> */}
            
            {/* <Button onClick={() => handleSearch()} type="primary" className="mt-4 mb-4">Search</Button>  */}

            {/* <ExcelFile filename={`Bookings_with_Inclusions_Report_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2"><PrinterOutlined/> Print </Button>}>
                <ExcelSheet data={result} name="Bookings_with_inclusions">
                    <ExcelColumn label="Booking Ref #" value="reference_number"/>
                    <ExcelColumn label="Customer Name" value={col => `${col.customer.first_name} ${col.customer.last_name}` }/>
                    <ExcelColumn label="Date of Arrival" value={col => `${moment(col.start_datetime).format('YYYY-MM-DD')}` }/>
                    <ExcelColumn label="Date of Departure" value={col => `${moment(col.end_datetime).format('YYYY-MM-DD')}` }/>
                    <ExcelColumn label="Rate" value= { col => _.sum(col.invoices.map(item => parseFloat(item.total_cost)) )} />
                    <ExcelColumn label="Status" value="status"/>
                </ExcelSheet>
            </ExcelFile>              */}

            <Table 
                columns={columns}
                dataSource={result ? result : []}
                rowKey="id"
                rowClassName="table-row"
                size="small"
                bordered
            />
        </>
    )
}

export default Page;