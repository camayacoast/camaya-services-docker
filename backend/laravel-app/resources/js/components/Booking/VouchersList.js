import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import VoucherService from 'services/Booking/VoucherService'
import { Descriptions, Tabs, DatePicker, Upload, Table, Button, Typography, Space, message, Popconfirm, Row, Col, Form, Modal, Input, Select, InputNumber, TimePicker, notification, Alert, } from 'antd'
import Icon, { EditOutlined, LinkOutlined, MailOutlined, PhoneOutlined, PlusOutlined, UserOutlined, PrinterOutlined, RightCircleFilled } from '@ant-design/icons'
import {queryCache} from 'react-query'

import ReactExport from "react-export-excel";
const ExcelFile = ReactExport.ExcelFile;
const ExcelSheet = ReactExport.ExcelFile.ExcelSheet;
const ExcelColumn = ReactExport.ExcelFile.ExcelColumn;

import EditVoucherStubComponent from 'components/Booking/Vouchers/EditVoucherStub'

import CustomerService from 'services/Booking/Customer'

const { TabPane } = Tabs;

const NewVoucherForm = (props) => {

    const initialUploadData = {
        previewVisible: false,
        previewImage: '',
        previewTitle: '',
        fileList: [],
    };

    const [newVoucherQuery, {isLoading: newVoucherIsLoading, reset: newVoucherQueryReset, error: newVoucherQueryError}] = VoucherService.create();
    const [removeImageQuery, {isLoading: removeImageQueryIsLoading, error: removeImageQueryError}] = VoucherService.imageUploadRemove();
    const [deleteImage, {isLoading: deleteImageIsLoading, error: deleteImageError}] = VoucherService.deleteImage();
    const [uploadedImages, setUploadedImages] = React.useState([]);
    const [uploadData, setUploadData] = React.useState(initialUploadData);

    React.useEffect( () => {
        setUploadedImages([]);
        setUploadData(initialUploadData);
        props.form.resetFields();
    },[]);

    const newVoucherFormOnFinish = (values) => {

        if (newVoucherIsLoading) {
            return false;
        }

        const newValues = {
            ...values,
            voucher_images: uploadedImages,
        }
        
        newVoucherQuery(newValues, {
            onSuccess: (res) => {
                console.log(res);
                props.setModalVisible(false);
                message.success('Added voucher successfully!');

                queryCache.setQueryData("vouchers", prev => [...prev, res.data]);

                //Reset fields
                props.form.resetFields();
                newVoucherQueryReset();

                notification.success({
                    message: `New Package - ${res.data.code} Added!`,
                    description:
                        ``,
                });

                setUploadedImages([]);
                setUploadData(initialUploadData);
            },
            onError: (e) => {
                console.log(e);
                message.info(e.message+" Please check for errors.");
                newVoucherQueryReset();
            }
        })
    }

    // Functions Image
    const handleCancel = () => { 
        setUploadData( prev => ({...prev, previewVisible: false}) );
    };

    const handlePreview = async file => {
        if (!file.url && !file.preview) {
          file.preview = await getBase64(file.originFileObj);
        }

        if (!file.preview) {
            const image = new Image();
            image.src = file.url;
            const imgWindow = window.open(file.url);
            imgWindow.document.write(image.outerHTML);

            return;
        }
    
        setUploadData( prev => ({
                // fileList: [...prev.fileList],
                previewImage: file.url || file.preview,
                previewVisible: true,
                previewTitle: file.name || file.url.substring(file.url.lastIndexOf('/') + 1),
            })
        );
    };

    const handleChange = ({ fileList, file, event }) => {
        console.log(fileList);

        setUploadData( prev => ({...prev, fileList: fileList }));

        if (file.status == 'done') {
            // const viewIdSet = viewPackageForm.getFieldValue('id');

            // if (viewIdSet) {
            //     const response = file.response;

            //     packageListQuery.data.forEach(function (plqdPackage, index) {
            //         if (plqdPackage.id === viewIdSet) {
            //             const plqdPackageImage = packageListQuery.data[index].images || [];
            //             plqdPackageImage.push(response);
            //             packageListQuery.data[index].images = plqdPackageImage;
            //         }
            //     });
            // } else {
                setUploadedImages( prev => [...prev, file.response.path]);
            // }
        }
    };

    const handleRemove = async file => {

        if (file.url) { // file is saved in database
            // deleteImage(file.uid, {
            //     onSuccess: (res) => {
            //         console.log(res);
            //         const viewIdSet = viewPackageForm.getFieldValue('id');

            //         packageListQuery.data.forEach(function (plqdPackage, index) {
            //             if (plqdPackage.id === viewIdSet) {
            //                 const plqdPackageImages = plqdPackage.images;
            //                 const newPackageImages = _.reject(plqdPackageImages, { id: file.uid });

            //                 packageListQuery.data[index].images = newPackageImages;
            //             }
            //         });
            //     },
            //     onError: (e) => {
            //         console.log(e);
            //     }
            // })            

            return;
        }

        setUploadedImages( prev => _.filter(prev, (item) =>  item != file.response.path));

        removeImageQuery({ files_to_remove: ["public/"+file.response.file_name] },{
            onSuccess: (res) => {
                console.log(res);
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const uploadButton = (
        <div>
          <PlusOutlined />
          <div style={{ marginTop: 8 }}>Upload</div>
        </div>
      );

    const ShowTagItemError = ({name}) => {
        let errors = [];

        if (newVoucherQueryError && newVoucherQueryError.errors) {
            _.map(newVoucherQueryError.errors, (error, key) => {
                if (key.split('.')[0] == name) {
                    errors.push(error);
                }
            });

            return <div role="alert" style={{color: '#ff4d4f'}}>{errors.join(', ')}</div>
        }
        return null;
    }

    const ShowFormItemError = ({name}) => {


        if (newVoucherQueryError && newVoucherQueryError.errors && newVoucherQueryError.errors[name]) {
            document.querySelector('#'+name).scrollIntoView();

            return newVoucherQueryError.errors[name].map( (item, key) => (
                    <div role="alert" style={{color: '#ff4d4f'}} key={key}>{item}</div>
                ))
        }
        return null;
    }

    return <Form {...props} onFinish={newVoucherFormOnFinish} scrollToFirstError={true}>
        <Row gutter={[12,12]}>
            <Form.Item name="id" noStyle>
                <Input type="hidden" />
            </Form.Item>
            <Col xl={12}>
                <Form.Item
                    name="name"
                    label="Name"
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
                <Form.Item id="code" name="code" label="Code" extra={<ShowFormItemError name="code" />} rules={[
                    {
                        pattern: /^\S*$/,
                        message: 'No spaces allowed. Use underscore (_) instead.'
                    },
                    {
                        required: true
                    }
                ]}>
                    <Input style={{textTransform: 'uppercase'}}/>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Typography.Text className="mb-2">Voucher images</Typography.Text>
                    <Upload
                        action={
                            props.type === 'new' 
                            ? `${process.env.APP_URL}/api/booking/voucher/image-upload`
                            : `${process.env.APP_URL}/api/booking/voucher/add-image`
                        }
                        headers={
                            {
                                Authorization: 'Bearer '+localStorage.getItem('token'),
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        }
                        data={
                            props.type === 'new'
                            ? null
                            : { id: viewPackageForm.getFieldValue('id') }
                        }
                        listType="picture-card"
                        fileList={uploadData.fileList}
                        onPreview={handlePreview}
                        onChange={handleChange}
                        onRemove={handleRemove}
                        >
                        {uploadData.fileList.length >= 8 ? null : uploadButton}
                        </Upload>
                        <Modal
                            visible={uploadData.previewVisible}
                            title={uploadData.previewTitle}
                            footer={null}
                            onCancel={handleCancel}
                            >
                            <img alt="product image" style={{ width: '100%' }} src={uploadData.previewImage} />
                        </Modal>
            </Col>
            <Col xl={12}>
                <Form.Item name="type" label="Type"
                rules={[
                    {
                        required: true
                    }
                ]}>
                    <Select>
                        <Select.Option value="per_booking">Per booking</Select.Option>
                        <Select.Option value="per_guest">Per guest</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="availability" label="Availability"
                rules={[
                    {
                        required: true
                    }
                ]}>
                    <Select>
                        <Select.Option value="for_dtt">For day tour</Select.Option>
                        <Select.Option value="for_overnight">For overnight</Select.Option>
                        <Select.Option disabled value="for_dtt_and_overnight">For day tour and overnight</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="category" label="Category">
                    <Select>
                        <Select.Option value="">None</Select.Option>
                        <Select.Option value="golf">Golf</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="mode_of_transportation" label="Mode of transportation"
                onChange={val => setModeOfTransportation(val)}
                rules={[
                    {
                        required: true
                    }
                ]}>
                    <Select>
                        <Select.Option value="own_vehicle">Own vehicle</Select.Option>
                        <Select.Option value="camaya_transportation">Camaya transportation</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="allowed_days" label="Allowed days"
                rules={[
                    {
                        required: true
                    }
                ]}>
                    <Select mode="multiple">
                        <Select.Option value="mon">Monday</Select.Option>
                        <Select.Option value="tue">Tueday</Select.Option>
                        <Select.Option value="wed">Wednesday</Select.Option>
                        <Select.Option value="thu">Thursday</Select.Option>
                        <Select.Option value="fri">Friday</Select.Option>
                        <Select.Option value="sat">Saturday</Select.Option>
                        <Select.Option value="sun">Sunday</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="price" label="Price"
                    rules={[
                        {
                            required: true
                        }
                    ]}
                >
                    <InputNumber min="1"/>
                </Form.Item>
            </Col>
            {/* <Col xl={24}>
                Package Price Ranges
            </Col> */}
            <Col xl={24}>
                <Form.Item name="exclude_days" label="Exclude days"
                    extra={<ShowTagItemError name="exclude_days" />}
                // rules={[
                //     {
                //         pattern: /^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$/,
                //         message: 'Format not allowed.'
                //     },
                // ]}
                >
                    <Select mode="tags" placeholder="format:YYYY-MM-DD" tokenSeparators={[',',';',' ']}/>
                </Form.Item>
            </Col>

            <Col xl={12}>
                <Form.Item name="selling_start_date" label="Selling start date"
                >
                    <DatePicker/>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="selling_end_date" label="Selling end date"
                >
                    <DatePicker/>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="booking_start_date" label="Booking start date"
                >
                    <DatePicker/>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="booking_end_date" label="Booking end date"
                >
                    <DatePicker/>
                </Form.Item>
            </Col>

            <Col xl={12}>
                <Form.Item name="status" label="Status"
                    rules={[{required: true}]}
                >
                    <Select>
                        <Select.Option value="unpublished">Unpublished</Select.Option>
                        <Select.Option value="published">Published</Select.Option>
                        <Select.Option value="expired">Expired</Select.Option>
                        <Select.Option value="ended">Ended</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="quantity_per_day" label="Quantity per day">
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="stocks" label="Stocks">
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Form.Item name="description" label="Description">
                    <Input.TextArea style={{borderRadius:'12px'}}/>
                </Form.Item>
            </Col>

            <Col xl={24}>
                <Button htmlType="submit">Save</Button>
            </Col>

        </Row>
    </Form>
    //  $table->dateTime('starttime',0)->nullable();
    //  $table->dateTime('endtime',0)->nullable();
    //  $table->timestamps();
}

const GenerateNewVoucherForm = (props) => {

    const [generateNewVoucherQuery, {isLoading: generateNewVoucherQueryIsLoading, reset: generateNewVoucherQueryReset, error: generateNewVoucherQueryError}] = VoucherService.generate();

    const [generateNewVoucherForm] = Form.useForm();
    const [newCustomerModalVisible, setnewCustomerModalVisible] = React.useState(false);
    const [newCustomerQuery, {isLoading: newCustomerQueryIsLoading, error: newCustomerQueryError}] = CustomerService.create();
    const customerListQuery = CustomerService.list(props.isTripping || false);

    const [newCustomerForm] = Form.useForm();
    const newCustomerFormFinish = (values) => {
        console.log(values);

        const newValues = {
            ...values,
            // isAgent: props.isTripping ? true : false,
        }

        if (newCustomerQueryIsLoading) {
            message.info("Saving ...");
            return false;
        }

        newCustomerQuery(newValues, {
            onSuccess: (res) => {
    
              console.log(res);
    
              queryCache.setQueryData(['customers'], prev => [...prev, res.data]);
            // customerListQueryData.push(res.data);
      
              notification.success({
                  message: `New ${props.isTripping ? 'Agent' : 'Customer'} added`,
                  description:
                      ``,
              });
    
              // Reset Forms
              newCustomerForm.resetFields();
    
            },
            onError: (e) => {
                message.info(e.errors.email)
            }
        });
    }

    const onFinish = (values) => {


        if (generateNewVoucherQueryIsLoading) {
            return false;
        }

        generateNewVoucherQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                generateNewVoucherQueryReset();

                props.setVoucherModalVisible(false);
                generateNewVoucherForm.resetFields();

                Modal.info({
                    title: 'Voucher Details',
                    icon: null,
                    content: <>
                        <div><strong>Voucher Code:</strong> {res.data.voucher_code}</div>
                        <div><strong>Voucher:</strong> {res.data.voucher.name} - {res.data.voucher.code}</div>
                        <div><strong>Validity:</strong> {moment(res.data.validity_start_date).format('YYYY-MM-DD')} ~ {moment(res.data.validity_end_date).format('YYYY-MM-DD')}</div>
                    </>
                });
            },
            onError: (e) => {
                message.info(e.message);
                generateNewVoucherQueryReset();
            }
        })
    }

    const SelectItem = (props) => {

        return <div>
            {
                props.item.images && props.item.images.length ? <img src={props.item.images[0].image_path} style={{width:40, padding: 5}} />: '-'
            }
            <small><strong>{props.item.name}</strong></small>
            <br/>
            
            {/* <p>{props.item.description}</p> */}
            {/* <small>{props.item.type} | {props.item.availability} | {props.item.mode_of_transportation}</small> */}
            
            <small>Voucher Code: {props.item.code}<span className="text-success" style={{float:'right'}}>PHP {props.item.price}</span></small>
        </div>
    }

    return <>

        <Modal
            visible={newCustomerModalVisible}
            onCancel={()=>setnewCustomerModalVisible(false)}
            footer={null}>
                <Form
                layout="vertical"
                form={newCustomerForm}
                onFinish={newCustomerFormFinish}
            >
                <Row gutter={[12,12]} className="mt-4">
                    <Col xl={24}>
                        <Alert message="All fields are required except for middle name." type="info" className="mb-2" />
                    </Col>
                    <Col xl={24}>
                        <Form.Item name="first_name" label="First name" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Input placeholder="First name" />
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="middle_name" label="Middle name">
                            <Input placeholder="Middle name (optional)" />
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="last_name" label="Last name" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Input placeholder="Last name" />
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="nationality" label="Nationality" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Input placeholder="Nationality" />
                        </Form.Item>
                    </Col>
                    <Col xl={12}>
                        <Form.Item name="contact_number" label="Contact number" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Input placeholder="Contact number" />
                        </Form.Item>
                    </Col>
                    <Col xl={24}>
                        <Form.Item name="email" label="Email address" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Input placeholder="Email address" />
                        </Form.Item>
                    </Col>
                    <Col xl={24}>
                        <Form.Item name="address" label="Address" rules={[
                            {
                                required: true
                            }
                        ]}>
                            <Input.TextArea style={{borderRadius: 12}} placeholder="Address" />
                        </Form.Item>
                    </Col>
                </Row>
                <div className="mt-4" style={{textAlign:'right'}}>
                    <Button onClick={()=>newCustomerForm.submit()}>Save {props.isTripping ? 'Agent' : 'Customer'}</Button>
                </div>
            </Form>
        </Modal>
    
        <Form
            form={generateNewVoucherForm}
            onFinish={onFinish}
            {...props.formOptions}
        >

        <Row gutter={[8,8]}>
            <Col xl={24}>
                <Form.Item label={`Select a customer`} name="customer" rules={[{required:true}]}>
                    <Select
                        showSearch
                        style={{ width: '100%' }}
                        placeholder={`Select ${props.isTripping ? 'an agent or customer' : 'a customer'}`}
                        optionFilterProp="children"
                        size="large"
                        filterOption={(input, option) =>
                            option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                        }
                        dropdownRender={menu => (<div>
                            {menu}
                            <Button type="link" block onClick={()=>setnewCustomerModalVisible(true)}>New customer</Button>
                            </div>
                        )}
                    >
                        { customerListQuery.data &&
                            customerListQuery.data.map( (item, key) => (
                                <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Select.Option>
                            ))
                        }
                    </Select>
                </Form.Item>
                <Form.Item name="voucher_stub" label="Voucher stub"
                        rules={[{required: true}]}
                    >
                    <Select style={{width: '100%'}}>
                        <Select.Option value="">-</Select.Option>
                        {
                            props.voucherStubs.map( (item, key) => {
                                return <Select.Option key={key} value={item.id}>
                                    <SelectItem item={item} />
                                </Select.Option>
                            })
                        }
                        
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Button htmlType="submit">Generate</Button>
            </Col>
        </Row>
            
        </Form>
    </>

}

function Page(props) {

    const voucherListQuery = VoucherService.list();
    const generatedVouchersQuery = VoucherService.generatedList();
    const [changeVoucherStatusQuery, {isLoading: changeVoucherStatusQueryIsLoading, reset: changeVoucherStatusQueryReset}] = VoucherService.changeStatus();
    const [changeVoucherPaymentStatusQuery, {isLoading: changeVoucherPaymentStatusQueryIsLoading, reset: changeVoucherPaymentStatusQueryReset}] = VoucherService.changePaymentStatus();

    const [changeVoucherPaidAtQuery, {isLoading: changeVoucherPaidAtQueryIsLoading, reset: changeVoucherPaidAtQueryReset}] = VoucherService.changePaidAt();
    const [changeVoucherModeOfPaymentQuery, {isLoading: changeVoucherModeOfPaymentQueryIsLoading, reset: changeVoucherModeOfPaymentQueryReset}] = VoucherService.changeModeOfPayment();

    const [modalVisible, setModalVisible] = React.useState(false);
    const [voucherModalVisible, setVoucherModalVisible] = React.useState(false);
    const [searchString, setSearchString] = React.useState(null);
    const [editVoucherStubModalVisible, setEditVoucherStubModalVisible] = React.useState(false);
    const [editVoucherStubRecord, setEditVoucherStubRecord] = React.useState({});

    const [resendVoucherConfirmationQuery, {isLoading: resendVoucherConfirmationQueryIsLoading, reset: resendVoucherConfirmationQueryReset}] = VoucherService.resendVoucherConfirmation();

    const [newVoucherForm] = Form.useForm();

    React.useEffect( () => {
        if (editVoucherStubModalVisible == false) {
            setEditVoucherStubRecord({});
        }
    }, [editVoucherStubModalVisible]);

    const changeTab = (key) => {
        console.log(key);
    }

    const handleVoucherStatusChange = (id, value) => {
        // console.log(id, value);

        if (changeVoucherStatusQueryIsLoading) {
            return false;
        }

        changeVoucherStatusQuery({
            id: id,
            status: value,
        }, {
            onSuccess: (res) => {
                console.log(res);
                changeVoucherStatusQueryReset();
                message.success("Voucher status has been changed.");
            },
            onError: (e) => {
                message.info(e.message);
                changeVoucherStatusQueryReset();
            }
        });
    }

    const handleVoucherPaymentStatusChange = (id, value) => {
        // console.log(id, value);

        if (changeVoucherPaymentStatusQueryIsLoading) {
            return false;
        }

        changeVoucherPaymentStatusQuery({
            id: id,
            payment_status: value,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Voucher payment status has been changed.");
                changeVoucherPaymentStatusQueryReset();
            },
            onError: (e) => {
                message.info(e.message);
                changeVoucherPaymentStatusQueryReset();
            }
        });
    }

    const handleVoucherPaidAtChange = (id, value) => {
        // console.log(id, value);

        if (changeVoucherPaidAtQueryIsLoading) {
            return false;
        }

        changeVoucherPaidAtQuery({
            id: id,
            paid_at: value,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Voucher paid at has been changed.");
                changeVoucherPaidAtQueryReset();
            },
            onError: (e) => {
                message.info(e.message);
                changeVoucherPaidAtQueryReset();
            }
        });
    }

    const handleVoucherModeOfPaymentChange = (id, value) => {
        // console.log(id, value);

        if (changeVoucherModeOfPaymentQueryIsLoading) {
            return false;
        }

        changeVoucherModeOfPaymentQuery({
            id: id,
            mode_of_payment: value,
        }, {
            onSuccess: (res) => {
                console.log(res);
                message.success("Voucher paid at has been changed.");
                changeVoucherModeOfPaymentQueryReset();
            },
            onError: (e) => {
                message.info(e.message);
                changeVoucherModeOfPaymentQueryReset();
            }
        });
    }

    const searchVoucher = (search) => {
        setSearchString(search.toLowerCase());
    }

    const handleEditVoucherStubClick = (record) => {
        setEditVoucherStubModalVisible(true);

        setEditVoucherStubRecord(record);
    }

    const handleResendVoucherConfirmation = (ref_no) => {

        if (resendVoucherConfirmationQueryIsLoading) {
            message.info('Sending voucher confirmation email. Please wait...');
            return false;
        }

        resendVoucherConfirmationQuery({transaction_reference_number: ref_no }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Voucher confirmation email sent!');
                resendVoucherConfirmationQueryReset();
            },
            onError: (e) => {
                // console.log(e);
                message.error('Failed to send voucher confirmation.');
                resendVoucherConfirmationQueryReset();
            }
        });
    }

    return (
        <>

            <Modal
                title="Edit Voucher Stub"
                visible={editVoucherStubModalVisible}
                onCancel={()=> setEditVoucherStubModalVisible(false)}
                footer={null}
            >
                { (editVoucherStubRecord && editVoucherStubModalVisible) && <EditVoucherStubComponent voucherListQuery={voucherListQuery} setEditVoucherStubModalVisible={setEditVoucherStubModalVisible} setEditVoucherStubRecord={setEditVoucherStubRecord} voucherStub={editVoucherStubRecord} /> }
            </Modal>
            <Typography.Title level={5}>Vouchers</Typography.Title>

            <Tabs defaultActiveKey="1" onChange={changeTab}>
                <TabPane tab="Generated vouchers" key="1">

                        <Row gutter={[24,24]}>
                            <Col xl={24} xs={24} style={{padding: 20}}>
                                <div style={{display:'flex', flexDirection: 'row', justifyContent:'space-between', alignItems: 'flex-start'}}>
                                    <div>
                                        <Input style={{width: 400}} type="text" placeholder="Search transaction ref # or voucher code" className="mr-2 my-3" onChange={(e) => searchVoucher(e.target.value)} />
                                        <Button onClick={()=>setVoucherModalVisible(true)}>New Voucher</Button>
                                    </div>
                                    <div>
                                        <ExcelFile filename={`Generated_Vouchers_Report_${moment().format('YYYY-MM-DD HH:mm:ss')}`} element={<Button className="mt-3"><PrinterOutlined/></Button>}>
                                            <ExcelSheet data={generatedVouchersQuery.data} name="generated_vouchers">
                                                <ExcelColumn label="Transaction Ref #" value="transaction_reference_number"/>
                                                <ExcelColumn label="Code" value="voucher_code"/>
                                                <ExcelColumn label="Customer" value={ col => col.customer_id == null ? '' : `${col.customer.first_name} ${col.customer.last_name} `}/>
                                                <ExcelColumn label="Email" value={ col => col.customer_id == null ? '' : `${col.customer.email}` }/>
                                                <ExcelColumn label="Address" value={ col => col.customer_id == null ? '' : `${col.customer.address} `}/>
                                                <ExcelColumn label="Voucher Status" value="voucher_status"/>
                                                <ExcelColumn label="Purchased Date" value={ col => moment(col.created_at).format('MMM D, YYYY')}/>
                                                <ExcelColumn label="Expiration" value={ col => col.paid_at != null ? 'Paid' : moment(col.created_at).add(2, 'days').calendar() }/>
                                                <ExcelColumn label="Payment Status" value="payment_status"/>
                                                <ExcelColumn label="Paid At" value={ col => col.paid_at == null ? '' : moment(col.paid_at).format('MMM D, YYYY')} />
                                                <ExcelColumn label="Mode of Payment" value="mode_of_payment" />
                                                <ExcelColumn label="Price" value={ col => parseFloat(col.price) } />
                                                <ExcelColumn label="Voucher" value={ col => `${col.voucher.name}` } />
                                                <ExcelColumn label="Created By" value={ col => col.created_by == null ? 'System generated' : `${col.created_by.first_name} ${col.created_by.last_name}` } />
                                                <ExcelColumn label="Provider Ref. No." value="provider_reference_number"/>
                                            </ExcelSheet>
                                        </ExcelFile>
                                    </div>
                                </div>
                            </Col>
                            
                        </Row>

                        <Modal
                            title="New voucher"
                            visible={voucherModalVisible}
                            onCancel={()=>setVoucherModalVisible(false)}
                            footer={null}
                        >
                            <GenerateNewVoucherForm
                                voucherStubs={voucherListQuery.data ? voucherListQuery.data : []}
                                setVoucherModalVisible={setVoucherModalVisible}
                                formOptions={{
                                    layout: 'vertical',
                                    initialValues:{
                                    }
                                }}
                            />
                        </Modal>

                        <Table
                            size="small"
                            dataSource={generatedVouchersQuery.data ? 
                                generatedVouchersQuery.data
                                .filter(item => {
                                    if (item && searchString) {
                                        const searchValue =  item.transaction_reference_number.toLowerCase() + " " + item.voucher_code.toLowerCase();
                                        return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                                    }
                                    return true;
                                })
                                : []}
                            loading={generatedVouchersQuery.isLoading}
                            rowKey="id"
                            scroll={{
                                // y: 240,
                                x: '200vw'
                            }}
                            columns={[
                                // {
                                //     title: 'ID',
                                //     dataIndex: 'id',
                                //     key: 'id',
                                // },
                                
                                {
                                    title: 'Voucher Confirmation',
                                    render:(text, record) => <Button onClick={()=>handleResendVoucherConfirmation(record.transaction_reference_number)}>Send Email</Button>
                                },
                                {
                                    title: 'Transaction reference #',
                                    dataIndex: 'transaction_reference_number',
                                    key: 'transaction_reference_number',
                                    render: (text) => <strong>{text}</strong>
                                },
                                {
                                    title: 'Code',
                                    dataIndex: 'voucher_code',
                                    key: 'voucher_code',
                                    render: (text) => <strong>{text}</strong>
                                },
                                {
                                    title: 'Customer',
                                    render: (text, record) => record.customer ? <div><UserOutlined/> {record.customer.first_name} {record.customer.last_name}
                                        <br/><small><MailOutlined/> {record.customer.email}</small>
                                        <br/><small><PhoneOutlined/> {record.customer.contact_number}</small>
                                        </div> : ''
                                },
                                {
                                    title: 'Voucher status',
                                    dataIndex: 'voucher_status',
                                    key: 'voucher_status',
                                    render: (text, record) => {
                                        return <Select disabled={(record.voucher_status == 'cancelled' || record.voucher_status == 'redeemed')} style={{width: 100}} defaultValue={record.voucher_status} onChange={e => handleVoucherStatusChange(record.id, e)}>
                                            <Select.Option value="new">New</Select.Option>
                                            <Select.Option disabled value="active">Active</Select.Option>
                                            <Select.Option value="redeemed">Redeemed</Select.Option>
                                            <Select.Option value="voided">Voided</Select.Option>
                                            <Select.Option disabled value="cancelled">Cancelled</Select.Option>
                                        </Select>
                                    }
                                },
                                {
                                    title: 'Purchased date',
                                    render: (text, record) => moment(record.created_at).format('DD MMM YYYY')
                                },
                                {
                                    title: 'Expires',
                                    render: (text, record) => {

                                        if (record.paid_at) {
                                            return "Paid";
                                        } else {
                                            return record.cancelled_at ? <span className="text-danger">Expired: {moment(record.cancelled_at).format('DD MMM YYYY')}</span> : moment(record.created_at).add(2, 'days').calendar()
                                        }
                                        
                                    }
                                },
                                {
                                    title: 'Payment status',
                                    dataIndex: 'payment_status',
                                    key: 'payment_status',
                                    render: (text, record) => {
                                        return <>
                                        <Select disabled={record.mode_of_payment == 'online_payment'} style={{width: 100}} defaultValue={record.payment_status} onChange={e => handleVoucherPaymentStatusChange(record.id, e)}>
                                            <Select.Option value="unpaid">Unpaid</Select.Option>
                                            <Select.Option value="paid">Paid</Select.Option>
                                        </Select>
                                        </>
                                    }
                                },
                                {
                                    title: 'Paid at',
                                    render: (text, record) => {
                                        return <>
                                            <DatePicker disabled={record.mode_of_payment == 'online_payment'} defaultValue={record.paid_at ? moment(record.paid_at) : null} onChange={e => handleVoucherPaidAtChange(record.id, e)} />
                                        </>
                                    }
                                },
                                {
                                    title: 'Mode of payment',
                                    render: (text, record) => {
                                        return <>
                                        <Select disabled={record.mode_of_payment == 'online_payment'} style={{width: 150}} defaultValue={record.mode_of_payment} onChange={e => handleVoucherModeOfPaymentChange(record.id, e)}>
                                            <Select.Option value="none">None</Select.Option>
                                            <Select.Option disabled value="online_payment">Online Payment</Select.Option>
                                            <Select.Option value="bank_deposit">Bank Deposit</Select.Option>
                                        </Select>
                                        </>
                                    }
                                },
                                {
                                    title: 'Price',
                                    dataIndex: 'price',
                                    key: 'price',
                                    render: (text, record) => <span className="text-success">&#8369; {text}</span>
                                },
                                {
                                    title: 'Voucher',
                                    render: (text, record) => <>
                                        <Descriptions bordered title={`${record.voucher.name} - ${record.voucher.code}`} size="small">
                                            <Descriptions.Item>{record.voucher.images && record.voucher.images.length ? <img src={record.voucher.images[0].image_path} style={{width: 80}}/> : 'No image'}</Descriptions.Item>
                                            <Descriptions.Item label="Availability">{record.voucher.availability}</Descriptions.Item>
                                            <Descriptions.Item label="Type">{record.voucher.type}</Descriptions.Item>
                                        </Descriptions>
                                    </>
                                },
                                {
                                    title: 'Created by',
                                    render: (text, record) => <>{record.created_by ? <>{record.created_by.first_name} {record.created_by.last_name}</> : <small>System generated</small>}</>
                                },
                                {
                                    title: 'Provider reference number',
                                    render: (text, record) => <>{record.provider_reference_number}</>
                                },
                            ]}
                        />
                </TabPane>
                <TabPane tab="Voucher stubs" key="2">
                    <div>
                        <Input style={{width: 400}} type="text" placeholder="Search voucher" className="mr-2 my-3" onChange={(e) => searchVoucher(e.target.value)} />
                        <Button onClick={()=>setModalVisible(true)}>New Voucher Stub</Button>
                    </div>

                    <Modal
                        title="New voucher stub"
                        visible={modalVisible}
                        onCancel={()=>setModalVisible(false)}
                        footer={null}
                    >
                        <NewVoucherForm
                            setModalVisible={setModalVisible}
                            type="new"
                            form={newVoucherForm}
                            layout="vertical"
                            // initialValues={{
                            //     name: 'Test',
                            //     code: 'TEST'
                            // }}
                        />
                    </Modal>
                    <Table
                        size="small"
                        // dataSource={voucherListQuery.data ? voucherListQuery.data : []}

                        dataSource={voucherListQuery.data ? 
                            voucherListQuery.data
                            .filter(item => {
                                if (item && searchString) {
                                    const searchValue =  item.name.toLowerCase() + " " + item.code.toLowerCase();
                                    return searchString ? searchValue.indexOf(searchString) !== -1 : true;
                                }
                                return true;
                            })
                            : []}
                                
                        loading={voucherListQuery.isLoading}
                        rowKey="id"
                        scroll={{
                            x: '100vw'
                    }}
                    columns={[
                        // {
                        //     title: 'ID',
                        //     dataIndex: 'id',
                        //     key: 'id',
                        // },
                        {
                            title: 'Voucher',
                            dataIndex: 'name',
                            key: 'name',
                        },
                        {
                            title: 'Image',
                            render: (text, record) => record.images && record.images.length ? <a href={record.images[0].image_path} target="_blank"><img src={record.images[0].image_path} style={{width:40}} /></a> : '-'
                        },
                        {
                            title: 'Code',
                            dataIndex: 'code',
                            key: 'code',
                        },
                        {
                            title: 'Price',
                            dataIndex: 'price',
                            key: 'price',
                            render: (text) => <span className="text-success">&#8369; {text}</span>,
                        },
                        {
                            title: 'Status',
                            dataIndex: 'status',
                            key: 'status',
                            render: (text, record) =>
                                        <Select disabled style={{width: 130}} defaultValue={record.status} onChange={e => console.log(record.id, e)}>
                                            <Select.Option value="published">Published</Select.Option>
                                            <Select.Option value="unpublished">Unpublished</Select.Option>
                                            <Select.Option value="expired">Expired</Select.Option>
                                            <Select.Option value="retired">Retired</Select.Option>
                                            <Select.Option value="ended">Ended</Select.Option>
                                        </Select>
                        },
                        {
                            title: 'Type',
                            dataIndex: 'type',
                            key: 'type',
                            render: (text, record) =>
                                        <Select disabled style={{width: 130}} defaultValue={record.type} onChange={e => console.log(record.id, e)}>
                                            <Select.Option value="per_booking">Per booking</Select.Option>
                                            <Select.Option value="per_guest">Per guest</Select.Option>
                                        </Select>
                        },
                        {
                            title: 'Availability',
                            dataIndex: 'availability',
                            key: 'availability',
                            render: (text, record) =>
                                        <Select disabled style={{width: 130}} defaultValue={record.availability} onChange={e => console.log(record.id, e)}>
                                            <Select.Option value="for_dtt">Daytour</Select.Option>
                                            <Select.Option value="for_overnight">Overnight</Select.Option>
                                        </Select>
                        },
                        {
                            title: 'Action',
                            render: (text, record) =>
                                        <Button icon={<EditOutlined/>} onClick={() => handleEditVoucherStubClick(record) } />
                        },
                        
                    ]}
                />
                </TabPane>
            </Tabs>
        </>
    )
}

export default Page;