import React from 'react'
import ProductService from 'services/Booking/Product'
import RoleService from 'services/RoleService'
import StubService from 'services/Booking/StubService'
import { queryCache } from 'react-query'
import moment from 'moment-timezone'
// import ImageUpload from 'components/Booking/ImageUpload'

import { Typography, Row, Col, Card, Drawer, Button, Grid, Form, Input, Select, Switch, TimePicker, InputNumber, notification, message, Upload, Modal, Carousel, Space } from 'antd'

const { useBreakpoint } = Grid;

// import db from 'common/db.json'
import img from 'assets/placeholder-1-1.jpg'
import { PlusOutlined, ReloadOutlined } from '@ant-design/icons'

moment.tz.setDefault('Asia/Manila');

const getBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => resolve(reader.result);
      reader.onerror = error => reject(error);
    });
}

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

    const [newProductDrawerVisible, setNewProductDrawerVisible] = React.useState(false);
    const [viewProductDrawerVisible, setViewProductDrawerVisible] = React.useState(false);
    const [productFilter, setProductFilter] = React.useState('');

    const [newProductQuery, {isLoading: newProductQueryIsLoading, error: newProductQueryError}] = ProductService.create();
    const [updateProductQuery, {isLoading: updateProductQueryIsLoading, error: updateProductQueryError}] = ProductService.update();
    const roleListQuery = RoleService.list();
    const productListQuery = ProductService.list();
    const stubListQuery = StubService.list();

    const [removeImageQuery, {isLoading: removeImageQueryIsLoading, error: removeImageQueryError}] = ProductService.imageUploadRemove();
    const [deleteImage, {isLoading: deleteImageIsLoading, error: deleteImageError}] = ProductService.deleteImage();
    const [uploadedImages, setUploadedImages] = React.useState([]);
    const [uploadData, setUploadData] = React.useState(initialUploadData);

    const [newProductForm] = Form.useForm();
    const [viewProductForm] = Form.useForm();

    // React.useEffect( () => {
    //     console.log(productImagesToUpload);
    // },[productImagesToUpload]);

    React.useEffect( () => {
        console.log('running');
    },[]);

    // Functions Image
    const handleCancel = () => { 
        setUploadData( prev => ({...prev, previewVisible: false}) );
    };

    const handlePreview = async file => {
        if (!file.url && !file.preview) {
          file.preview = await getBase64(file.originFileObj);
        }

        if (!file.preview) {
            console.log(file);

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
            const viewIdSet = viewProductForm.getFieldValue('id');

            if (viewIdSet) {
                const response = file.response;

                productListQuery.data.forEach(function (plqdProduct, index) {
                    if (plqdProduct.id === viewIdSet) {
                        const plqdProductImage = productListQuery.data[index].images || [];
                        plqdProductImage.push(response);
                        productListQuery.data[index].images = plqdProductImage;
                    }
                });
            } else {
                setUploadedImages( prev => [...prev, file.response.path]);
            }
        }
    };

    const handleRemove = async file => {

        if (file.url) { // file is saved in database
            console.log(file);

            deleteImage(file.uid, {
                onSuccess: (res) => {
                    console.log(res);
                    const viewIdSet = viewProductForm.getFieldValue('id');

                    productListQuery.data.forEach(function (plqdProduct, index) {
                        if (plqdProduct.id === viewIdSet) {
                            const plqdProductImages = plqdProduct.images;
                            const newProductImages = _.reject(plqdProductImages, { id: file.uid });

                            productListQuery.data[index].images = newProductImages;
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

    const onNewProductFormFinish = (values) => {
        console.log(values);        

        const newValues = {
            ...values,
            product_images: uploadedImages,
        }
        
        if (newProductQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        newProductQuery(newValues, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['products', { id: res.data.id }], res.data);

                productListQuery.data.push({...res.data});

                newProductForm.resetFields();

                setNewProductDrawerVisible(false);

                notification.success({
                    message: `New Product - ${res.data.code} Added!`,
                    description:
                        ``,
                });

                setUploadedImages([]);
                setUploadData(initialUploadData);
            },
            onError: (e) => {
                message.warning(e.errors && e.errors.code);
            }
        });
    }

    const onViewProductFormFinish = (values) => {
        console.log(values);

        const newValues = {
            ...values,
            // product_images: uploadedImages,
        }

        if (updateProductQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        updateProductQuery(newValues, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['products', { id: res.data.id }], res.data);

                productListQuery.data.forEach(function (plqdProduct, index) {
                    if (plqdProduct.id === res.data.id) {
                        productListQuery.data[index] = res.data;
                    }
                });

                viewProductForm.resetFields();

                setViewProductDrawerVisible(false);

                notification.success({
                    message: `Product ${res.data.code} Updated!`,
                    description:
                        ``,
                });
            },
        });
    }

    const handleViewProduct = (item) => {
        console.log(item);

        if (item.serving_time) {
            item.serving_time[0] = moment(item.serving_time[0]);
            item.serving_time[1] = moment(item.serving_time[1]);
        }

        if (item.allowed_days == null) {
            item.allowed_days = [];
        }

        // uploadData.fileList = [];
        setUploadData(initialUploadData)
        setUploadedImages([]);       

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

        viewProductForm.resetFields();

        const newItem  = {
            ...item, 
            allowed_roles: _.map(item.allowed_roles, 'role_id'),
            allowed_sources: _.map(item.allowed_sources, 'source'),
            product_pass: _.map(item.product_pass, 'stub_id'),
        };

        console.log(newItem, roleListQuery.data, uploadData, uploadedImages);        

        viewProductForm.setFieldsValue(newItem);
        setViewProductDrawerVisible(true);        
    }

    const onViewProductDrawerClose = () => {
        viewProductForm.resetFields();
        setViewProductDrawerVisible(false);
    }

    const ShowFormItemError = ({name}) => {
        if (newProductQueryError && newProductQueryError.errors && newProductQueryError.errors[name]) {
            return newProductQueryError.errors[name].map( (item, key) => (
                    <div role="alert" style={{color: '#ff4d4f'}} key={key}>{item}</div>
                ))
        }

        return <></>
    }

    const ProductForm = ({type}) => (
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
            <Col xl={24}>
                <Typography.Text className="mb-2">Product images</Typography.Text>
                    <Upload
                        action={
                            type === 'new' 
                            ? `${process.env.APP_URL}/api/booking/product/image-upload`
                            : `${process.env.APP_URL}/api/booking/product/add-image`
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
                            : { id: viewProductForm.getFieldValue('id') }
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
                <Form.Item name="category" label="Category"
                rules={[
                    {
                        required: false
                    }
                ]}>
                    <Select>
                        <Select.Option value="">None</Select.Option>
                        <Select.Option value="golf">Golf</Select.Option>
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
                        <Select.Option value="for_dtt_and_overnight">For day tour and overnight</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="price" label="Price"
                rules={[
                    {
                        required: true
                    }
                ]}>
                    <Input/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="walkin_price" label="Walk-in price">
                    <Input placeholder="optional"/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="kid_price" label="Kid price">
                    <Input placeholder="optional"/>
                </Form.Item>
            </Col>
            <Col xl={8}>
                <Form.Item name="infant_price" label="Infant price">
                    <Input placeholder="optional"/>
                </Form.Item>
            </Col>
            <Col xl={24}>
                Product Price Ranges
            </Col>
            <Col xl={12}>
                <Form.Item name="serving_time" label="Serving time">
                    <TimePicker.RangePicker format="hh:mm a" minuteStep={5} use12Hours={true} />
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="allowed_days" label="Allowed days"
                rules={[
                    {
                        required: false
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
                <Form.Item name="quantity_per_day" label="Quantity per day">
                    <InputNumber/>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Form.Item name="description" label="Description">
                    <Input.TextArea style={{borderRadius:'12px'}}/>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="auto_include" label="Auto-include to booking" valuePropName="checked">
                    <Switch/>
                </Form.Item>
            </Col>

            <Col xl={12}>
                <Form.Item name="status" label="Status">
                    <Select>
                        <Select.Option value="unpublished">Unpublished</Select.Option>
                        <Select.Option value="published">Published</Select.Option>
                        <Select.Option value="retired">Retired</Select.Option>
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={12}>
                <Form.Item name="addon_of" label="Addon of">
                    <Select>
                        <Select.Option value="">None</Select.Option>
                        {
                            productListQuery.data && productListQuery.data.map( (item, key) => (
                                <Select.Option key={key} value={item.code}>{item.name}</Select.Option>
                            ))
                        }
                    </Select>
                </Form.Item>
            </Col>
            <Col xl={24}>
                <Form.Item name="allowed_roles" label="Allowed roles">
                    <Select mode="multiple">
                        {
                            roleListQuery.data && roleListQuery.data.map((role, key) => (
                                <Select.Option value={role.id} key={role.id}>{role.name}</Select.Option>
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

            <Col xl={24}>
                <Form.Item name="product_pass" label="Pass stubs">
                    <Select mode="multiple">
                        {
                            stubListQuery.data.map( (item, key) => {
                                return (
                                    <Select.Option key={key} value={item.id}>{item.type}</Select.Option>
                                )
                            })
                        }
                    </Select>
                </Form.Item>
            </Col>
        </Row>
    );

    const contentStyle = {
        height: '160px',
        color: '#fff',
        lineHeight: '160px',
        textAlign: 'center',
        background: '#364d79',
      };

    return (
        <div className="fadeIn">

            {/* Drawers */}
            <Drawer
                title="New Product"
                width={ screen.xs == false ? 520 : '95%'}
                closable={false}
                visible={newProductDrawerVisible}
                onClose={()=>setNewProductDrawerVisible(false)}
                footer={<Button type="primary" style={{float:'right'}} onClick={()=>newProductForm.submit()}>Save</Button>}
            >
                <Form
                    form={newProductForm}
                    onFinish={onNewProductFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                    initialValues={{
                        auto_include: false,
                        status: 'unpublished',
                        allowed_days: [],
                        // name: 'Day tour trip',
                        // code: 'DTT',
                        // type: 'per_guest',
                        // availability: 'for_dtt',
                        // // serving_time: '',
                        // // quantity_per_day: '',
                        // price: 499,
                        // walkin_price: 999,
                        // // kid_price: '',
                        // // infant_price: '',
                        // // description: '',
                        // // addon_of: '',
                    }}
                >
                    <ProductForm type="new"/>
                </Form>
            </Drawer>

            <Drawer
                title="View Product"
                width={ screen.xs == false ? 520 : '95%'}
                closable={false}
                visible={viewProductDrawerVisible}
                onClose={()=>onViewProductDrawerClose()}
                footer={<Button type="primary" style={{float:'right'}} onClick={()=>viewProductForm.submit()}>Update</Button>}
            >
                <Form
                    form={viewProductForm}
                    onFinish={onViewProductFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                >
                    <ProductForm type="view"/>
                </Form>
            </Drawer>

            <Typography.Title level={4}>
                <Space>                           
                    <>Products</>    
                    <Button type="primary" icon={<ReloadOutlined />} onClick={() => productListQuery.refetch()} />                            
                    <Input onChange={(e) => {
                        setProductFilter(e.target.value);
                    }} placeholder="Search Products" style={{width: 200}} />
                </Space> 
            </Typography.Title>

            <Row gutter={[12,12]}>
                {
                    productListQuery.data && productListQuery.data
                    .filter((item) => {    
                        if (!productFilter) return true;
                        const re = new RegExp(productFilter, 'i')                                       
                        return re.test(item.name) || re.test(item.code) || re.test(item.price)
                    })
                    .map( (item, key) => (
                        <Col xl={4} xs={12} key={key}>
                            <Button style={{padding: 0}} type="link" onClick={()=>handleViewProduct(item)}>View</Button>
                            <Card
                                bordered={false}
                                hoverable={true}
                                className="card-shadow"
                                size="small"
                                // cover={<img src={img} style={{width: '100%'}} />}
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
                                        <img src={img} className="uploaded-image-preview" style={{width: '100%'}} />
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
                        onClick={()=>setNewProductDrawerVisible(true)}
                        className="card-add-button"
                    >
                        <Button type="link"><PlusOutlined/> Add Product</Button>
                    </Card>
                </Col>
            </Row>
        </div>
        
    )
}

export default Page;