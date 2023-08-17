import React from 'react'

import { Form, Input, Row, Col, Typography, Button, Checkbox, DatePicker, Select, Card, message, Upload, Modal } from 'antd'
import { UploadOutlined, DeleteOutlined, LockOutlined } from '@ant-design/icons';

import SalesAdminPortalServices from 'services/SalesAdminPortal';

import countries from 'common/countries.json';

function Page(props) {

    // States
    const [withSpouse, setWithSpouse] = React.useState(true);
    // const [withBIS, setWithBIS] = React.useState(false);
    const [internationalAddress, setInternationalAddress] = React.useState(false);
    const [formErrors, setFormErrors] = React.useState({});
    const [sameAsPermanentAddress, setSameAsPermanentAddress] = React.useState(false);
    const [fileList, setFileList] = React.useState([]);
    const [uploading, setUploading] = React.useState(false);
    const [attachmentType, setAttachmentType] = React.useState('');
    const [attachmentTypeModalVisible, setAttachmentTypeModalVisible] = React.useState(false);
    const [fileToUpload, setFileToUpload] = React.useState({});
    const [uploadedFiles, setUploadedFiles] = React.useState([]);
    const [clientAssuanceNA, setClientAssuanceNA] = React.useState(false);
    const [clientPlaceAssuanceOrig, setClientPlaceAssuanceOrig] = React.useState('');
    const [clientPlaceAssuance, setClientPlaceAssuance] = React.useState(false);
    const [clientDateAssuanceOrig, setClientDateAssuanceOrig] = React.useState('');
    const [clientDateAssuance, setClientDateAssuance] = React.useState(false);
    const [clientSpouseAssuanceNA, setClientSpouseAssuanceNA] = React.useState(false);
    const [clientSpousePlaceAssuance, setClientSpousePlaceAssuance] = React.useState(false);
    const [clientSpousePlaceAssuanceOrig, setClientSpousePlaceAssuanceOrig] = React.useState('');
    const [clientSpouseDateAssuance, setClientSpouseDateAssuance] = React.useState(false);
    const [clientSpouseDateAssuanceOrig, setClientSpouseDateAssuanceOrig] = React.useState('');

    // Form
    const [newClientForm] = Form.useForm();

    // POST
    const [addNewClientRecordQuery, { isLoading: addNewClientRecordQueryIsLoading, reset: addNewClientRecordQueryReset}] = SalesAdminPortalServices.addNewClientRecord();
    const salesAgentListQuery = SalesAdminPortalServices.salesAgentList(true);
    const [uploadFileQuery, { isLoading: uploadFileQueryIsLoading, reset: uploadFileQueryReset}] = SalesAdminPortalServices.uploadFile();
    const [removeFileQuery, { isLoading: removeFileQueryIsLoading, reset: removeFileQueryReset}] = SalesAdminPortalServices.removeFile();

    React.useEffect( () => {
        // setWithBIS(props.withBIS);

        newClientForm.setFieldsValue({ with_bis: props.withBIS });

    }, [props.withBIS]);

    React.useEffect( () => {
        // setWithBIS(props.withBIS);

        newClientForm.setFieldsValue({ current_home_address_international: internationalAddress });

    }, [internationalAddress]);

    React.useEffect( () => {
        if (sameAsPermanentAddress) {
            setInternationalAddress(false);

            newClientForm.setFieldsValue({
                ...newClientForm.getFieldsValue(),
                current_home_address_house_number: newClientForm.getFieldValue('permanent_home_address_house_number'),
                current_home_address_street: newClientForm.getFieldValue('permanent_home_address_street'),
                current_home_address_baranggay: newClientForm.getFieldValue('permanent_home_address_baranggay'),
                current_home_address_city: newClientForm.getFieldValue('permanent_home_address_city'),
                current_home_address_province: newClientForm.getFieldValue('permanent_home_address_province'),
                current_home_address_zip_code: newClientForm.getFieldValue('permanent_home_address_zip_code'),
            });
        }
    }, [sameAsPermanentAddress]);

    React.useEffect( () => {
        console.log(fileList)
    }, [fileList]);

    React.useEffect( () => {
        if (!attachmentTypeModalVisible && attachmentType) {
        
            let file = fileToUpload;
            file.attachment_type = attachmentType;

                setFileList( prev => {
                    setAttachmentType('');
                    setFileToUpload({});
                    return [...prev, file];
                });

                if (uploadFileQueryIsLoading) {
                    return false;
                }

                let formData = new FormData();
                formData.append('file', file);
                formData.append('attachment_type', attachmentType);
                
                uploadFileQuery(formData, {
                    onSuccess: (res) => {
                        console.log(res);
                        setUploadedFiles( prev => {
                            return [...prev, res.data];
                        });

                        setFileList([]);
                    },
                    onError: (e) => {
                        console.log(e);
                    }
                })

        }
    }, [attachmentTypeModalVisible]);


    const handleFormFinish = (values) => {
        console.log(values);

        if (addNewClientRecordQueryIsLoading) {
            return false;
        }

        // console.log(newClientForm.validateFields(['email']));

        addNewClientRecordQuery(
                {
                    ...values,
                    current_home_address_international: internationalAddress,
                    attachments: uploadedFiles
                }
            , {
            onSuccess: (res) => {
                // console.log(res)
                newClientForm.resetFields();
                addNewClientRecordQueryReset();

                message.success('New ('+ res.data.client.first_name +' '+ res.data.client.last_name +') client record saved!');

                props.setNewClientModalVisible(false);
            },
            onError: (e) => {
                // console.log(e.error);
                if (e.error == 'EMAIL_EXISTS') {
                    setFormErrors({
                        email: e.message
                    })
                }

                if (e.message == 'Unauthorized.') {
                    message.error("You don't have access to do this action.");
                }
                 // setFormErrors(e.errors);
                addNewClientRecordQueryReset();

            },
        })
    }

    const handleRemoveFileAttachment = (file_path) => {
        if (removeFileQueryIsLoading) {
            return false;
        }

        removeFileQuery({
            file_path: file_path
        }, {
            onSuccess: (res) => {
                message.success("Attachment removed!");

                setUploadedFiles( prev => {
                    let newData = [...prev].filter( i => i.file_path != file_path);

                    return newData;
                });
            },
            onError: (e) => {
                message.warning("Attachment not removed.");
            }
        })
    }

    const onCheck = async () => {
        try {
          const values = await newClientForm.validateFields();
          console.log('Success:', values);
        } catch (errorInfo) {
          setFormErrors({ email: _.find(errorInfo.errorFields, i => i.name == 'email')?.errors })
          console.log('Failed:', errorInfo);
        }
    };

    const uploadProps = {
        onRemove: file => {
          setFileList( prev => {
            const index = prev.indexOf(file);
            const newFileList = prev.slice();
            newFileList.splice(index, 1);

            return newFileList;
          });
        },
        beforeUpload: file => {

            setAttachmentTypeModalVisible(true);
            setFileToUpload(file);
            return false;
        },
        fileList,
    };

    const handlePermanentAddressChange = (value, input) => {
        if (sameAsPermanentAddress) {
            newClientForm.setFieldsValue({
                ...newClientForm.getFieldsValue(),
                ['current_'+input]: value,
            });
        }
    }

    return (
        <>
            <Modal visible={attachmentTypeModalVisible} footer={null} title="Select attachment type">
                <Select style={{width: '100%'}} value={attachmentType} onChange={(e) => {
                      setAttachmentType(e);
                  }}>
                      <Select.Option value="ID">ID</Select.Option>
                      {/* <Select.Option value="Marriage Certificate">Marriage Certificate</Select.Option> */}
                      {/* <Select.Option value="Special Power of Attorney">Special Power of Attorney</Select.Option> */}
                      <Select.Option value="Birth Certificate">Birth Certificate</Select.Option>
                      {/* <Select.Option value="Proof of Income">Proof of Income</Select.Option> */}
                      <Select.Option value="CRF">CRF</Select.Option>
                      <Select.Option value="RIS1">RIS1 (RA)</Select.Option>
                      <Select.Option value="RIS2">RIS2 (BIS)</Select.Option>
                      <Select.Option value="1904">1904</Select.Option>
                      <Select.Option value="POI">POI</Select.Option>
                      <Select.Option value="MC">MC</Select.Option>
                      <Select.Option value="SPA">SPA</Select.Option>

                </Select>

                <Button style={{marginTop: 16}} onClick={() => setAttachmentTypeModalVisible(false)}>Select</Button>
            </Modal>
            <Form
                form={newClientForm}
                layout="vertical"
                onFinish={handleFormFinish}
                initialValues={{
                    with_spouse: true
                }}
            >
                <Row gutter={[32,32]}>
                    <Col xl={ props.withBIS ? 6 : 24}>
                        <Typography.Title level={3}>
                            Client Registration Form
                        </Typography.Title>

                        <Form.Item label="Assigned agent" name="assigned_agent" rules={[
                                {
                                    required: true
                                }]}
                            >
                            <Select 
                                placeholder="Assigned agent"
                                showSearch
                                optionFilterProp="children"
                                filterOption={true}>
                                {
                                    salesAgentListQuery.data && salesAgentListQuery.data
                                        .filter( i => {
                                            if (i.sub_team != null) {
                                                return true;
                                            } else {
                                                return false;
                                            }
                                        })
                                        .map(
                                        (item, key) => {
                                            if( item.id == 0 ) {
                                                return <Select.Option key={key} value={item.id}>{item.first_name}</Select.Option>
                                            } else {
                                                return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name} - {item.email} ({item.sub_team && item.sub_team.team?.name ? item.sub_team.team?.name : ''})</Select.Option>
                                            }
                                            
                                    })
                                    
                                }
                            </Select>
                        </Form.Item>

                        <Form.Item label="First name" name="first_name" rules={[
                                {
                                    required: true
                                }]}
                            >
                            <Input />
                        </Form.Item>

                        <Form.Item label="Middle name" name="middle_name" extra={<small>Optional</small>}>
                            <Input />
                        </Form.Item>

                        <Form.Item label="Last name" name="last_name" rules={[
                                {
                                    required: true
                                }]}
                            >
                            <Input />
                        </Form.Item>

                        <Form.Item label="Extension" name="extension" extra={<small>Optional</small>}>
                            <Input placeholder="Jr. / Sr. / III" />
                        </Form.Item>

                        <Form.Item label="Contact number" name="contact_number"
                            rules={[
                                {
                                    required: true
                                }]}
                            >
                            <Input />
                        </Form.Item>

                        <Form.Item label="Email address" name="email" extra={<small>Client e-mail address should be unique per client.</small>}
                            hasFeedback
                            validateStatus={formErrors.email ? 'error' : ''}
                            help={formErrors.email ? formErrors.email : ''}
                            rules={[
                                {
                                    required: true
                                },
                                {
                                    type: 'email'
                                }
                            ]}
                        >
                            <Input />
                        </Form.Item>

                        <Form.Item name="with_bis">
                            <Checkbox checked={props.withBIS} onClick={()=>props.setNewClientWithBIS(!props.withBIS)} className="mr-2">Register client with BIS</Checkbox>
                        </Form.Item>
                    </Col>
                    { props.withBIS &&
                    <Col xl={ props.withBIS ? 18 : 0}>
                        <Typography.Title level={3}>
                            Buyer's Information Sheet
                        </Typography.Title>
                        <div style={{width: 300}}>
                            {/* Gender, Birth date, Birth place, Citizenship
                            Monthly household income,
                            Government issued identification, Government issued identification date of issuance, Government issued identification place of issuance
                            Tax identification number, occupation, company name, company address

                            Spouse Information:
                            Last name, First name, Middle name, Extension, Gender, Birth date, Birth place, Citizenship
                            Government issued identification, Government issued identification date of issuance, Government issued identification place of issuance
                            Tax identification number, occupation, company name, company address

                            Contact information:
                            Home phone, Business phone, Email address, Mobile number

                            Complete address:
                            Permanent home address, Current home address, Office address, Preferred mailing address,  Preferred mailing address type

                            Signature:
                            Buyer signature,
                            Spouse signature */}
                        </div>
                        <Row gutter={[8, 8]}>
                            <Col xl={8}>
                                <Form.Item label="Gender" name="gender" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Select placeholder="Gender">
                                        <Select.Option value="male">Male</Select.Option>
                                        <Select.Option value="female">Female</Select.Option>
                                    </Select>
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Birth date" name="birth_date" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <DatePicker placeholder="Birth date" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Birth place" name="birth_place" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Input placeholder="Birth place" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Citizenship" name="citizenship" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Monthly household income" name="monthly_household_income" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Select placeholder="">
                                        <Select.Option value="below_50000">Below P50,000</Select.Option>
                                        <Select.Option value="50001_80000">P50,001 - P80,000</Select.Option>
                                        <Select.Option value="80001_120000">P80,001 - P120,000</Select.Option>
                                        <Select.Option value="120001_150000">P120,001 - P150,000</Select.Option>
                                        <Select.Option value="150001_180000">P150,001 - P180,000</Select.Option>
                                        <Select.Option value="above_180000">Above P180,000</Select.Option>
                                    </Select>
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Government issued identification" name="government_issued_identification" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Select placeholder="">
                                        {/* <Select.Option value="passport">Passport</Select.Option> */}
                                        <Select.OptGroup label="Local/Filipino Primary ID">
                                            <Select.Option value="Seaman's Book">Seaman's Book</Select.Option>
                                            <Select.Option value="UMID">UMID</Select.Option>
                                            <Select.Option value="Social Security System">Social Security System</Select.Option>
                                            <Select.Option value="Driver's License">Driver's License</Select.Option>
                                            <Select.Option value="Professional ID">Professional ID</Select.Option>
                                            <Select.Option value="Firearm's">Firearm's</Select.Option>
                                            <Select.Option value="Integrated Bar of the Phils.">Integrated Bar of the Phils.</Select.Option>
                                            <Select.Option value="Postal ID">Postal ID</Select.Option>
                                            <Select.Option value="Voter's ID">Voter's ID</Select.Option>
                                        </Select.OptGroup>
                                        <Select.OptGroup label="Local/Filipino Secondary ID">
                                            <Select.Option value="Senior ID">Senior ID</Select.Option>
                                            <Select.Option value="Police Clearance">Police Clearance</Select.Option>
                                            <Select.Option value="Philhealth ID">Philhealth ID</Select.Option>
                                            <Select.Option value="TIN ID">TIN ID</Select.Option>
                                            <Select.Option value="Brgy. Resident ID">Brgy. Resident ID</Select.Option>
                                        </Select.OptGroup>

                                        <Select.OptGroup label="Foreigner Primary ID">
                                            <Select.Option value="Passport">Passport</Select.Option>
                                            <Select.Option value="Driver's License">Driver's License</Select.Option>
                                        </Select.OptGroup>

                                        <Select.OptGroup label="Foreigner Secondary ID">
                                                <Select.Option value="Resident ID">Resident ID</Select.Option>
                                                <Select.Option value="VISA ID">VISA ID</Select.Option>
                                                <Select.Option value="Permanent Resident ID/Card">Permanent Resident ID/Card</Select.Option>
                                                <Select.Option value="Health Card">Health Card</Select.Option>
                                        </Select.OptGroup>
                                    </Select>
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Place of issuance" name="id_issuance_place">
                                    <Input disabled={clientPlaceAssuance} placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Row gutter={[8,8]}>
                                    <Col xl={12}>
                                        <Form.Item label="Date of issuance" name="id_issuance_date">
                                            <DatePicker allowClear placeholder="" disabled={clientDateAssuance} />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={12}>
                                        <Form.Item label=" " name="is_available_issuance_details">
                                            <Checkbox checked={clientAssuanceNA} onChange={(e) => { 
                                                let is_checked = e.target.checked;
                                                setClientAssuanceNA(is_checked); 
                                                setClientPlaceAssuance(is_checked);
                                                setClientDateAssuance(is_checked);

                                                if( is_checked ) {
                                                    newClientForm.setFieldsValue({
                                                        id_issuance_place: null,
                                                        id_issuance_date: null
                                                    });
                                                } else {
                                                    newClientForm.setFieldsValue({
                                                        id_issuance_place: clientPlaceAssuanceOrig,
                                                        id_issuance_date: clientDateAssuanceOrig
                                                    });
                                                }
                                            }}>Not Applicable</Checkbox>
                                        </Form.Item>
                                    </Col>
                                </Row>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Tax identification number" name="tax_identification_number" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Occupation" name="occupation" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Company name" name="company_name" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Company address" name="company_address" rules={[{
                                    required: props.withBIS
                                }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>

                            {/* Spouse */}

                            <Col xl={24}>
                                <Form.Item valuePropName="checked" defaultChecked={withSpouse} name="with_spouse">
                                    <Checkbox defaultChecked={withSpouse} onChange={(e) => setWithSpouse(e.target.checked)}> WITH SPOUSE</Checkbox>
                                </Form.Item>
                            </Col>

                            { withSpouse == true &&
                            <>
                            <Col xl={24}>
                                <Typography.Title level={5}>Spouse information</Typography.Title>
                            </Col>

                            <Col xl={24}>
                                <Card size="small">
                                <Row gutter={[8,8]}>
                                    <Col xl={8}>
                                        <Form.Item label="First name" name="spouse_first_name" rules={[
                                            {
                                                required: withSpouse
                                            }]}
                                        >
                                            <Input />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Middle name" name="spouse_middle_name" extra={<small>Optional</small>}>
                                            <Input />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Last name" name="spouse_last_name" rules={[
                                                {
                                                    required: withSpouse
                                                }]}
                                            >
                                            <Input />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Extension" name="spouse_extension" extra={<small>Optional</small>}>
                                            <Input placeholder="Jr. / Sr. / III" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Gender" name="spouse_gender" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Select placeholder="Gender">
                                                <Select.Option value="male">Male</Select.Option>
                                                <Select.Option value="female">Female</Select.Option>
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Birth date" name="spouse_birth_date" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <DatePicker placeholder="Birth date" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Birth place" name="spouse_birth_place" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Input placeholder="Birth place" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Citizenship" name="spouse_citizenship" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Input placeholder="" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Government issued identification" name="spouse_government_issued_identification" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Select placeholder="">
                                                <Select.OptGroup label="Local/Filipino Primary ID">
                                                    <Select.Option value="Seaman's Book">Seaman's Book</Select.Option>
                                                    <Select.Option value="UMID">UMID</Select.Option>
                                                    <Select.Option value="Social Security System">Social Security System</Select.Option>
                                                    <Select.Option value="Driver's License">Driver's License</Select.Option>
                                                    <Select.Option value="Professional ID">Professional ID</Select.Option>
                                                    <Select.Option value="Firearm's">Firearm's</Select.Option>
                                                    <Select.Option value="Integrated Bar of the Phils.">Integrated Bar of the Phils.</Select.Option>
                                                    <Select.Option value="Postal ID">Postal ID</Select.Option>
                                                    <Select.Option value="Voter's ID">Voter's ID</Select.Option>
                                                </Select.OptGroup>
                                                <Select.OptGroup label="Local/Filipino Secondary ID">
                                                    <Select.Option value="Senior ID">Senior ID</Select.Option>
                                                    <Select.Option value="Police Clearance">Police Clearance</Select.Option>
                                                    <Select.Option value="Philhealth ID">Philhealth ID</Select.Option>
                                                    <Select.Option value="TIN ID">TIN ID</Select.Option>
                                                    <Select.Option value="Brgy. Resident ID">Brgy. Resident ID</Select.Option>
                                                </Select.OptGroup>

                                                <Select.OptGroup label="Foreigner Primary ID">
                                                    <Select.Option value="Passport">Passport</Select.Option>
                                                    <Select.Option value="Driver's License">Driver's License</Select.Option>
                                                </Select.OptGroup>

                                                <Select.OptGroup label="Foreigner Secondary ID">
                                                        <Select.Option value="Resident ID">Resident ID</Select.Option>
                                                        <Select.Option value="VISA ID">VISA ID</Select.Option>
                                                        <Select.Option value="Permanent Resident ID/Card">Permanent Resident ID/Card</Select.Option>
                                                        <Select.Option value="Health Card">Health Card</Select.Option>
                                                </Select.OptGroup>
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Place of issuance" name="spouse_id_issuance_place">
                                            <Input disabled={clientSpousePlaceAssuance} placeholder="" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Row gutter={[8,8]}>
                                            <Col xl={12}>
                                                <Form.Item label="Date of issuance" name="spouse_id_issuance_date">
                                                    <DatePicker disabled={clientSpouseDateAssuance} allowClear placeholder="" />
                                                </Form.Item>
                                            </Col>
                                            <Form.Item label=" " name="is_available_spouse_issuance_details">
                                                <Checkbox checked={clientSpouseAssuanceNA} onChange={(e) => { 
                                                    let is_checked = e.target.checked;
                                                    setClientSpouseAssuanceNA(is_checked); 
                                                    setClientSpousePlaceAssuance(is_checked);
                                                    setClientSpouseDateAssuance(is_checked);

                                                    if( is_checked ) {
                                                        newClientForm.setFieldsValue({
                                                            spouse_id_issuance_place: null,
                                                            spouse_id_issuance_date: null
                                                        });
                                                    } else {
                                                        newClientForm.setFieldsValue({
                                                            spouse_id_issuance_place: clientSpousePlaceAssuanceOrig,
                                                            spouse_id_issuance_date: clientSpouseDateAssuanceOrig
                                                        });
                                                    }
                                                }}>Not Applicable</Checkbox>
                                            </Form.Item> 
                                        </Row>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Tax identification number" name="spouse_tax_identification_number" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Input placeholder="" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Occupation" name="spouse_occupation" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Input placeholder="" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Company name" name="spouse_company_name" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Input placeholder="" />
                                        </Form.Item>
                                    </Col>
                                    <Col xl={8}>
                                        <Form.Item label="Company address" name="spouse_company_address" rules={[{
                                            required: withSpouse
                                        }]}>
                                            <Input placeholder="" />
                                        </Form.Item>
                                    </Col>
                                </Row>
                                </Card>
                            </Col>

                            </>
                            }
                            <Col xl={24}>
                                <Typography.Title level={5}>Contact information</Typography.Title>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="Home phone" name="home_phone" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Business phone" name="business_phone" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Email address" name="email">
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Mobile number" name="mobile_number" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={24}>
                                <Typography.Title level={4}>Complete address</Typography.Title>
                            </Col>

                            {/* Address */}

                            <Col xl={24}>
                                <Typography.Title level={5}>Permanent home address</Typography.Title>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="House no." name="permanent_home_address_house_number" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_house_number")} placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="Street" name="permanent_home_address_street" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_street")} placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="Baranggay" name="permanent_home_address_baranggay" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_baranggay")} placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="City" name="permanent_home_address_city" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_city")} placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="Province" name="permanent_home_address_province" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_province")} placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={8}>
                                <Form.Item label="Zip code" name="permanent_home_address_zip_code" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input onChange={(e) => handlePermanentAddressChange(e.target.value, "home_address_zip_code")} placeholder="" />
                                </Form.Item>
                            </Col>

                            <Col xl={24}>
                                <Typography.Title level={5}>Current home address</Typography.Title>
                                <Checkbox onClick={()=>setSameAsPermanentAddress(!sameAsPermanentAddress)} className="mr-2">Same as permanent address</Checkbox>
                                <Form.Item name="current_home_address_international">
                                    <Checkbox checked={internationalAddress} disabled={sameAsPermanentAddress} onClick={()=>setInternationalAddress(!internationalAddress)} className="mr-2">International address</Checkbox>
                                </Form.Item>
                            </Col>

                            { !internationalAddress &&
                            <Col xl={8}>
                                <Form.Item label="House no." name="current_home_address_house_number" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                                </Form.Item>
                            </Col>
                            }

                            <Col xl={8}>
                                <Form.Item label="Street address" name="current_home_address_street" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                                </Form.Item>
                            </Col>
                            
                            { !internationalAddress &&
                            <Col xl={8}>
                                <Form.Item label="Baranggay" name="current_home_address_baranggay" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                                </Form.Item>
                            </Col>
                            }

                            <Col xl={8}>
                                <Form.Item label="City" name="current_home_address_city" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input readOnly={sameAsPermanentAddress} placeholder="" />
                                </Form.Item>
                            </Col>

                            { !internationalAddress &&
                            <Col xl={8}>
                                <Form.Item label="Province" name="current_home_address_province" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                                </Form.Item>
                            </Col>
                            }

                            <Col xl={8}>
                                <Form.Item label={`${internationalAddress ? 'State' : ''} Zip code`} name="current_home_address_zip_code" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Input prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress} placeholder="" />
                                </Form.Item>
                            </Col>

                            { internationalAddress &&
                                <Col xl={8}>
                                    <Form.Item label={`Country`} name="current_home_address_country" rules={[{
                                                required: props.withBIS
                                    }]}>
                                        <Select prefix={sameAsPermanentAddress ? <LockOutlined className="site-form-item-icon" /> : ''} readOnly={sameAsPermanentAddress}>
                                            {
                                                countries.list.map( (i, key) => {
                                                    return <Select.Option key={key} value={i}>{i}</Select.Option>
                                                })
                                            }
                                        </Select>
                                    </Form.Item>
                                </Col>
                            }

                            <Col xl={8}>
                                <Form.Item label="Preferred mailing address" name="preferred_mailing_address" rules={[{
                                            required: props.withBIS
                                        }]}>
                                    <Select placeholder="">
                                        <Select.Option value="permanent_home_address">Permanent home address</Select.Option>
                                        <Select.Option value="current_home_address">Current home address</Select.Option>
                                    </Select>
                                </Form.Item>
                            </Col>
                            {/* <Col xl={24}>
                                <Typography.Title level={5}>Signature</Typography.Title>
                            </Col> */}

                            <Col xl={24}>
                                <Typography.Title level={5}>Attachments</Typography.Title>
                                {/* <Form.Item name="attachments"> */}
                                    <Upload {...uploadProps}
                                        name="files"
                                        itemRender={(originNode, file, currFileList) => (
                                            <Card style={{marginTop: 8}}>{originNode} Attachment type: <strong>{file.attachment_type}</strong></Card>
                                        )}
                                    >
                                        <Button icon={<UploadOutlined />}>Select File</Button>
                                    </Upload>
                                {/* </Form.Item> */}

                                {
                                    uploadedFiles && uploadedFiles.map( (item, key) => {
                                        return <Card key={key} style={{marginTop: 8,}}>
                                                <strong>{item.attachment_type}</strong>: {item.file_name}
                                                <Button style={{float:'right'}} icon={<DeleteOutlined/>} onClick={() => handleRemoveFileAttachment(item.file_path)} />
                                            </Card>
                                    })
                                }
                            </Col>
                        </Row>
                    </Col>
                   }
                </Row>


                <Button htmlType="submit" onClick={onCheck}>Save Client Record</Button>
            </Form>
        </>
    )
}

export default Page;