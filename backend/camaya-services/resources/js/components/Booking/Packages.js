import React from 'react'
import moment from 'moment-timezone'
import PackageService from 'services/Booking/Package'
import ProductService from 'services/Booking/Product'
import RoomTypeService from 'services/Hotel/RoomType'
import RoleService from 'services/RoleService'
import { queryCache } from 'react-query'

import { Typography, Tag, Row, Col, Card, Drawer, Button, Grid, Form, Input, Select, message, DatePicker, InputNumber, notification, Upload, Modal, Carousel, Space } from 'antd'

const { useBreakpoint } = Grid;

// import db from 'common/db.json'
import img from 'assets/placeholder-1-1.jpg'
import { CheckCircleOutlined, PlusOutlined, ReloadOutlined } from '@ant-design/icons'

moment.tz.setDefault('Asia/Manila');

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
        c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
        }
    }
    return "";
}

function Page(props) {

    const screen = useBreakpoint();

    const initialUploadData = {
        previewVisible: false,
        previewImage: '',
        previewTitle: '',
        fileList: [],
    };

    const [newPackageDrawerVisible, setnewPackageDrawerVisible] = React.useState(false);
    const [viewPackageDrawerVisible, setViewPackageDrawerVisible] = React.useState(false);
    const [disablePackageRoomTypeInclusion, setdisablePackageRoomTypeInclusion] = React.useState(false);
    const [packageFilter, setPackageFilter] = React.useState('');

    const [newPackageQuery, {isLoading: newPackageQueryIsLoading, error: newPackageQueryError}] = PackageService.create();
    const [updatePackageQuery, {isLoading: updatePackageQueryIsLoading, error: updatePackageQueryError}] = PackageService.update();
    const roleListQuery = RoleService.list();
    const packageListQuery = PackageService.list(null, 0);
    const productListQuery = ProductService.list();
    const roomTypeListQuery = RoomTypeService.listPerEntity();

    const [removeImageQuery, {isLoading: removeImageQueryIsLoading, error: removeImageQueryError}] = ProductService.imageUploadRemove();
    const [deleteImage, {isLoading: deleteImageIsLoading, error: deleteImageError}] = PackageService.deleteImage();
    const [uploadedImages, setUploadedImages] = React.useState([]);
    const [uploadData, setUploadData] = React.useState(initialUploadData);

    const [selectedFile, setSelectedFile] = React.useState('');
    const [viewPackageImage, setViewPackageImage] = React.useState('');

    const [newPackageForm] = Form.useForm();
    const [viewPackageForm] = Form.useForm();

    const fileUploadRef = React.useRef();

    React.useEffect( () => {
        // setdisablePackageRoomTypeInclusion(newPackageForm.getFieldValue('availability') == 'for_dtt' ? true:false);
    },[]);

    React.useEffect( () => {
        setUploadedImages([]);
        setUploadData(initialUploadData);
    },[newPackageDrawerVisible]);

    React.useEffect( () => {
        if (viewPackageDrawerVisible) {
            setViewPackageImage(viewPackageForm.getFieldValue('images') && viewPackageForm.getFieldValue('images').length ? viewPackageForm.getFieldValue('images')[0].image_path : '')
        }
    },[viewPackageDrawerVisible])

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

        setUploadData( prev => {
            return {...prev, fileList: [...fileList] }
        });

        if (file.status == 'done') {
            const viewIdSet = viewPackageForm.getFieldValue('id');

            if (viewIdSet) {
                const response = file.response;

                packageListQuery.data.forEach(function (plqdPackage, index) {
                    if (plqdPackage.id === viewIdSet) {
                        const plqdPackageImage = packageListQuery.data[index].images || [];
                        plqdPackageImage.push(response);
                        packageListQuery.data[index].images = plqdPackageImage;
                    }
                });
            } else {
                setUploadedImages( prev => [...prev, file.response.path]);
            }
        }
    };

    const handleRemove = async file => {

        if (file.url) { // file is saved in database
            deleteImage(file.uid, {
                onSuccess: (res) => {
                    console.log(res);
                    const viewIdSet = viewPackageForm.getFieldValue('id');

                    packageListQuery.data.forEach(function (plqdPackage, index) {
                        if (plqdPackage.id === viewIdSet) {
                            const plqdPackageImages = plqdPackage.images;
                            const newPackageImages = _.reject(plqdPackageImages, { id: file.uid });

                            packageListQuery.data[index].images = newPackageImages;
                        }
                    });
                },
                onError: (e) => {
                    console.log(e);
                }
            })            

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

    // Image upload

    const onnewPackageFormFinish = (values) => {
        console.log(values);
        
        // return false;
        const newValues = {
            ...values,
            product_images: uploadedImages,
        }

        if (newPackageQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        newPackageQuery(newValues, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['packages', { id: res.data.id }], res.data);

                packageListQuery.data.push({...res.data});

                newPackageForm.resetFields();

                setnewPackageDrawerVisible(false);

                notification.success({
                    message: `New Package - ${res.data.code} Added!`,
                    description:
                        ``,
                });

                setUploadedImages([]);
                setUploadData(initialUploadData);
            },
        });
    }

    const onViewPackageFormFinish = (values) => {
        console.log(values);

        const newValues = {
            ...values,
            product_images: uploadedImages,
        }

        if (updatePackageQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }
        
        updatePackageQuery(newValues, {
            onSuccess: (res) => {
                console.log(res.data);

                
                // queryCache.setQueryData(['packages'], prev => [...prev.filter(i=>i.id!=res.data.id), res.data]);
                packageListQuery.refetch();


                // packageListQuery.data.forEach(function (plqdPackage, index) {
                //     if (plqdPackage.id === res.data.id) {
                //         packageListQuery.data[index] = res.data;
                //     }
                // });

                viewPackageForm.resetFields();

                setViewPackageDrawerVisible(false);

                notification.success({
                    message: `Package ${res.data.code} Updated!`,
                    description:
                        ``,
                });
            },
        });
    }

    const handleViewPackage = (item) => {
        viewPackageForm.resetFields();

        setUploadData(initialUploadData)
        setUploadedImages([]);

        if (item.selling_start_date) {
            item.selling_start_date = moment(item.selling_start_date);
            item.selling_end_date = moment(item.selling_end_date);
        }

        if (item.booking_start_date) {
            item.booking_start_date = moment(item.booking_start_date);
            item.booking_end_date = moment(item.booking_end_date);
        }        

        if (item.images) {  
            const savedData = [];
            const savedImages = [];

            item.images.forEach(image => {                
                savedData.push({
                    uid: image.id,
                    name: item.name,
                    status: 'done',
                    url: image.image_path
                });

                savedImages.push(image.image_path);

                return image;
            });   
            
            const savedFileList = { ...uploadData, fileList: savedData };

            setUploadData(savedFileList)
            setUploadedImages(savedImages);
        }     
        
        if (item.package_inclusions) {
            //package_inclusions: _.map(item.package_inclusions, 'related_id'),
            const packageInclusion = [];
            
            item.package_inclusions.forEach(product => {
                productListQuery.data.map(field => {
                    if (product.related_id === field.id) {
                        packageInclusion[field.id] = {
                            [field.code]: product.quantity,
                            // quantity: product.quantity,
                        };
                    }

                    return field;
                });         

                return product;
            });

            item.product_inclusions = packageInclusion;
        }
        
        const newItem  = {
            ...item, 
            allowed_roles: _.map(item.allowed_roles, 'role_id'),
            allowed_sources: _.map(item.allowed_sources, 'source'),            
            room_type_inclusions: _.map(item.package_room_type_inclusions, i => i.related_id+"_"+i.entity)[0],
        };

        console.log(newItem, roleListQuery.data, uploadData, uploadedImages);        

        viewPackageForm.setFieldsValue(newItem);
        setViewPackageDrawerVisible(true);
    }

    const onViewPackageDrawerClose = () => {
        viewPackageForm.resetFields();
        setViewPackageDrawerVisible(false);
    }

    const ShowFormItemError = ({name}) => {

        if (newPackageQueryError && newPackageQueryError.errors && newPackageQueryError.errors[name]) {
            return newPackageQueryError.errors[name].map( (item, key) => (
                    <div role="alert" style={{color: '#ff4d4f'}} key={key}>{item}</div>
                ))
        }
        return null;
    }

    const ShowTagItemError = ({name}) => {
        let errors = [];

        if (newPackageQueryError && newPackageQueryError.errors) {
            _.map(newPackageQueryError.errors, (error, key) => {
                if (key.split('.')[0] == name) {
                    errors.push(error);
                }
            });

            return <div role="alert" style={{color: '#ff4d4f'}}>{errors.join(', ')}</div>
        }
        return null;
    }

    const handleUploadImage = () => {
            const formData = new FormData();
    
            formData.append('file', selectedFile);
            formData.append('id', viewPackageForm.getFieldValue('id'));
    
            fetch(
                `${process.env.APP_URL}/api/booking/package/add-image`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                            Authorization: 'Bearer '+localStorage.getItem('token'),
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                }
            )
                .then((response) => response.json())
                .then((result) => {
                    // console.log('Success:', result);
                    message.success('Success uploading of package image');
                    const viewIdSet = viewPackageForm.getFieldValue('id');

                    setSelectedFile('');
                    viewPackageForm.setFieldsValue({ images: [result, ...viewPackageForm.getFieldValue('images')] });

                    setViewPackageImage(result.image_path)
                    fileUploadRef.current.value = ""

                    if (viewIdSet) {

                        packageListQuery.refetch();
                    
                        // const index = packageListQuery.data.findIndex( i => i.id == viewIdSet);

                        // packageListQuery.data[index].images = [...packageListQuery.data[index].images, result]

                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
    };

    const PackageForm = ({type}) => (
        <Row gutter={[12,12]}>
            <Form.Item name="id" noStyle>
                <Input type="hidden" />
            </Form.Item>
            <Col xl={12}>
                <Form.Item
                    name="name"
                    label="Name"
                    extra={<ShowFormItemError name="name" />}
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
                <Form.Item name="code" label="Code" extra={<ShowFormItemError name="code" />} rules={[
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
            <Col xl={24} style={{display:'none'}}>
                <Typography.Text className="mb-2">Package images</Typography.Text>
                    <Upload
                        action={
                            type === 'new' 
                            ? `${process.env.APP_URL}/api/booking/product/image-upload`
                            : `${process.env.APP_URL}/api/booking/package/add-image`
                        }
                        headers={
                            {
                                Authorization: 'Bearer '+localStorage.getItem('token'),
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            }
                        }
                        data={
                            type === 'new'
                            ? null
                            : { id: viewPackageForm.getFieldValue('id') }
                        }
                        listType="picture-card"
                        fileList={uploadData.fileList}
                        onPreview={handlePreview}
                        onChange={handleChange}
                        onRemove={handleRemove}
                        // customRequest={({ file, onSuccess, onError }) => {
                        //     Promise.resolve().then(() => onSuccess((data) => console.log(data)));
                        // }}
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
                    <Select onChange={(e)=>setdisablePackageRoomTypeInclusion(e == 'for_dtt' ? true:false)}>
                        <Select.Option value="for_dtt">For day tour</Select.Option>
                        <Select.Option value="for_overnight">For overnight</Select.Option>
                        <Select.Option value="for_dtt_and_overnight">For day tour and overnight</Select.Option>
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
                <Form.Item name="regular_price" label="Rack Rate"
                    rules={[
                        {
                            required: true
                        }
                    ]}
                >
                    <InputNumber min="1"/>
                </Form.Item>
            </Col>
            {/* <Col xl={8}>
                <Form.Item name="selling_price" label="Selling price"
                    rules={[
                        {
                            required: false
                        }
                    ]}
                >
                    <InputNumber min="1"/>
                </Form.Item>
            </Col> */}
            <Col xl={8}>
                <Form.Item name="weekday_rate" label="Weekday Rate"
                    // rules={[
                    //     {
                    //         required: false
                    //     }
                    // ]}
                >
                    <InputNumber min="1"/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="weekend_rate" label="Weekend Rate"
                    rules={[
                        {
                            required: true
                        }
                    ]}
                >
                    <InputNumber min="1"/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="walkin_price" label="Walk-in price">
                    <InputNumber min="1" placeholder="optional"/>
                </Form.Item>
            </Col>
            {/* <Col xl={8}>
                <Form.Item name="promo_rate" label="Promo Rate"
                    rules={[
                        {
                            required: true
                        }
                    ]}
                >
                    <InputNumber min="1"/>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Form.Item name="holidays" label="Holidays"
                    extra={<ShowTagItemError name="holidays" />}
                >
                    <Select mode="tags" placeholder="format:YYYY-MM-DD" tokenSeparators={[',',';',' ']}/>
                </Form.Item>
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

            <Col xl={24}>
                <Form.Item name="room_type_inclusions" label="Room type inclusions"
                >
                    <Select disabled={disablePackageRoomTypeInclusion}>
                        {
                            roomTypeListQuery.data && roomTypeListQuery.data.map( (item, key) => (
                                <Select.Option key={key} value={`${item.room_type.id}_${item.entity}`}>{item.room_type.property.name} - {item.room_type.name} ({item.entity})</Select.Option>
                            ))
                        }
                    </Select>
                </Form.Item>
            </Col>

            <Col xl={24}>
                {/* <Form.Item name="product_inclusions" label="Product inclusions"> */}
                    {/* <Select mode="multiple"
                        onChange={(e) => console.log(e)}
                        onSelect={e => console.log(e)}
                    >
                        {
                            productListQuery.data && productListQuery.data.map( (item, key) => (
                                <Select.Option key={key} value={item.code}>{item.name}</Select.Option>
                            ))
                        }
                    </Select> */}
                {/* </Form.Item> */}
                <Typography.Text className="mb-2">Product inclusions</Typography.Text>
                <Form.List name="product_inclusions">
                    {fields => (
                        <Row gutter={[8,8]}>
                        {productListQuery.data.map(field => (
                            <Col key={field.id} xl={12} xs={24}>
                            <Card size="small">
                                <div style={{width: '100%', display:'flex', justifyContent:'space-between'}}>
                                    <div><div>{field.name}<br/><strong>{field.code}</strong></div><small className="text-secondary">{field.type}</small></div>
                                    {/* <Form.Item
                                        // {...field}
                                        name={[field.id, 'code']}
                                        fieldKey={[field.id, 'code']}
                                        initialValue={field.code}                 
                                        noStyle
                                    >
                                        <Input type="hidden" readOnly/>
                                    </Form.Item> */}
                                    <Form.Item
                                        // {...field}
                                        name={[field.id, field.code]}
                                        fieldKey={[field.id, field.code]}
                                        noStyle
                                    >
                                        <InputNumber placeholder="qty." min={0} max={field.type == 'per_guest' ? 1 : 10} />
                                    </Form.Item>
                                </div>
                            </Card>
                            </Col>
                        ))}
                        </Row>
                    )}
                </Form.List>
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

            <Col xl={8}>
                <Form.Item name="min_adult" label="Min. adult" rules={[
                        {
                            required: true
                        }
                    ]}>
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="max_adult" label="Max. adult">
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="min_kid" label="Min. kid">
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="max_kid" label="Max. kid">
                    <InputNumber/>
                </Form.Item>
            </Col>

            <Col xl={8}>
                <Form.Item name="min_infant" label="Min. infant">
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="max_infant" label="Max. infant">
                    <InputNumber/>
                </Form.Item>
            </Col>
            
            <Col xl={24}>
                <Form.Item name="allowed_roles" label="Allowed roles">
                    <Select mode="multiple">
                        {
                            roleListQuery.data && roleListQuery.data.map((role, key) => (
                                <Select.Option value={role.id} key={key}>{role.name}</Select.Option>
                            ))
                        }
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Form.Item name="allowed_sources" label="Allowed sources">
                    <Select mode="multiple">
                        <Select.Option value="website">Website</Select.Option>
                        <Select.Option value="walk_in">Walk-in</Select.Option>
                        <Select.Option value="admin">Admin</Select.Option>
                        <Select.Option value="agent_portal">Agent Portal</Select.Option>
                        <Select.Option value="hoa_portal">HOA Portal</Select.Option>
                        <Select.Option value="golf_portal">Golf Portal</Select.Option>
                    </Select>
                </Form.Item>
            </Col>

        </Row>
    );

    return (
        <div className="fadeIn">

            {/* Drawers */}
            <Drawer
                title="New Package"
                width={ screen.xs == false ? 600 : '95%'}
                closable={false}
                visible={newPackageDrawerVisible}
                onClose={()=>setnewPackageDrawerVisible(false)}
                footer={<Button type="primary" disabled={newPackageQueryIsLoading} style={{float:'right'}} onClick={()=>newPackageForm.submit()}>Save</Button>}
            >
                <Form
                    form={newPackageForm}
                    onFinish={onnewPackageFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                    initialValues={{
                        auto_include: false,
                        status: 'unpublished'
                        // name: 'Day tour trip',
                        // code: 'DTT',
                        // type: 'per_guest',
                        // availability: 'for_dtt',
                        // mode_of_transportation: 'own_vehicle',
                        // allowed_days: ['mon'],
                        // regular_price: 499,
                        // selling_price: 499,
                        // walkin_price: 999,
                        // min_adult: 1,
                        // // infant_price: '',
                        // // description: '',
                        // // addon_of: '',
                    }}
                >
                    <PackageForm type="new"/>
                </Form>
            </Drawer>

            <Drawer
                title={`View Package: ${viewPackageDrawerVisible ? viewPackageForm.getFieldValue('code') : ''}`}
                width={ screen.xs == false ? 600 : '95%'}
                closable={false}
                visible={viewPackageDrawerVisible}
                onClose={()=>onViewPackageDrawerClose()}
                footer={<Button type="primary" style={{float:'right'}} onClick={()=>viewPackageForm.submit()}>Update</Button>}
            >
                <div className='p-2 mb-4' style={{border:'solid 1px gainsboro', borderRadius: 6}}>
                    <h3>Upload Package Image</h3>
                    <div>
                        {
                            viewPackageForm.getFieldValue('images')?.length ?
                                <img src={viewPackageImage} style={{width: 180}} />
                                : <>No Image</>
                        }
                    </div>
                    <input type="file" ref={fileUploadRef} onChange={e => setSelectedFile(e.target.files[0])} />
                    <Button onClick={()=> handleUploadImage()}>Upload</Button>
                </div>
                <Form
                    form={viewPackageForm}
                    onFinish={onViewPackageFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                    // initialValues={}
                    // initialValues={{ product_inclusions: productListQuery.data }}
                >
                    <PackageForm  type="view"/>
                </Form>
            </Drawer>

            <Typography.Title level={4}>
                <Space>
                    <>Packages</>
                    <Button type="primary" icon={<ReloadOutlined />} onClick={() => packageListQuery.refetch()} />
                    <Input onChange={(e) => {
                        setPackageFilter(e.target.value);
                    }} placeholder="Search Package" style={{width: 200}} />
                </Space>
            </Typography.Title>

            <Row gutter={[12,12]}>
                {
                    packageListQuery.data && packageListQuery.data
                    .filter((item) => {    
                        if (!packageFilter) return true;
                        const re = new RegExp(packageFilter, 'i')                                       
                        return re.test(item.name) || re.test(item.code) || re.test(item.selling_price) || re.test(item.weekday_rate) || re.test(item.weekend_rate) || re.test(item.promo_rate)
                    })
                    .map( (item, key) => (
                        <Col xl={4} xs={12} key={key}>
                            <Button style={{padding: 0}} type="link" onClick={()=>handleViewPackage(item)}>View { item.status == 'published' && <CheckCircleOutlined color="limegreen" />}</Button>
                            <Card
                                bordered={false}
                                hoverable={true}
                                className="card-shadow"
                                size="small"
                                // cover={<img src={img} style={{width: '100%'}} />}
                                // onClick={()=>handleViewPackage(item)}
                                cover={(item.images && item.images.length) ? 
                                    <Carousel>
                                        {
                                            item.images.map( (image, key) => (
                                                <div key={key}>
                                                    <img className="uploaded-image-preview" src={image.image_path} style={{width: '100%'}} />
                                                </div>
                                            ))
                                        }
                                    </Carousel>
                                :
                                    <img className="uploaded-image-preview" src={img} style={{width: '100%'}} />
                                }
                            >
                                <Card.Meta
                                    title={<><Typography.Title level={5} className="mb-1">{item.name}</Typography.Title><small><Typography.Text type="secondary">{item.code}</Typography.Text></small></>}
                                    description={item.price}
                                />
                            </Card>
                        </Col>
                    ))
                }
                <Col xl={4} xs={12}>
                    <Card
                        bordered={true}
                        // hoverable={true}
                        size="small"
                        onClick={()=>{
                            setnewPackageDrawerVisible(true);
                            newPackageForm.resetFields();
                        }}
                        className="card-add-button"
                    >
                        <Button type="link"><PlusOutlined/> Add Package</Button>
                    </Card>
                </Col>
            </Row>
        </div>
        
    )
}

export default Page;