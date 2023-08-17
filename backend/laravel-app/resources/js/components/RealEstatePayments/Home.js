import React, { useEffect } from 'react'
import moment from 'moment-timezone'

import SalesAdminPortalService from 'services/SalesAdminPortal'
import PaymentService from 'services/RealEstatePayments/PaymentService'

import { Table, Button, Modal, message, Descriptions, Space, Dropdown, Menu, Typography, Form, Input, Tag, Card, Badge, Checkbox, Upload, Divider, Popconfirm, Col, Row, Statistic, Alert, InputNumber, DatePicker, Select, Popover } from 'antd'
import { EyeOutlined, PrinterOutlined, EllipsisOutlined, ExclamationCircleOutlined, UploadOutlined, InfoCircleOutlined } from '@ant-design/icons'

const { TextArea } = Input;

import { numberWithCommas } from 'utils/Common';

import ReactExport from "react-export-excel";
import ActivityLogs from 'components/RealEstatePayments/ActivityLogs';
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

const paymentTypes = {
    'reservation_fee_payment': 'Reservation',
    'downpayment': 'Downpayment',
    'monthly_amortization_payment': 'Monthly Amortization',
    'full_cash': 'Full Cash',
    'partial_cash': 'Partial Cash',
    'split_cash': 'Split Cash',
    'retention_fee': 'Retention Fee',
    'title_fee': 'Title Fee',
    'penalty': 'Penalty',
    'redocs_fee': 'Redocumentation Fee',
    'docs_fee': 'Documentation Fee',
    'hoa_fees': 'HOA',
    'camaya_air_payment': 'Camaya Air Payment',
    'others': 'Others',
};

let paymentTypeFilters = [];

_.map(paymentTypes, function(i, e){
    paymentTypeFilters.push({text: i, value: e});
})

const includedPaymentTypes = [
    'reservation_fee_payment', 'downpayment',
    'title_fee', 'retention_fee', 'redocs_fee', 'docs_fee',
    'split_cash', 'full_cash', 'partial_cash', 'monthly_amortization_payment', 'penalty'
];

const notIncludedPaymentTypes = [
    'reservation_fee_payment', 'downpayment',
    'title_fee', 'retention_fee', 'redocs_fee', 'docs_fee',
    'split_cash', 'full_cash', 'partial_cash', 'monthly_amortization_payment', 'penalty'
];

export default function Page(props) {

    const PaymentListQuery = PaymentService.list();

    const [paymentDetailsForPayMayaQuery, { isLoading: paymentDetailsForPayMayaQueryIsLoading, reset: paymentDetailsForPayMayaQueryReset}] = PaymentService.paymentDetailsForPayMaya();
    const [setupPayMayaWebhookQuery, { isLoading: setupPayMayaWebhookQueryIsLoading, reset: setupPayMayaWebhookQueryReset}] = PaymentService.setupPayMayaWebhook();

    const [addPaymentQuery, { IsLoading: addPaymentQueryIsLoading, reset: addPaymentQueryReset}] = SalesAdminPortalService.addPayment();
    const [importReportQuery, { isLoading: importReportQueryIsLoading, reset: importReportQueryReset}] = SalesAdminPortalService.generateImportReport();
    const [downloadImporTemplateQuery, { isLoading: downloadImporTemplateQueryIsLoading, reset: downloadImporTemplateQueryReset}] = SalesAdminPortalService.downloadImportTemplate();
    const [bulkUploadQuery, { isLoading: bulkUploadQueryIsLoading, reset: bulkUploadQueryReset}] = SalesAdminPortalService.bulkUploadPayments();
    const [updatePaymentDetailQuery, { isLoading: updatePaymentDetailQueryIsLoading, reset: updatePaymentDetailQueryReset}] = SalesAdminPortalService.updatePaymentDetail();
    const [exportUnidentifedReportQuery, { isLoading: exportUnidentifedReportQueryIsLoading, reset: exportUnidentifedReportQueryReset}] = PaymentService.exportUnidentifiedReport();
    const [updatePaymentQuery, {isLoading: updatePaymentQueryIsLoading, reset: updatePaymentQueryReset}] = SalesAdminPortalService.REupdatePaymentRecord();


    const [paymentVerificationQuery, { isLoading: paymentVerificationIsLoading, reset: paymentVerificationReset}] = PaymentService.paymentVerification();

    const [filterPaymentListQuery, { isLoading: filterPaymentListQueryLoading}] = PaymentService.filterPaymentLists();

    const [paymentDetails, setPaymentDetails] = React.useState([]);
    const [paymentDetailsModal, setPaymentDetailsModal] = React.useState(false);
    const [transactionId, setTransactionId] = React.useState(0);
    const [isVerified, setIsVerified] = React.useState(0);
    const [advancePayment, setAdvancePayment] = React.useState(0);
    const [searchString, setSearchString] = React.useState('');
    const [fileList, setFileList] = React.useState([]);
    const [activityLogRefetch, setActivityLogRefetch] = React.useState(false);
    const [totalAmount, setTotalAmount] = React.useState(0);
    const [bulkUploadModal, setBulkUploadModal] = React.useState(false);
    const [bulkUploadData, setBulkUploadData] = React.useState([]);
    const [updatePaymentModal, setUpdatePaymentModal] = React.useState(false);
    const [updatePaymentData, setUpdatePaymentData] = React.useState([]);
    const [permisionMessageShow, setPermisionMessageShow] = React.useState(false);


    const [verifyPaymentForm] = Form.useForm();
    const [formUpdatePayment] = Form.useForm();
    const [filterForm] = Form.useForm();
    const [filteredData, setFilteredData] = React.useState([]);

    const [startDate, setStartDate] = React.useState(null);
    const [endDate, setEndDate] = React.useState(null);

    const openUpdatePaymentModal = (record) => {
        setUpdatePaymentData(record);
        setUpdatePaymentModal(true);
    }

    const updatePaymentFinish = () => {

        if( updatePaymentQueryIsLoading ) {
            return false;
        }

        if( updatePaymentData.payment_type == 'penalty' || updatePaymentData.payment_type == 'monthly_amortization_payment' ) {
            message.info("Recalculating payments please wait.");
        }
        setUpdatePaymentModal(false);

        updatePaymentQuery({
            reservation_number: updatePaymentData.reservation_number,
            client_number: updatePaymentData.client_number,
            transaction_id: updatePaymentData.transaction_id,
            payment_id: updatePaymentData.id,
            payment_amount: updatePaymentData.payment_amount,
            payment_type: updatePaymentData.payment_type,
            payment_gateway: updatePaymentData.payment_gateway,
            paid_at: updatePaymentData.paid_at ? moment(updatePaymentData.paid_at).format('YYYY-MM-DD HH:mm:ss') : null,
            is_verified: updatePaymentData.is_verified,
            action_type: 'update',
        }, {
            onSuccess: (res) => {
                if( res.data == 1 ) {
                    message.success("Payment Updated!");
                    setPaymentDetailsModal(false);
                    setActivityLogRefetch(true);
                } else {
                    message.info("Transaction not exist due to recalulation of schedule. Updating list please try again.");
                    setPaymentDetailsModal(false);
                    setUpdatePaymentModal(false);
                }
                PaymentListQuery.refetch();
            },
            onError: (e) => {
                updatePaymentQueryReset();
                message.warning(`Updating payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    const deletePaymentDetail = (record) => {

        if( updatePaymentQueryIsLoading ) {
            return false;
        }

        updatePaymentQuery({
            reservation_number: record.reservation_number,
            client_number: record.client_number,
            amortization_schedule_id: record.amortization_schedule_id,
            transaction_id: record.transaction_id,
            payment_id: record.id,
            payment_amount: record.payment_amount,
            payment_type: record.payment_type,
            payment_gateway: record.payment_gateway,
            paid_at: record.paid_at ? moment(record.paid_at).format('YYYY-MM-DD HH:mm:ss') : null,
            is_verified: record.is_verified,
            action_type: 'delete',
        }, {
            onSuccess: (res) => {
                message.success("Payment successfully deleted!");
                setPaymentDetailsModal(false);
                setUpdatePaymentModal(false);
                setActivityLogRefetch(true);
                PaymentListQuery.refetch();
            },
            onError: (e) => {
                updatePaymentQueryReset();
                message.warning(`Updating payment failed: ${e.errors ? _.map(e.errors, (i) => i) : e.message}`)
            }
        })
    }

    const handlePreviewPaymentClick = (data) => {

        if( permisionMessageShow == false && data.allowed_to_update_payment == false ) {
            console.log('%c Unable to edit payment details, Please add permission to your account:', 'background: #222; color: #bada55');
            console.log('%c SalesAdminPortal.UpdatePayment.AmortizationLedger', 'background: #222; color: #bada55');
            setPermisionMessageShow(true);
        }

        setIsVerified(data.is_verified);
        setTransactionId(data.transaction_id);
        setPaymentDetails([data]);
        setPaymentDetailsModal(true);
    }

    const verifiedPaymentForm = (values) => {

        values.transaction_id = transactionId;
        values.advance_payment = advancePayment;
        values.reservation_number = ( typeof paymentDetails[0] !== 'undefined' ) ?  paymentDetails[0].reservation_number : values.reservation_number;
        values.client_number = ( typeof paymentDetails[0] !== 'undefined' ) ? paymentDetails[0].client_number : values.client_number;

        Modal.confirm({
            title: 'Payment Verification',
            icon: <ExclamationCircleOutlined />,
            content: 'Are you sure you want to verify this payment?',
            okText: 'Continue',
            cancelText: 'Cancel',
            onOk: () => {

                if(paymentVerificationIsLoading) {
                    return false;
                }

                paymentVerificationQuery(values, {
                    onSuccess: (res) => {
                        setTimeout(function() {
                            let data = res.data
                            setIsVerified(data[0].is_verified);
                            setPaymentDetails(data);
                            PaymentListQuery.refetch();
                            message.success("Payment is already verified.");
                            // Preventing payment to associate with RA if client # is not set
                            // if( values.payment_type === 'monthly_amortization_payment' && data[0].client_number !== null && data[0].client_number !== '' ) {
                                // amortizationPaymentRequest(data);
                            // }
                            setActivityLogRefetch(true);
                        }, 500);
                    },
                    onError: (e) => {;
                        message.warning(e.message);
                        paymentVerificationReset();
                    }
                });
            }
        });
    }

    const amortizationPaymentRequest = (payments) => {

        if (addPaymentQueryIsLoading) {
            return false;
        }

        let data = payments[0];
        let date_of_paid = (data.paid_at !== null) ? data.paid_at : moment().format('YYYY-MM-DD HH:mm:ss');

        addPaymentQuery({
            reservation_number: data.reservation.reservation_number,
            payment_amount: data.payment_amount,
            payment_type: data.payment_type,
            pr_number: null,
            or_number: null,
            payment_gateway: data.payment_gateway,
            paid_at: date_of_paid,
            remarks: null,
            check_number: null,
            check_account_number: null,
            bank: null,
            bank_account_number: null,
            payment_gateway_reference_number: data.payment_gateway_reference_number,
            re_dashboard_request: true,
            payment_id: data.id,
            transaction_id: data.transaction_id,
            advance_payment: data.advance_payment
        }, {
            onSuccess: (res) => {
                setActivityLogRefetch(true);
            },
            onError: (e) => {
                addPaymentQueryReset();
            }
        })
    }

    const handlePaymentGatewayRefNoClick = (ref_no) => {
        // console.log(ref_no);

        let modal = Modal.info({
            icon: null,
            width: 500,
            content: <>Loading... Please wait.</>,
        });

        if (!paymentDetailsForPayMayaQueryIsLoading) {
            paymentDetailsForPayMayaQuery({
                'payment_gateway_reference_number': ref_no
            }, {
                onSuccess: (res) => {
                    // console.log(res);
                    if (res.data.code && res.data.code == 'PY0009') {

                        modal.update({
                            icon: null,
                            width: 500,
                            content: <>{res.data.message}</>,
                        });

                    } else {
                        modal.update({
                            icon: null,
                            width: 500,
                            title: 'PayMaya Payment Status',
                            content: <Descriptions title="Details from PayMaya" bordered layout="horizontal">
                                <Descriptions.Item span={3} label="Payment gateway">PayMaya</Descriptions.Item>
                                <Descriptions.Item span={3} label="Paid at">{moment(res.data.paymentDetails.paymentAt).format('D MMM YYYY')}</Descriptions.Item>
                                <Descriptions.Item span={3} label="Payment status">{res.data.paymentStatus}</Descriptions.Item>
                                <Descriptions.Item span={3} label="Status">{res.data.status}</Descriptions.Item>
                                <Descriptions.Item span={3} label="Payment Scheme">
                                    {res.data.paymentScheme}<br/>
                                    Card no.: {res.data.paymentDetails.responses.efs.payer.fundingInstrument.card && res.data.paymentDetails.responses.efs.payer.fundingInstrument.card.cardNumber}<br/>
                                    Expiry: {res.data.paymentDetails.responses.efs.payer.fundingInstrument.card && res.data.paymentDetails.responses.efs.payer.fundingInstrument.card.expiryMonth}/{res.data.paymentDetails.responses.efs.payer.fundingInstrument.card && res.data.paymentDetails.responses.efs.payer.fundingInstrument.card.expiryYear}
                                </Descriptions.Item>
                                <Descriptions.Item span={3} label="Total amount">{res.data.totalAmount.currency} {res.data.totalAmount.amount}</Descriptions.Item>
                            </Descriptions>
                        });
                    }
                },
                onError: (e) => {
                    message.danger(e.message);
                }
            })
        }
    }

    const handleSetupPayMayaWebhookClick = () => {
        let ans = prompt("Enter PIN:");

        if (ans == '12354') {
            // alert(ans);
            if (!setupPayMayaWebhookQueryIsLoading) {
                setupPayMayaWebhookQuery({}, {
                    onSuccess: (res) => {
                        console.log(res);
                        Modal.info({
                            icon: null,
                            width: 900,
                            content: <Descriptions title="Details" bordered layout="horizontal">
                                {
                                    res.data && res.data.map( (item, key) => {
                                        return <Descriptions.Item span={3} key={key} label="Webhook">
                                                <Space>
                                                    <span>ID: {item.id}</span>
                                                    <span>Name: {item.name}</span>
                                                    <span>Callback URL: {item.callbackUrl}</span>
                                                </Space>
                                        </Descriptions.Item>
                                    })
                                }
                            </Descriptions>
                        })
                        message.success("OK");
                    },
                    onError: (e) => {
                        message.danger(e.message);
                    }
                })
            }
        }
    }

    const handleSearch = (e) => {
        setSearchString(e.target.value.toLowerCase());
    }

    const handleImportReport = (data) => {

        if (importReportQueryIsLoading) {
            return false;
        }

        importReportQuery({
            data: data,
        }, {
            onSuccess: (res) => {
                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                //Build a URL from the file
                const fileURL = URL.createObjectURL(file);
                //Download fileURL
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `Upload_Reports_${moment().format('YYYY-MM-DD HH:mm:ss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);
            },
            onError: (e) => {
                message.info("Error");
            }
        })
    }

    const handleUnidentifiedReport = () => {
        if (exportUnidentifedReportQueryIsLoading) {
            return false;
        }

        exportUnidentifedReportQuery({
            type: 'unidentified_collection_report',
        }, {
            onSuccess: (res) => {

                var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                //Build a URL from the file
                const fileURL = URL.createObjectURL(file);
                //Download fileURL
                var a = document.createElement("a");
                a.href = fileURL;
                a.download = `Unidentified_Collections_Report_${moment().format('YYYY-MM-DD HH:mm:ss')}`;
                a.click();
                window.URL.revokeObjectURL(fileURL);

            },
            onError: (e) => {
                message.info("Error");
                exportUnidentifedReportQueryReset();
            }
        })
    }

    const handleDownloadTemplate = () => {
        if (downloadImporTemplateQueryIsLoading) {
            return false;
        }

        downloadImporTemplateQuery(
            {
                template: true,
            }, {
                onSuccess: (res) => {
                    var file = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' });
                    //Build a URL from the file
                    const fileURL = URL.createObjectURL(file);
                    //Download fileURL
                    var a = document.createElement("a");
                    a.href = fileURL;
                    a.download = `Reservation_import_template`;
                    a.click();
                    window.URL.revokeObjectURL(fileURL);
                },
                onError: (e) => {
                    message.warning("Failed.");
                }
            }
        )
    }

    const uploadProps = {
        onRemove: file => {
          setFileList( prev => {
            const index = prev.indexOf(file);
            const newFileList = prev.slice();
            newFileList.splice(index, 1);

            return newFileList;
          });
        },
        method: 'post',
        name: 'bulkPayment',
        action: `${process.env.APP_URL}/api/sales-admin-portal/import-reservation-data`,
        headers:{
            Authorization: `Bearer ${localStorage.getItem('token')}`,
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        beforeUpload() {
            message.info('Checking please wait....');
        },
        onSuccess: (res) => {
            // handleImportReport(res);
            // message.success(`Uploading payment success`);
            // setActivityLogRefetch(true);
            // PaymentListQuery.refetch();

            message.success(`Please check data before uploading.`);
            setBulkUploadData(res);
            setTimeout(function() {
                setBulkUploadModal(true);
            }, 1000)
        },
        onChange(info, i) {
            if (info.file.status === 'done') {
                message.success(`${info.file.name} file imported successfully`);
                PaymentListQuery.refetch();

            } else if (info.file.status === 'error') {
                message.error(`Failed: Excel content or account have no permission.`);
            }
            return false;
        },
        fileList,
    };

    const uploadBulkPayments = () => {

        if (bulkUploadQueryIsLoading) {
            return false;
        }

        message.info(`Uploading payments, please wait...`);

        bulkUploadQuery({
            rows: typeof bulkUploadData.ok_lists !== 'undefined' ? bulkUploadData.ok_lists : [],
        }, {
            onSuccess: (res) => {
                message.success(`Uploading payment success`);
                setBulkUploadModal(false);
                setBulkUploadData([]);
                setActivityLogRefetch(true);
                PaymentListQuery.refetch();

            },
            onError: (e) => {
                message.warning("Bulk upload failed.");
            }
        });
    }

    const updatePaymentDetail = (text, id, field, is_verified) => {
        // console.log(text);

        if( text !== '' ) {

            if (updatePaymentDetailQueryIsLoading) {
                return false;
            }

            updatePaymentDetailQuery({
                id: id,
                value: text,
                field: field,
                is_verified: is_verified,
            }, {
                onSuccess: (res) => {
                    message.success('Payment detail updated.')
                    setPaymentDetails(res.data);
                    setActivityLogRefetch(true);
                    PaymentListQuery.refetch();
                },
                onError: (e) => message.info(e.message),
            })
        }


    }

    const handleFilter = values => {

        const start_date = values.date[0].format('YYYY-MM-DD');
        const end_date = values.date[1].format('YYYY-MM-DD');

        if (!values.date) {
            message.warning('Please fill out start date and end date');
            return;
        }

        if(filterPaymentListQueryLoading) return false;

        filterPaymentListQuery({
            startDate: start_date,
            endDate: end_date
        }, {
            onSuccess: (res) => {
                setFilteredData(res.data);
            },
            onError: (e) => message.info(e.message),
        })

        setStartDate(start_date)
        setEndDate(end_date)

    }

    const report_column = [
        {
            title: '#',
            dataIndex: 'number',
            key: 'number',
            render: (text) => text
        },
        {
            title: 'DATE',
            dataIndex: 'date',
            key: 'date',
            render: (text) => text
        },
        {
            title: 'AMOUNT',
            dataIndex: 'amount',
            key: 'amount',
            render: (text) => text
        },
        {
            title: 'CLIENT NUMBER',
            dataIndex: 'client_number',
            key: 'client_number',
            render: (text) => text
        },
        {
            title: 'FIRSTNAME',
            dataIndex: 'first_name',
            key: 'first_name',
            render: (text) => text
        },
        {
            title: 'LASTNAME',
            dataIndex: 'last_name',
            key: 'last_name',
            render: (text) => text
        },
        {
            title: 'PAYMENT DESTINATION',
            dataIndex: 'payment_destination',
            key: 'payment_destination',
            render: (text) => text
        },
        {
            title: 'PAYMENT GATEWAY',
            dataIndex: 'payment_gateway',
            key: 'payment_gateway',
            render: (text) => text
        },
    ];

    return (
        <div>
            <Space>
                <div className='mt-4'>
                    Print Payment List:
                    <ExcelFile filename={`Real_Estate_Payments_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="ml-2" size="small"><PrinterOutlined/></Button>}>
                        <ExcelSheet data={(filteredData && filteredData.length ? filteredData : (!startDate ? PaymentListQuery.data : filteredData))} name="Real_Estate_Payment_List">
                            <ExcelColumn label="Transaction ID" value="transaction_id"/>
                            <ExcelColumn label="Type" value="payment_type"/>
                            <ExcelColumn label="First name" value="first_name"/>
                            <ExcelColumn label="Middle name" value="middle_name"/>
                            <ExcelColumn label="Last name" value="last_name"/>
                            <ExcelColumn label="Payment gateway" value="payment_gateway"/>
                            <ExcelColumn label="Amount" value="payment_amount"/>
                            <ExcelColumn label="Paid at" value={ col => col.paid_at ? moment(col.paid_at).format('M/D/YYYY') : 'NOT YET PAID' }/>
                            <ExcelColumn label="Status" value={ col => col.payment_statuses && col.payment_statuses.length > 0 ? col.payment_statuses[0].status : ''}/>
                            <ExcelColumn label="Payment gateway reference #" value="payment_gateway_reference_number"/>
                        </ExcelSheet>
                    </ExcelFile>
                </div>
            </Space>
            <div className="mt-4 mb-4" style={{display:'flex', justifyContent:'space-between'}}>

                <Input style={{width: 300}} type="search" placeholder="Search Reservation/Client #" onChange={(e)=>handleSearch(e)} />

                <Form layout="inline" form={filterForm} onFinish={handleFilter}>
                    <Form.Item name="date" label="Select Date">
                        <DatePicker.RangePicker />
                    </Form.Item>
                    <Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" loading={filterPaymentListQueryLoading}>
                                Apply Filter
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>

                <div style={{display:'flex', justifyContent:'space-between'}}>
                    <Upload {...uploadProps}
                            itemRender={(originNode, file, currFileList) => {
                                return <Card style={{marginTop: 8}}>{originNode} Attachment type: <strong>{file.attachment_type}</strong></Card>
                            }}
                        >
                            <Button size="small">Import Payments</Button>
                    </Upload>
                    <Dropdown placement="bottomRight" overlay={
                            <Menu>
                                <Menu.Item onClick={() => handleDownloadTemplate()}>Download Import Template</Menu.Item>
                                <Menu.Item onClick={() => handleUnidentifiedReport()}>Unidentified Collection Report</Menu.Item>
                            </Menu>
                        }>
                        <Button size="small" style={{marginLeft: '10px'}}>Options</Button>
                    </Dropdown>
                    <ActivityLogs size="small" refetch={activityLogRefetch} refetchSetter={setActivityLogRefetch}/>
                </div>
            </div>
            <Table
                scroll={{ x: 1500 }}
                size="small"
                className="mt-4"
                loading={!PaymentListQuery.data ? true : false}
                rowKey="id"
                rowSelection={{
                    type: 'checkbox',
                    columnTitle: <></>,
                    onSelect: (record, selected, selectedRows) => {
                        let value = parseFloat(record.payment_amount);
                        if( selected ) {
                            setTotalAmount(totalAmount + value);
                        } else {
                            setTotalAmount(totalAmount - value);
                        }
                    }
                }}
                // dataSource={PaymentListQuery.data && PaymentListQuery.data}
                dataSource={
                
                    _.filter((filteredData && filteredData.length ? filteredData : (!startDate ? PaymentListQuery.data : filteredData)), item => {
                        if (item && searchString) {
                            let reservation_nunber = (item.reservation_number != null) ? item.reservation_number.toLowerCase() : '';
                            const searchValue =  reservation_nunber + ' ' + item.client_number + ' ' + item.first_name.toLowerCase() + ' ' + item.last_name.toLowerCase();
                            return searchString ? searchValue.indexOf(searchString.toLowerCase()) !== -1 : true;
                        }
                        return true;
                    })
                }
                columns={[
                    // {
                    //     title: 'id',
                    //     dataIndex: 'id',
                    //     key: 'id'
                    // },
                    // {
                    //     title: '',
                    //     dataIndex: 'transaction_id',
                    //     render: (text, record) => {
                    //         return <>
                    //             <Checkbox
                    //                 value={record.payment_amount}
                    //                 // style={{marginLeft: 16}}
                    //                 onChange={(e) => {
                    //                     let checked = (e.target.checked) ? 1 : 0;
                    //                     let value  = parseFloat(e.target.value);
                    //                     if( checked ) {
                    //                         setTotalAmount(totalAmount + value);
                    //                     } else {
                    //                         setTotalAmount(totalAmount - value);
                    //                     }
                    //                 }}
                    //             ></Checkbox>
                    //         </>;
                    //     }
                    // },
                    {
                        title: 'Transaction ID',
                        dataIndex: 'transaction_id',
                        key: 'transaction_id',
                        render: (text, record) => {

                            let color = 'green';
                            includedPaymentTypes.includes(record.payment_type) ?
                                color = (record.is_verified === 0) ? 'orange' : 'green'
                            : color = 'green';

                            return <Tag color={color}>{record.transaction_id}</Tag>;
                        }
                    },
                    {
                        title: 'Client #',
                        dataIndex: 'client_number',
                        key: 'client_number'
                    },
                    {
                        title: 'Reservation #',
                        dataIndex: 'reservation_number',
                        key: 'reservation_number',
                        filters: [
                            { text: 'With Reservation #', value: 'with_value' },
                            { text: 'Unidentified', value: 'with_out_value' },
                        ],
                        defaultFilteredValue: ['with_value', 'with_out_value'],
                        onFilter: (value, record, c) => {

                            if( value == 'with_value' ) {
                                return record.reservation_number != '' && record.reservation_number != null;
                            } else {
                                return record.reservation_number == '' || record.reservation_number == null;
                            }

                        },
                    },
                    {
                        title: 'Type',
                        dataIndex: 'payment_type',
                        key: 'payment_type',
                        filters: paymentTypeFilters,
                        defaultFilteredValue: [],
                        onFilter: (value, record) => {
                            return (record.payment_type !== null && record.payment_type !== '') ? record.payment_type.includes(value) : false;
                        },
                        render: (text, record) => {

                            let typeContainer = [];

                            if( text !== null ) {
                                let types = text.split(',');

                                types = _.map(types, function(value){
                                    let type = value.trim();
                                    if( typeof paymentTypes[type] !== 'undefined' ) {
                                        typeContainer.push(paymentTypes[type]);
                                    } else {
                                        typeContainer.push(type);
                                    }

                                });

                                return <strong style={{fontSize: '12px'}}>{typeContainer.join(', ')}</strong>;

                            } else {

                                return '';

                            }

                        }
                    },
                    {
                        title: 'First name',
                        dataIndex: 'first_name',
                        key: 'first_name'
                    },
                    {
                        title: 'Middle name',
                        dataIndex: 'middle_name',
                        key: 'middle_name'
                    },
                    {
                        title: 'Last name',
                        dataIndex: 'last_name',
                        key: 'last_name'
                    },
                    {
                        title: 'Payment gateway',
                        dataIndex: 'payment_gateway',
                        key: 'payment_gateway',
                        filters: [
                            { text: 'Cash', value: 'Cash' },
                            { text: 'PayMaya', value: 'PayMaya' },
                            { text: 'DragonPay', value: 'DragonPay' },
                            { text: 'PesoPay', value: 'PesoPay' },
                            { text: 'PDC', value: 'PDC' },
                            { text: 'Direct Payment', value: 'Direct Payment' },
                            { text: 'Card', value: 'Card' },

                        ],
                        defaultFilteredValue: ['Cash', 'PayMaya', 'DragonPay', 'PesoPay', 'PDC', 'Direct Payment', 'Card'],
                        onFilter: (value, record) => {
                            let payment_gateway = (record.payment_gateway === 'cash') ? 'Cash' : record.payment_gateway;
                            return payment_gateway.includes(value)
                        },
                    },
                    {
                        title: 'Amount',
                        dataIndex: 'payment_amount',
                        key: 'payment_amount',
                        render: (text, record) => <strong className="text-success">{Number(record.payment_amount).toLocaleString('en-US', {
                            style: 'currency',
                            currency: 'PHP',
                          })}</strong>
                    },
                    {
                        title: 'Paid at',
                        filters: [
                            { text: 'NOT YET PAID', value: 'NOT_YET_PAID' },
                            { text: 'WITH DATE VALUE', value: 'WITH_DATE_VALUE' },
                        ],
                        onFilter: (value, record) => {

                            if(value === 'WITH_DATE_VALUE') return record.paid_at;

                            if(value === 'NOT_YET_PAID') return !record.paid_at;

                        },
                        render: (text, record) => <>
                            {record.paid_at ? moment(record.paid_at).format('D MMM YYYY') : <small className="text-danger">NOT YET PAID</small>}
                        </>
                    },

                    {
                        title: 'Status',
                        filters: [
                            { text: 'PENDING', value: 'PENDING' },
                            { text: 'SUCCESS', value: 'SUCCESS' },
                            { text: 'FAILURE', value: 'FAILURE' },
                            { text: 'SUCCESS_ADMIN', value: 'SUCCESS_ADMIN' },
                            { text: 'FAILED', value: 'FAILED' },
                            { text: 'NO STATUS', value: 'NO_STATUS' }
                        ],
                        onFilter: (value, record) => {

                            if(value === 'NO_STATUS') {
                                return !record.payment_statuses.length;
                            }

                            if(record.payment_statuses && record.payment_statuses.length) {
                                return record.payment_statuses[0].status.includes(value)
                            }
                    
                        },
                        render: (text, record) => <>
                            {record.payment_statuses && record.payment_statuses.length > 0  ? record.payment_statuses[0].status : ''}
                        </>
                    },

                    {
                        title: 'Remarks',
                        render: (text, record) => <>
                            <div style={{fontSize: '12px', width: '152px'}}>{record.remarks}</div>
                        </>
                    },

                    {
                        title: 'Payment Gateway Ref. #',
                        dataIndex: 'payment_gateway_reference_number',
                        key: 'payment_gateway_reference_number',
                        render: (text, record) => <div style={{width: '200px', overflow: 'hidden'}} title={record.payment_gateway_reference_number}>
                            {
                                record.payment_gateway == 'PayMaya' && record.paid_at && record.payment_gateway_reference_number !== null ?
                                    <Button type="link" onClick={() => handlePaymentGatewayRefNoClick(record.payment_gateway_reference_number)}><EyeOutlined/> {record.payment_gateway_reference_number}</Button>
                                    : record.payment_gateway_reference_number
                            }
                        </div>
                    },

                    {
                        title: 'Action',
                        render: (text, record) => {
                            return <Dropdown overlay={
                                    <Menu>
                                        <Menu.Item onClick={() => handlePreviewPaymentClick(record)}>Payment Details</Menu.Item>
                                    </Menu>
                                }>
                                <Button icon={<EllipsisOutlined />} />
                            </Dropdown>
                        }
                    },

                ]}
                summary = {(data) => {
                    let total = (totalAmount < 0) ? 0 : totalAmount;
                    return <>
                        <tr className='table-summary' style={{background: '#fff'}}>
                            <td colspan={9} style={{textAlign: 'right'}}><strong>TOTAL:</strong></td>
                            <td colSpan={6}>
                                <strong className="text-success">{Number(total).toLocaleString('en-US', {
                                    style: 'currency',
                                    currency: 'PHP',
                                })}</strong>
                            </td>
                        </tr>
                    </>
                }}
            />

            {/* <div className="mt-5">
                <Button onClick={() => handleSetupPayMayaWebhookClick()}>Setup PayMaya Webhook</Button>
            </div> */}


            <Modal
                visible={paymentDetailsModal}
                onCancel={(e)=> {
                    setPaymentDetailsModal(false)
                    verifyPaymentForm.resetFields();
                }}
                width={800}
                bodyStyle={{minHeight: '200px'}}
                footer={null}
            >

                <Typography.Title level={4} style={{marginBottom: '20px'}}>
                    Payment Details
                </Typography.Title>
                {
                    paymentDetails != [] ?
                    _.map(paymentDetails, function(record, i) {

                        let label = (record.payment_encode_type === 'online_payment') ? 'Verified' : 'Generated';
                        let date_of_payment = (record.paid_at !== null) ? moment(record.paid_at).format('MMM D, YYYY') : '-';

                        let typeContainer = [];
                        if( record.payment_type !== null ) {
                            let types = record.payment_type.split(',');

                            types = _.map(types, function(value){
                                let type = value.trim();
                                typeContainer.push(paymentTypes[type]);
                            });

                        }

                        return <div key={i}>
                            <>
                                { includedPaymentTypes.includes(record.payment_type) ?
                                    (record.payment_encode_type === 'online_payment') &&
                                        <Badge.Ribbon text={record.is_verified ? 'Verified' : 'For Verification'}color={record.is_verified ? 'green' : 'orange'}>
                                            <div></div>
                                        </Badge.Ribbon>
                                    : ''
                                }
                                <Descriptions bordered size="small" style={{marginBottom: '20px'}}>
                                    <Descriptions.Item span={4} label="Date">{date_of_payment}</Descriptions.Item>
                                    { record.client_number === null || record.client_number === '' ?
                                        <Descriptions.Item span={4} label="Client #">
                                            <>
                                                <Typography.Text editable={{ onChange: (e) => updatePaymentDetail(e, record.id, 'client_number', record.is_verified) }}>
                                                    <span>{record.client_number}</span>
                                                </Typography.Text>
                                                { record.client_number ? '' : <span className="ml-2 text-secondary">Type client number</span>}
                                            </>
                                        </Descriptions.Item> :
                                        <Descriptions.Item span={4} label="Client #">{record.client_number}</Descriptions.Item>
                                    }
                                    { record.reservation_number === null || record.reservation_number === '' ?
                                        <Descriptions.Item span={4} label="Reservation #">
                                            <>
                                                <Typography.Text editable={{ onChange: (e) => updatePaymentDetail(e, record.id, 'reservation_number', record.is_verified) }}>
                                                    {record.reservation_number}
                                                </Typography.Text>
                                                { record.reservation_number ? '' : <span className="ml-2 text-secondary">Type reservation number</span>}
                                            </>
                                        </Descriptions.Item> :
                                        <Descriptions.Item span={4} label="Reservation #">{record.reservation_number}</Descriptions.Item>
                                    }
                                    <Descriptions.Item span={4} label="Transaction #">{record.transaction_id}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Payment Type">{ typeContainer.length > 0 ? typeContainer.join(', ') : ''}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Amount">{numberWithCommas(record.payment_amount)}</Descriptions.Item>
                                    <Descriptions.Item span={4} label="Payment Gateway">{record.payment_gateway}</Descriptions.Item>
                                    {
                                        (record.payment_gateway_reference_number) &&
                                        <Descriptions.Item span={4} label="Gateway Reference #">{record.payment_gateway_reference_number}</Descriptions.Item>
                                    }
                                    {
                                        (record.or_number) &&
                                        <Descriptions.Item span={4} label="OR#">{record.or_number}</Descriptions.Item>
                                    }
                                    {
                                        (record.cr_number) &&
                                        <Descriptions.Item span={4} label="PR#">{record.cr_number}</Descriptions.Item>
                                    }
                                    {
                                        (record.bank) &&
                                        <Descriptions.Item span={4} label="Bank">{record.bank}</Descriptions.Item>
                                    }
                                    {
                                        (record.bank_account_number) &&
                                        <Descriptions.Item span={4} label="Bank Account #">{record.bank_account_number}</Descriptions.Item>
                                    }
                                    {
                                        (record.check_number) &&
                                        <Descriptions.Item span={4} label="Check #">{record.check_number}</Descriptions.Item>
                                    }
                                    {
                                        (record.remarks) &&
                                        <Descriptions.Item span={4} label="Remarks">{record.remarks}</Descriptions.Item>
                                    }
                                    {
                                        (record.verified_by != null) && isVerified &&
                                        <Descriptions.Item span={4} label={`${label} By`}>{`${record.verified_by.first_name} ${record.verified_by.last_name}`}</Descriptions.Item>
                                    }
                                    {
                                        (record.verified_date != null) && isVerified &&
                                        <Descriptions.Item span={4} label={`Date ${label}`}>{moment(record.verified_date).format('YYYY-MM-DD')}</Descriptions.Item>
                                    }
                                    {
                                        (record.verified_date != null && record.advance_payment == 1 ) && isVerified &&
                                        <Descriptions.Item span={4} label="Advance Reservation Payment">
                                            <Tag color='green'>Yes</Tag>
                                        </Descriptions.Item>
                                    }
                                </Descriptions>
                            </>
                            { includedPaymentTypes.includes(record.payment_type) ?
                                (record.client_number == null || record.client_number == '') && (record.reservation_number == null || record.reservation_number == '') && (!isVerified) && record.payment_encode_type == 'online_payment' &&
                                <Alert
                                    style={{padding: '2px 16px 6px 16px', marginBottom: '10px', width: '60%'}}
                                    description="Reservation # or Client # is required to verify this payment."
                                    type="warning"
                                /> : ''
                            }
                            <Form
                                form={verifyPaymentForm}
                                onFinish={verifiedPaymentForm}
                            >
                                <Form.Item name="payment_type" style={{display: 'none'}} initialValue={record.payment_type}>
                                    <Input hidden={true} value={record.payment_type} />
                                </Form.Item>

                                <Form.Item name="is_verified" style={{display: 'none'}} initialValue={1}>
                                    <Input hidden={true} value={1} />
                                </Form.Item>

                                <Form.Item name="client_number" style={{display: 'none'}} initialValue={record.client_number}>
                                    <Input hidden={true} value={record.client_number} />
                                </Form.Item>

                                <Form.Item name="reservation_number" style={{display: 'none'}} initialValue={record.reservation_number}>
                                    <Input hidden={true} value={record.reservation_number} />
                                </Form.Item>
                                {
                                    includedPaymentTypes.includes(record.payment_type) ?
                                        isVerified ? '' :
                                            <>
                                                <Button type="primary" className='mr-2'
                                                    disabled={(record.client_number == null || record.client_number == '') && (record.reservation_number == null || record.reservation_number == '') ? true : false}
                                                    htmlType='submit'
                                                >
                                                    Verify Payment
                                                </Button>
                                                { record.payment_type == 'monthly_amortization_payment' &&
                                                    <Form.Item label="" name="advance_payment" style={{ marginLeft: 135, marginTop: '-30px'}} initialValue={0}>
                                                        <Checkbox
                                                            style={{marginLeft: 16}}
                                                            onChange={(e) => {
                                                                let checked = (e.target.checked) ? 1 : 0;
                                                                setAdvancePayment(checked);
                                                            }}
                                                        >Advance Reservation Payment</Checkbox>
                                                    </Form.Item>
                                                }
                                            </> : ''
                                    // <Button danger onClick={() => setPaymentDetailsModal(false)}>Close</Button>
                                }
                                { record.record_type === 'bulk_upload' && record.payment_encode_type !== 'online_payment' &&
                                    record.allowed_to_update_payment && record.payment_type !== 'penalty' ?
                                    <>
                                        <Popconfirm
                                            title="Are you sure you want to delete this payment?"
                                            onConfirm={() => deletePaymentDetail(record)}
                                            onCancel={() => console.log("cancelled delete payment")}
                                            okText="Yes"
                                            cancelText="No"
                                        >
                                            <Button danger className='ml-2'>Delete</Button>
                                        </Popconfirm>
                                        <Button type="primary ml-2" onClick={() => openUpdatePaymentModal(record)}>Update</Button>
                                    </> : <Button danger onClick={() => setPaymentDetailsModal(false)}>Close</Button>
                                }
                            </Form>
                        </div>
                    }) : 'No Payment found'
                }

            </Modal>

            <Modal
                visible={updatePaymentModal}
                onCancel={() => {
                    formUpdatePayment.resetFields();
                    setUpdatePaymentModal(false);
                    setUpdatePaymentData([]);
                }}
                afterClose={(e) => {
                    formUpdatePayment.resetFields();
                    setUpdatePaymentModal(false)
                    setUpdatePaymentData([]);
                }}
                footer={null}
                title="Update Payment"
                destroyOnClose={true}
            >
                <Descriptions bordered size="small">
                    <Descriptions.Item span={4} label="Transaction #">{updatePaymentData.transaction_id}</Descriptions.Item>
                    <Descriptions.Item span={4} label="Payment Type">{paymentTypes[updatePaymentData.payment_type]}</Descriptions.Item>
                    { updatePaymentData.payment_type === 'penalty_fee' &&
                    <Descriptions.Item span={4} label="Discounted Amount">
                        {
                            numberWithCommas(twoDecimalPlace(
                                parseFloat(updatePaymentData.payment_amount) - ((parseFloat(updatePaymentData.discount) / 100) * parseFloat(updatePaymentData.payment_amount))
                            ))
                        }
                    </Descriptions.Item>
                    }
                </Descriptions>

                <Form
                    layout={"horizontal"}
                    labelCol={{ span: 8 }}
                    wrapperCol={{ span: 14 }}
                    form={formUpdatePayment}
                    onFinish={updatePaymentFinish}
                ><br />

                    <Form.Item label="Payment (PHP)" name="payment_amount" rules={[{required:true}]} initialValue={updatePaymentData.payment_amount}>
                        <InputNumber min={0}
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, payment_amount: e })}
                        />
                    </Form.Item>
                    {
                        updatePaymentData.payment_type === 'penalty_fee' &&
                        <Form.Item label="Discount" name="discount" initialValue={updatePaymentData.discount}>
                            <InputNumber
                                formatter={value => `${value}%`}
                                parser={value => value.replace('%', '')}
                                min={0}
                                max={100}
                                style={{width: 100}}
                                size="large"
                                onChange={(e)=> {
                                    let discount = ( e === '' || parseFloat(e) < 0 ) ? 0 : e;
                                    setUpdatePaymentData({ ...updatePaymentData, discount: discount })
                                }}
                            />
                        </Form.Item>
                    }

                    <Form.Item label="Date paid" name="paid_at" rules={[{required: true}]}
                        initialValue={moment(updatePaymentData.paid_at)}
                        >
                        <DatePicker
                            style={{width: 200}}
                            size="large"
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, paid_at: e ? moment(e) : undefined })}
                        />
                    </Form.Item>

                    <Form.Item label="Payment gateway" rules={[{required: true}]} name="payment_gateway" initialValue={updatePaymentData.payment_gateway}>
                        <Select style={{marginLeft: 4, width: '100%'}}
                            onChange={(e) => setUpdatePaymentData({ ...updatePaymentData, payment_gateway: e})}
                            >
                            <Select.OptGroup label="Online Payment">
                                <Select.Option value="DragonPay">DragonPay</Select.Option>
                                <Select.Option value="PayMaya">PayMaya</Select.Option>
                                <Select.Option value="PesoPay">PesoPay</Select.Option>
                            </Select.OptGroup>
                            <Select.Option value="Cash">Cash</Select.Option>
                            <Select.Option value="PDC">PDC</Select.Option>
                            <Select.Option value="Direct Payment">Direct Payment</Select.Option>
                            <Select.Option value="Card Transaction">Card Transaction</Select.Option>
                        </Select>
                    </Form.Item>

                </Form>

                <Popconfirm
                    title="Are you sure you want to update payment details?"
                    onConfirm={() => formUpdatePayment.submit()}
                    onCancel={() => console.log("cancelled update payment")}
                    okText="Yes"
                    cancelText="No"
                >
                    <Button block type="primary" className='mt-2'>Update</Button>
                </Popconfirm>

            </Modal>

            <Modal
                visible={bulkUploadModal}
                onCancel={(e)=> setBulkUploadModal(false)}
                width="80%"
                bodyStyle={{minHeight: '200px'}}
                footer={null}
            >

                <Row gutter={16}>
                    <Col span={6}>
                        <Card>
                        <Statistic
                            title="UPLOADING"
                            value={(typeof bulkUploadData.ok_display !== 'undefined') ? bulkUploadData.ok_display.count : 0}
                            valueStyle={{ color: '#3f8600' }}
                        />
                        </Card>
                    </Col>
                    <Col span={6}>
                        <Card>
                        <Statistic
                            title="NOT UPLOADING"
                            value={(typeof bulkUploadData.not_ok_display !== 'undefined') ? bulkUploadData.not_ok_display.count : 0}
                            valueStyle={{ color: '#cf1322' }}
                        />
                        </Card>
                    </Col>
                </Row>

                { typeof bulkUploadData.ok_display !== 'undefined' && bulkUploadData.ok_display.count > 0 &&
                    <>
                        <Divider orientation="left"><Tag color="green">UPLOADING</Tag></Divider>
                        <Table
                            scroll={{ x: 1200 }}
                            size="small"
                            className="mt-4"
                            rowKey="number"
                            dataSource={ bulkUploadData.ok_display.data }
                            columns={report_column}
                        />
                    </>
                }

                { typeof bulkUploadData.not_ok_display !== 'undefined' && bulkUploadData.not_ok_display.count > 0 &&
                    <>
                        <Divider orientation="left"><Tag color="red">NOT UPLOADING</Tag></Divider>
                        <Table
                            scroll={{ x: 1300 }}
                            size="small"
                            className="mt-4"
                            rowKey="number"
                            dataSource={ bulkUploadData.not_ok_display.data }
                            columns={report_column}
                            expandable={{
                                expandedRowRender: (record) => {
                                    return <p style={{ margin: '0px 0px 0px 60px' }}>{record.message}</p>
                                }
                            }}
                        />
                    </>
                }
                {bulkUploadQueryIsLoading &&<Button type="primary" className='mt-2 mr-2' disabled={bulkUploadQueryIsLoading}>Uploading...</Button>}
                {
                  typeof bulkUploadData.ok_display !== 'undefined' && bulkUploadData.ok_display.count > 0 && !bulkUploadQueryIsLoading &&
                    <Popconfirm
                        title="Are you sure you want to upload the payments?"
                        onConfirm={() => uploadBulkPayments()}
                        onCancel={() => console.log('close pop confirm')}
                        okText="Yes"
                        cancelText="No"
                    >

                        <Button type="primary" className='mt-2 mr-2'>Upload</Button>
                        </Popconfirm>
                }

                <Button onClick={() => {
                        setBulkUploadModal(false);
                        setBulkUploadData([]);
                    }} type="default" className='mt-2'>Cancel</Button>

            </Modal>

            {
                typeof bulkUploadData.ok_display !== 'undefined' && bulkUploadModal === false &&

                <div style={{position: 'fixed', bottom: '50px', right: '50px'}}>

                    <Button onClick={() => setBulkUploadModal(true)} type="primary" shape="circle" icon={<UploadOutlined />} size={'large'} isLoading={bulkUploadQueryIsLoading}/>
                </div>
            }


        </div>
    )
}
