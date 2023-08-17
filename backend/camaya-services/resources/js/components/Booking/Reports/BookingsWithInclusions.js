import React from 'react'
import moment from 'moment-timezone'

import ReportService from 'services/Booking/ReportService'
import PackageService from 'services/Booking/Package'
import ProductService from 'services/Booking/Product'

import { Typography, Table, Select, Button, message, Tag } from 'antd'
import { PrinterOutlined } from '@ant-design/icons'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

const numberWithCommas = (x) => {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function Page(props) {

    const [bookingsWithInclusionsQuery, { isLoading: bookingsWithInclusionsQueryIsLoading, reset: bookingsWithInclusionsQueryReset }] = ReportService.bookingsWithInclusions();
    const packageListQuery = PackageService.list();
    const productListQuery = ProductService.list();

    // States
    const [result, setResult] = React.useState([]);
    const [packagesToSearch, setPackagesToSearch] = React.useState([]);
    const [productsToSearch, setProductsToSearch] = React.useState([]);

    React.useEffect( () => {

    },[]);

    const columns = [        
        {
          title: 'Booking Reference #',
          dataIndex: 'reference_number',
          key: 'reference_number',
        },
        {
            title: 'Customer',
            render: (text, record) => 
            <>
                {record.customer.first_name} {record.customer.last_name} {record.customer.user_type ? <Tag>{record.customer.user_type}</Tag> : ''}
            </>
        },
        {
            title: 'Date of Arrival',
            render: (text, record) => moment(record.start_datetime).format('YYYY-MM-DD')
        },   
        {
            title: 'Date of Departure',
            render: (text, record) => moment(record.end_datetime).format('YYYY-MM-DD')
        }, 
        {
            title: 'Rate',
            render: (text, record) => numberWithCommas('₱ '+_.sum(record.invoices.map(item => {
                    return parseFloat(item.total_cost);
                }
            )))
        },
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
        }, 
    ];

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
    

    return (
        <>
            <Typography.Title level={4}>Booking with Inclusions Report</Typography.Title>

            <Select mode="multiple" style={{width: '100%'}} onChange={handlePackageToSearchChange} placeholder="Select all package that you want to extract">
                {
                    packageListQuery.data && packageListQuery.data.map( (pkg, key) => {
                        return <Select.Option key={key} value={pkg.code}>{pkg.name}</Select.Option>
                    })
                }
            </Select>
            <Select className="mt-2" mode="multiple" style={{width: '100%'}} onChange={handleProductToSearchChange} placeholder="Select all product that you want to extract">
                {
                    productListQuery.data && productListQuery.data.map( (prd, key) => {
                        return <Select.Option key={key} value={prd.code}>{prd.name}</Select.Option>
                    })
                }
            </Select>
            <Button onClick={() => handleSearch()} type="primary" className="mt-4 mb-4">Search</Button> 

            <ExcelFile filename={`Bookings_with_Inclusions_Report_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2"><PrinterOutlined/> Print result ({result.length} result)</Button>}>
                <ExcelSheet data={result} name="Bookings_with_inclusions">
                    <ExcelColumn label="Booking Ref #" value="reference_number"/>
                    <ExcelColumn label="Customer Name" value={col => `${col.customer.first_name} ${col.customer.last_name}` }/>
                    <ExcelColumn label="Date of Arrival" value={col => `${moment(col.start_datetime).format('YYYY-MM-DD')}` }/>
                    <ExcelColumn label="Date of Departure" value={col => `${moment(col.end_datetime).format('YYYY-MM-DD')}` }/>
                    <ExcelColumn label="Rate" value= { col => _.sum(col.invoices.map(item => parseFloat(item.total_cost)) )} />
                    <ExcelColumn label="Status" value="status"/>
                </ExcelSheet>
            </ExcelFile>             

            <Table 
                // loading={propertyListQuery.status === 'loading'}
                columns={columns}
                dataSource={result ? result : []}
                rowKey="id"
                rowClassName="table-row"
                size="small"
                // onChange={(pagination, filters, sorter) => handleTableChange(pagination, filters, sorter, 'all')}
            />
        </>
    )
}

export default Page;