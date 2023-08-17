import React from 'react'
import img from 'assets/placeholder-1-1.jpg'
import TransportationLocationService from 'services/Transportation/Location'
import { queryCache } from 'react-query'
import mapboxgl from 'mapbox-gl'

mapboxgl.accessToken = 'pk.eyJ1IjoiY2FtYXlhLWtpdC1zZW5vIiwiYSI6ImNrZjByN2E1ejBvNDkycmxlbWl2MGE0bDcifQ.bZ_B0_7Gg1-2ZfL5DmUHQw';

import { Typography, Row, Col, Card, Button, Grid, Form, Drawer, Input, Select, InputNumber, notification } from 'antd'
import { PlusOutlined } from '@ant-design/icons'

const { useBreakpoint } = Grid;

function Page(props) {

    const screen = useBreakpoint();

    const defaultCoordinates = {
        lng: 120.5349,
        lat: 14.6272,
        zoom: 9.00
    };

    const [mapDefaultCoordinates, setmapDefaultCoordinates] = React.useState(defaultCoordinates);

    const [newLocationQuery, {isLoading: newLocationQueryIsLoading, error: newLocationQueryError}] = TransportationLocationService.create();
    const locationListQuery = TransportationLocationService.list();

    const [newLocationDrawerVisible, setnewLocationDrawerVisible] = React.useState(false);
    const [viewTransportationDrawerVisible, setviewTransportationDrawerVisible] = React.useState(false);

    const [map, setMap] = React.useState(null);
    const [markerPin, setMarkerPin] = React.useState(new mapboxgl.Marker());
    const [hoveredLocation, sethoveredLocation] = React.useState(null);

    const [newLocationForm] = Form.useForm();
    const [viewTransportationForm] = Form.useForm();

    let mapContainerRef = React.useRef();

    // const locationListQuery = {
    //     data: [
    //         { name: 'Camaya Coast', code: 'CMY', longitude: 120.422111, latitude: 14.453329 },
    //         { name: 'Esplanade Seaside Terminal', code: 'EST', longitude: 120.980439, latitude: 14.541648 },
    //         { name: 'Test3', code: 'TEST3', longitude: 0, latitude: 0 },
    //         { name: 'Camaya Coast', code: 'CMY', longitude: 120.422111, latitude: 14.453329 },
    //         { name: 'Esplanade Seaside Terminal', code: 'EST', longitude: 120.980439, latitude: 14.541648 },
    //         { name: 'Test3', code: 'TEST3', longitude: 0, latitude: 0 },
    //         { name: 'Camaya Coast', code: 'CMY', longitude: 120.422111, latitude: 14.453329 },
    //         { name: 'Esplanade Seaside Terminal', code: 'EST', longitude: 120.980439, latitude: 14.541648 },
    //         { name: 'Test3', code: 'TEST3', longitude: 0, latitude: 0 },
    //     ]
    // };

    React.useEffect( () => {

        setMap(new mapboxgl.Map({
            container: mapContainerRef,
            style: 'mapbox://styles/mapbox/streets-v11',
            // style: 'mapbox://styles/mapbox/satellite-streets-v11',
            center: [mapDefaultCoordinates.lng, mapDefaultCoordinates.lat],
            zoom: mapDefaultCoordinates.zoom,
            width: 400,
            height: 400,
        }));

        function handleResize() {
            if (markerPin) markerPin.setOffset([document.querySelector(".mapboxgl-canvas").clientWidth / 2, -document.querySelector(".mapboxgl-canvas").clientHeight])
        }
                    
        window.addEventListener('resize', handleResize);

        return ( () => {
            window.removeEventListener('resize', handleResize);
        })

    }, []);

    React.useEffect( () => {
        if (map) {
            map.on('move', () => {
                setmapDefaultCoordinates( prev => {

                    // console.log(map.getCenter().lng.toFixed(4), map.getCenter().lat.toFixed(4), map.getZoom().toFixed(2));
                    return {
                        lng: map.getCenter().lng.toFixed(6),
                        lat: map.getCenter().lat.toFixed(6),
                        zoom: map.getZoom().toFixed(2)
                    }
                });
            });
        }
    }, [map]);

    const onLocationHover = (location, action) => {
        // console.log(mapContainerRef.clientWidth, mapContainerRef.clientHeight);
        sethoveredLocation(location);

        if (action == 'over') {
                markerPin.setOffset([document.querySelector(".mapboxgl-canvas").clientWidth / 2, -document.querySelector(".mapboxgl-canvas").clientHeight])
                    .setLngLat([location.longitude,location.latitude])
                    .addTo(map);

                map.flyTo({
                    center: [location.longitude,location.latitude],
                    zoom: 9,
                    // pitch: 45,
                    // bearing: 90
                });
        } else {
            if (markerPin) {
                markerPin.remove();
                sethoveredLocation(null);

                map.flyTo({
                    center: [defaultCoordinates.lng,defaultCoordinates.lat],
                    zoom: defaultCoordinates.zoom,
                    // pitch: 45,
                    // bearing: 90
                });
            }
        }
    }

    const onnewLocationFormFinish = values => {
        console.log(values);

        newLocationQuery(values, {
            onSuccess: (res) => {
                console.log(res.data);

                queryCache.setQueryData(['locations', { id: res.data.id }], res.data);

                locationListQuery.data.push({...res.data});

                newLocationForm.resetFields();

                setnewLocationDrawerVisible(false);

                notification.success({
                    message: `New Location - ${res.data.code} Added!`,
                    description:
                        ``,
                });
            },
        });
    }

    const onViewTransportationFormFinish = values => {
        console.log(values);
    }

    const ShowFormItemError = ({name}) => {
        if (newLocationQueryError && newLocationQueryError.errors && newLocationQueryError.errors[name]) {
            return newLocationQueryError.errors[name].map( (item, key) => (
                    <div role="alert" style={{color: '#ff4d4f'}} key={key}>{item}</div>
                ))
        }

        return <></>
    }

    const TransportationForm = () => {
        return (
            <>
                <Row gutter={[12,12]}>
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
                        <Form.Item name="code" label="Code"
                            extra={<ShowFormItemError name="code" />}
                            rules={[
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
                        <Form.Item
                            name="description"
                            label="Description"
                            // extra={<ShowFormItemError name="name" />}
                        >
                            <Input.TextArea style={{borderRadius: 12}}/>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item
                            name="location"
                            label="Location"
                            // extra={<ShowFormItemError name="name" />}
                        >
                            <Input/>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item
                            name="longitude"
                            label="Longitude"
                            // extra={<ShowFormItemError name="name" />}
                        >
                            <InputNumber/>
                        </Form.Item>
                    </Col>

                    <Col xl={8}>
                        <Form.Item
                            name="latitude"
                            label="Latitude"
                            // extra={<ShowFormItemError name="name" />}
                        >
                            <InputNumber/>
                        </Form.Item>
                    </Col>
                    
                </Row>
            </>
        )
    }

    const handleViewTransportation = (item) => {
        viewTransportationForm.resetFields();
        viewTransportationForm.setFieldsValue(item);
        setviewTransportationDrawerVisible(true);
    }

    const onViewTransportationDrawerClose = () => {
        viewTransportationForm.resetFields();
        setviewTransportationDrawerVisible(false);
    }


    return (
        <>
            <Drawer
                    title="New Transportation"
                    width={ screen.xs == false ? 520 : '95%'}
                    closable={false}
                    visible={newLocationDrawerVisible}
                    onClose={()=>setnewLocationDrawerVisible(false)}
                    footer={<Button type="primary" style={{float:'right'}} onClick={()=>newLocationForm.submit()}>Save</Button>}
                >
                    <Form
                        form={newLocationForm}
                        onFinish={onnewLocationFormFinish}
                        layout="vertical"
                        scrollToFirstError={true}
                        // initialValues={}
                    >
                        <TransportationForm/>
                    </Form>
            </Drawer>

            <Drawer
                    title="View Transportation"
                    width={ screen.xs == false ? 520 : '95%'}
                    closable={false}
                    visible={viewTransportationDrawerVisible}
                    onClose={()=>onViewTransportationDrawerClose()}
                    footer={<Button type="primary" style={{float:'right'}} onClick={()=>viewTransportationForm.submit()}>Save</Button>}
                >
                    <Form
                        form={viewTransportationForm}
                        onFinish={onViewTransportationFormFinish}
                        layout="vertical"
                        scrollToFirstError={true}
                        // initialValues={}
                    >
                        <TransportationForm/>
                    </Form>
            </Drawer>

            <Typography.Title level={4} className="mb-4">Locations<Button type="dashed" className="ml-4" onClick={()=>setnewLocationDrawerVisible(true)}><PlusOutlined/> Add Location</Button></Typography.Title>
            
            <Row gutter={[32,32]}>
                <Col xl={8} style={{maxHeight: '400px', overflow: 'scroll'}}>
                    <Row gutter={[8,8]}>
                        {
                            (locationListQuery && locationListQuery.data) && locationListQuery.data.map( (item, key) => (
                                <Col xl={24} xs={24} key={key}>
                                    <Card
                                        bordered={true}
                                        hoverable={true}
                                        // className="card-shadow"
                                        size="small"
                                        // cover={<img src={img} style={{height: '100%'}} />}
                                        onClick={()=>handleViewTransportation(item)}
                                        onMouseOver={()=>onLocationHover(item, 'over')}
                                        onMouseOut={()=>onLocationHover(item, 'out')}
                                    >
                                        <Card.Meta
                                            title={<><Typography.Title level={5} className="mb-1">{item.name}</Typography.Title><small><Typography.Text type="secondary">{item.code}</Typography.Text></small></>}
                                            // description={item.price}
                                        />
                                    </Card>
                                </Col>
                            ))
                        }
                    </Row>
                </Col>
                <Col xl={16}>
                    <div style={{position:'relative', height: '100%', overflow: 'hidden'}}>
                    <div className="rounded-border-radius" style={{height: '550px', position: 'sticky', position: '-webkit-sticky', top: '0px', left: '0px'}}>
                        <div className='sidebarStyle rounded-border-radius'>
                            <div>{hoveredLocation && hoveredLocation.name} | Longitude: {mapDefaultCoordinates.lng} | Latitude: {mapDefaultCoordinates.lat} | Zoom: {mapDefaultCoordinates.zoom}</div>
                        </div>
                        <div ref={el => mapContainerRef = el} className="mapContainer" />
                    </div>
                    </div>
                </Col>
            </Row>
        </>
    )
}

export default Page;