import React from 'react'
import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');
import BookingService from 'services/Booking'
import CustomerService from 'services/Booking/Customer'
import ProductService from 'services/Booking/Product'
import PackageService from 'services/Booking/Package'
import RoomTypeService from 'services/Hotel/RoomType'
import ScheduleService from 'services/Transportation/ScheduleService'
import ViewBookingComponent from 'components/Booking/View'
import { queryCache } from 'react-query'
import Sun from 'assets/sun-solid.svg'
import Moon from 'assets/moon-regular.svg'
import ShipSolid from 'assets/ship-solid.svg'

import db from 'common/db.json'
import img from 'assets/placeholder-1-1.jpg'
import Loading from 'common/Loading'

import { Row, Col, Card, Space, Button, Select, Input, DatePicker, Form, Divider, Typography, notification, Modal, Alert, message, Tag, InputNumber, Tooltip, Carousel, Table, Checkbox  } from 'antd'
const { Option } = Select;
const { RangePicker } = DatePicker;
import Icon, { MinusCircleOutlined, ReloadOutlined } from '@ant-design/icons'

const productTypeData = ['per_booking', 'per_guest'];
const packageTypeData = ['per_booking', 'per_guest'];

const enumerateDaysBetweenDates = function(startDate, endDate) {
    var dates = [];

    var currDate = moment(startDate).startOf('day');
    var lastDate = moment(endDate).startOf('day');

    while (currDate.add(1, 'days').diff(lastDate) < 0) {
        // console.log(currDate.toDate());
        dates.push(currDate.clone().format('YYYY-MM-DD'));
    }

    return [moment(startDate).format('YYYY-MM-DD'), ...dates, moment(endDate).format('YYYY-MM-DD')];
};

export default function Page(props) {

    let addPax = {
        adultPax: null,
        kidPax: null,
        infantPax: null,
    }

    let removePax = {
        adultPax: null,
        kidPax: null,
        infantPax: null,
    }

    const tagColor = {
        draft: 'text-purple',
        pending: 'text-warning',
        confirmed: 'text-success',
        cancelled: 'text-danger',
    };

    // States
    const [adultPax, setAdultPax] = React.useState(null);
    const [kidPax, setKidPax] = React.useState(null);
    const [infantPax, setInfantPax] = React.useState(null);
    const [saveBookingAsDraft, setSaveBookingAsDraft] = React.useState(null);
    const [saveSend, setsaveSend] = React.useState(null);
    const [newCustomerModalVisible, setnewCustomerModalVisible] = React.useState(false);
    const [isOvernightBookingType, setisOvernightBookingType] = React.useState(null);
    const [totalBookingCost, settotalBookingCost] = React.useState(0);
    const [totalWalkInBookingCost, settotalWalkInBookingCost] = React.useState(0);
    const [selectedProducts, setselectedProducts] = React.useState([]);
    const [selectedRoomTypes, setselectedRoomTypes] = React.useState([]);
    const [selectedPackages, setselectedPackages] = React.useState([]);
    const [saveAndSendModalVisible, setsaveAndSendModalVisible] = React.useState(false);
    const [productGridSize, setproductGridSize] = React.useState(3);
    const [focCount, setfocCount] = React.useState(0);
    const [dateOfVisit, setdateOfVisit] = React.useState([]);
    const [arrivalSchedules, setArrivalSchedules] = React.useState([]);
    const [departureSchedules, setDepartureSchedules] = React.useState([]);
    const [firstTrip, setFirstTrip] = React.useState({});
    const [secondTrip, setSecondTrip] = React.useState({});
    const [modeOfTransportation, setmodeOfTransportation] = React.useState(null);
    const [paxFitsRoom, setpaxFitsRoom] = React.useState({});
    const [lockModeOfTransportation, setlockModeOfTransportation] = React.useState(false);
    const [bookingAsWalkIn, setBookingAsWalkIn] = React.useState(false);
    const [packageFilter, setPackageFilter] = React.useState('');
    const [productFilter, setProductFilter] = React.useState('');
    const [selectedAgent, setSelectedAgent] = React.useState(null);
    const [selectedSalesDirector, setSelectedSalesDirector] = React.useState(null);
    const [customer, setCustomer] = React.useState(null);
    const [bookingTags, setBookingTags] = React.useState([]);
    const [showUnavailableProducts, setShowUnavailableProducts] = React.useState(false);
    const [showUnavailablePackages, setShowUnavailablePackages] = React.useState(false);
    const [ferryOnly, setFerryOnly] = React.useState(false);

    // Golf states
    const [bookingDays, setBookingDays] = React.useState([]);
    const [isGolf, setIsGolf] = React.useState(false);
    const [teeTimeSchedules, setTeeTimeSchedules] = React.useState([]);
    const [guestTeeTime, setGuestTeeTime] = React.useState([]);

    // Tags
    const [selectedProductTypeTags, setselectedProductTypeTags] = React.useState([]);
    const [selectedPropertyTags, setselectedPropertyTags] = React.useState([]);
    const [selectedPackageTypeTags, setselectedPackageTypeTags] = React.useState([]);
    const [selectedEntityTags, setselectedEntityTags] = React.useState([]);

    const dtt_weekday = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const dtt_weekend = ['Saturday', 'Sunday'];
    const ovn_weekday = ['Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    const ovn_weekend = ['Friday', 'Saturday', 'Sunday'];

    // Forms
    const [newBookingForm] = Form.useForm();
    const [newCustomerForm] = Form.useForm();

    // Post, Put
    const [newBookingQuery, {isLoading: newBookingQueryIsLoading, error: newBookingQueryError, reset: newBookingQueryReset}] = BookingService.create();
    const [newCustomerQuery, {isLoading: newCustomerQueryIsLoading, error: newCustomerQueryError}] = CustomerService.create();
    const [getAvailableCamayaTransportationSchedulesQuery, {isLoading: getAvailableCamayaTransportationSchedulesQueryIsLoading}] = ScheduleService.getAvailableCamayaTransportationSchedules();
    const [teeTimeSchedulesQuery, {isLoading: teeTimeSchedulesQueryIsLoading, reset: teeTimeSchedulesQueryReset}] = BookingService.teeTimeSchedules();
    // Get
    const customerListQuery = CustomerService.list(props.isTripping || false);
    const productListQuery = ProductService.list();
    const packageListQuery = PackageService.list(dateOfVisit);
    const roomTypeListQuery = RoomTypeService.list(dateOfVisit);
    const agentListQuery = BookingService.agentList();

    const propertiesData = dateOfVisit.length ? _.uniq(_.map(roomTypeListQuery.data, (i) => { return i.room_type.property.code})) : _.uniq(_.map(roomTypeListQuery.data, 'property_code'));

    React.useEffect(() => {    
        return (() => {
        });
    },[]);

    
    React.useEffect(() => {    
        if (ferryOnly) {
            message.info("You're booking FERRY DEPARTURE ONLY.");
        }
        updateTotalBookingCost();
        setFirstTrip({});
        setSecondTrip({});
    },[ferryOnly]);

    React.useEffect( () => {
    
        // Get sales director id
        if (selectedAgent) {
            const agent = _.find(agentListQuery.data.sales_agents, i => i.user_id == selectedAgent);
            // console.log(agent);
            if (agent) {
                setSelectedSalesDirector(agent.sales_director_id);
                newBookingForm.setFieldsValue({ sd_tag: agent.sales_director_id});
            }
        }

    }, [selectedAgent]);

    React.useEffect( () => {

        setSelectedSalesDirector(null);
        newBookingForm.setFieldsValue({ sd_tag: ''});

        setSelectedAgent(null);
        newBookingForm.setFieldsValue({ agent_tag: ''});
    
        // Get sales director id
        if (customer) {
            const c = _.find(customerListQuery.data, i => i.id == customer);

            if (c.user && c.user.user_type == 'agent') {
                newBookingForm.setFieldsValue({agent_tag: c.user.id});
                setSelectedAgent(c.user.id);
            }
        }

    }, [customer]);

    React.useEffect( () => {

        if (selectedPackages && !_.find(selectedPackages, i => i.camaya_transportation_available == true)) {
            // console.log('test');
            setmodeOfTransportation(null);
            setFirstTrip({});
            setSecondTrip({});
            newBookingForm.setFieldsValue({mode_of_transportation:null});
            setlockModeOfTransportation(false);
            setTeeTimeSchedules([]);
            setGuestTeeTime([]);
        }

    }, [selectedPackages]);

    React.useEffect(() => {    
        roomTypeListQuery.refetch();
        packageListQuery.refetch();

        if ((dateOfVisit && dateOfVisit.length == 2)) setBookingDays(_.uniq(enumerateDaysBetweenDates(dateOfVisit[0], dateOfVisit[1])));

        setArrivalSchedules([]);
        setDepartureSchedules([]);
        setFirstTrip({});
        setSecondTrip({});
        setTeeTimeSchedules([]);
        setGuestTeeTime([]);
    },[dateOfVisit]);

    React.useEffect(() => {    
        // check pax total room capacity
        getTotalRoomCapacity();
        if (firstTrip.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0))) {
            setFirstTrip({});
        }
        if (secondTrip.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0))) {
            setSecondTrip({});
        }
    },[selectedRoomTypes, selectedPackages, adultPax, kidPax, infantPax]);

    React.useEffect( () => {
        updateTotalBookingCost();
    }, [selectedPackages, selectedProducts, selectedRoomTypes, adultPax, kidPax, firstTrip, secondTrip]);

    React.useEffect( () => {
        // console.log(customerListQuery.data);
    }, [customerListQuery.data]);

    const handleGuestTeeTimeSelectionChange = (date, tee_time_schedule_id, index, type) => {
        // console.log(date, tee_time_schedule_id, index);

        setGuestTeeTime(prev => {

            // const data = _.filter([...prev], item => (item.date != date && item.index != index));
            const i = _.findIndex([...prev], item => (item.date == date && item.index == index && item.type == type));
            // console.log(i);

            let data = {};

            if (tee_time_schedule_id) {
                data = {
                    date: date,
                    tee_time_schedule_id: tee_time_schedule_id,
                    index: index,
                    type: type,
                    schedule_data: _.find(teeTimeSchedules, i => i.id == tee_time_schedule_id)
                };
            }
 
            if (i < 0) {
                if (tee_time_schedule_id) {
                    return [...prev, data];
                } else {
                    return [...prev];
                }
            } else {

                // console.log(i);
                if (tee_time_schedule_id) {
                    prev[i]['tee_time_schedule_id'] = tee_time_schedule_id;
                    prev[i]['schedule_data'] = _.find(teeTimeSchedules, i => i.id == tee_time_schedule_id);
                } else {
                    prev.splice(i, 1);
                }

                return [...prev];

            }

        });
    }

    const onSearch = (val) => {
        // console.log('search:', val);
    }

    const updateTotalBookingCost = () => {
        const adult_pax = parseInt(newBookingForm.getFieldValue('adult_pax')) || 0;
        const kid_pax = parseInt(newBookingForm.getFieldValue('kid_pax')) || 0;
        // Compute packages price
        const total_pax = adult_pax + kid_pax;

        const nights = moment(dateOfVisit[1]).diff(moment(dateOfVisit[0]), 'days');

        let total_cost = 0;
        let walkin_total_cost = 0;

        /**
         * Compute for packages
         */
        selectedPackages.map((item, key) => {
            
            // total_cost = parseFloat(total_cost) + (parseFloat(item.price) * (item.type == 'per_booking' ? (nights ? parseInt(item.quantity) * parseInt(nights) : parseInt(item.quantity)) : total_pax));

            // total_cost = parseFloat(total_cost) + (parseFloat(item.price) * (item.type == 'per_booking' ? parseInt(item.quantity) * nights : total_pax));
            
            walkin_total_cost = parseFloat(walkin_total_cost) + (parseFloat(item.walkin_price ? item.walkin_price : item.price) * (item.type == 'per_booking' ? (nights ? parseInt(item.quantity) * parseInt(nights) : parseInt(item.quantity)) : total_pax));

            if (item.type == 'per_booking') {
                if (nights) {
                    total_cost = parseFloat(total_cost) + (parseFloat(item.price) * parseInt(item.quantity));
                } else {
                    // dtt per booking
                    total_cost = parseFloat(total_cost) + (parseFloat(item.price) * parseInt(item.quantity));
                }
                
            } else {
                total_cost = parseFloat(total_cost) + (parseFloat(item.price) * total_pax - (focCount * parseFloat(item.price)));
            }
        });

        /**
         * Compute for packages
         */
        selectedProducts.map((item, key) => {
            // console.log(item.price, item.walkin_price);
            total_cost = parseFloat(total_cost) + (parseFloat(item.price) * (item.type == 'per_booking' ? parseInt(item.quantity) : total_pax));
            walkin_total_cost = parseFloat(walkin_total_cost) + (parseFloat(item.walkin_price ? item.walkin_price : item.price) * (item.type == 'per_booking' ? parseInt(item.quantity) : total_pax));
        });

        /**
         * Compute for rooms
         */
        // if (selectedRoomTypes.length) {
            selectedRoomTypes.map((item, key) => {
                const room_type = _.find(roomTypeListQuery.data, i => (i.room_type.id == item.room_type_id && i.entity == item.entity));
                // total_cost = parseFloat(total_cost) + (parseFloat(room_type.room_type.rack_rate) * parseInt(item.quantity) * nights);
                total_cost = parseFloat(total_cost) + (parseFloat(room_type.room_rate_total) * parseInt(item.quantity));
            });
        // }

        if (focCount > 0) {
            // #KIT
            const tripping_dtt = _.find(productListQuery.data, i => i.code == 'TRIPPING_DTT');

            if (!isOvernightBookingType) {
                total_cost = parseFloat(total_cost) - (parseFloat(tripping_dtt.price) * focCount);
            }
        }

        // Ferry only
        if (firstTrip?.seat_segment_id) {
            // console.log(firstTrip, secondTrip)
            total_cost = parseFloat(total_cost) + ( (parseFloat(firstTrip?.rate) * total_pax) )
        }
        if (secondTrip?.seat_segment_id) {
            // console.log(firstTrip, secondTrip)
            total_cost = parseFloat(total_cost) + ( (parseFloat(secondTrip?.rate) * total_pax) )
        }

        settotalBookingCost(total_cost);
        settotalWalkInBookingCost(walkin_total_cost);
    }

    const handleChangePax = (selected_count, type) => {   
        
        let pax;
        
        switch (type) {
            case 'adultPax':
                pax = adultPax;
                setAdultPax(selected_count);
            break;

            case 'kidPax':
                pax = kidPax;
                setKidPax(selected_count);
            break;

            case 'infantPax':
                pax = infantPax;
                setInfantPax(selected_count);
            break;
        } 

        if (selected_count > pax) {
            for (var i = pax; i < selected_count; i++) {
                addPax[type]();
            }
        } else {
            let _pax = pax;
            while (_pax > selected_count) {
                _pax--;
                removePax[type](_pax);
            }
        }
        
    
    }

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

        const newValues = {
                ...values,
                asDraft: saveBookingAsDraft,
                saveSend: saveSend,
                selectedProducts: selectedProducts,
                selectedPackages: selectedPackages,
                selectedRoomTypes: selectedRoomTypes,
                firstTrip: firstTrip,
                secondTrip: secondTrip,
                bookingAsWalkIn: bookingAsWalkIn,

                isGolf: isGolf,
                guestTeeTime: guestTeeTime,
                ferryOnly: ferryOnly,
        };

        // console.log(newValues);
        // return false;
        // console.log(selectedProducts,selectedPackages,selectedRoomTypes);
        if (!ferryOnly && selectedProducts.length <= 0 && selectedPackages.length <= 0 && selectedRoomTypes.length <= 0) {
            message.warning('Please select a product, package, or room reservation');
            return false;
        }

        if (ferryOnly && (!firstTrip?.seat_segment_id && !secondTrip?.seat_segment_id)) {
            message.warning('Ferry departure only booking should select a trip to book.');
            return false;
        }

        if (modeOfTransportation == 'camaya_transportation' && (!firstTrip?.seat_segment_id && !secondTrip?.seat_segment_id)) {
            message.warning('Please select a trip to book.');
            return false;
        }

        if (!paxFitsRoom.canFit && isOvernightBookingType) {
            message.warning('Sorry, total pax exceeds the total maximum room capacity.');
            window.location.hash = '#rooms';
            return false;
        }

        if (newBookingQueryIsLoading) {
            message.info("Saving booking in progress...");
            return false;
        }
        
        newBookingQuery(newValues, {
            onSuccess: (res) => {
    
                console.log(res);

                queryCache.setQueryData(['bookings', { reference_number: res.data.reference_number }], res.data);

                notification.success({
                    message: 'New Booking',
                    description:
                        ``,
                });

                // Reset Forms
                newBookingForm.resetFields();
                // addPax.adultPax();
                removePax.adultPax(_.range(adultPax));
                removePax.kidPax(_.range(kidPax));
                removePax.infantPax(_.range(infantPax));
                setAdultPax(null);
                setKidPax(null);
                setInfantPax(null);
                setTeeTimeSchedules([]);
                setGuestTeeTime([]);

                props.setBookingPanes( prev => {

                    const targetPaneIndex = _.findIndex(prev, { key: props.paneKey });
                    prev.splice(targetPaneIndex, 1);

                    return [...prev, {
                        title: <><span className={tagColor[res.data.status]} style={{fontSize: '1.4rem'}}>&bull;</span>{` View ${res.data.reference_number}`}</>,
                        content: <ViewBookingComponent isTripping={props.isTripping || false} referenceNumber={res.data.reference_number} status={res.data.status} />,
                        key: res.data.reference_number
                        }];
                });

                props.updateBookingTabs({ reference_number: res.data.reference_number, status: res.data.status}, 'add');

                props.setBookingPaneActiveKey(res.data.reference_number);
    
            },
            onError: (e) => {
                console.log(e);
                // newBookingQuery.reset();
                newBookingQueryReset();
                notification.error({
                    message: <div style={{textTransform:'capitalize'}}>{e.error.replace('_', ' ')}</div>,
                    description:
                        e.message,
                });
            }
        });
      }

    const Toolbar = () => {
        {/* toolbar */}
        return (
            <Row className="my-4">
                <Col xl={12}>
                    {/* Total Booking Cost: {totalBookingCost} */}
                    <Space size="large"><Typography.Title level={3}>&#8369;{totalBookingCost} <small style={{fontWeight: 'normal', opacity: 0.75}}>total</small></Typography.Title><Typography.Title level={4}>&#8369;{totalWalkInBookingCost} <small style={{fontWeight: 'normal', opacity: 0.75}}>walk-in total</small></Typography.Title></Space>
                </Col>
                <Col xl={12}>
                    <div style={{display:'flex', alignItems:'flex-end', justifyContent:'flex-end'}}>
                    <Space size="small">
                        <Button onClick={()=> saveBookingAsWalkIn()}>Book as Walk-in</Button>
                        <Button onClick={()=> saveBooking({asDraft: true})}>Save as draft</Button>
                        <Button onClick={()=> setsaveAndSendModalVisible(true)} type="primary">Book</Button>
                    </Space>
                    </div>
                </Col>
            </Row>
        )
    }

    const saveBooking = ({asDraft, saveSend}) => {
        setSaveBookingAsDraft(asDraft);
        setsaveSend(saveSend);
        newBookingForm.submit();
    }

    const saveBookingAsWalkIn = () => {
        setBookingAsWalkIn(true);
        setsaveSend(false);
        newBookingForm.submit();
    }

    const handleAdditionalEmailChange = (value) => {
        const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        _.remove(value, e => !re.test(e));
    }

    const isOvernight = (date_of_visit) => {
        if (date_of_visit) return moment(date_of_visit[1]).isAfter(moment(date_of_visit[0]));
    }

    const isDayAllowed = (allowed_days, date_of_visit) => {

        const [arrival, departure] = date_of_visit || [null, null];
        if (arrival) {
            return _.includes(allowed_days, arrival.format('ddd').toLowerCase());
        }
        return true;
    }

    const isDateExcluded = (exclude_days, date_of_visit) => {

        const [arrival, departure] = date_of_visit || [null, null];
        if (arrival) {
            return _.includes(exclude_days, arrival.format('YYYY-MM-DD').toLowerCase());
        }
        return false;
    }

    const isAvailableToSelect = (date_of_visit, availability) => {

        if (isOvernight(date_of_visit) == true && availability == 'for_dtt') {
            return false;
        } else if (isOvernight(date_of_visit) == false && availability == 'for_overnight') {
            return false;
        } else {
            return true;
        }

    }

    const getTeeTimeSchedules = () => {

        teeTimeSchedulesQuery({
            dates: bookingDays
        }, {
            onSuccess: (res) => {
                // console.log(res);
                setTeeTimeSchedules(res.data);
            },
            onError: (e) => {
                message.danger(e.error);
            }
        })
    }

    const checkProductAvailability = (availability) => {
        const date_of_visit = newBookingForm.getFieldValue('date_of_visit');
    
        if (!date_of_visit) {
            message.info('Select a date of visit to choose a product.');
            return false;
        }

        const isAvailable = isAvailableToSelect(date_of_visit, availability);

        if (!isAvailable && availability == 'for_dtt') {
            message.warning(`Product is for DTT bookings only.`);
            return false;
        } else if (!isAvailable && availability == 'for_overnight') {
            message.warning(`Product is for OVERNIGHT bookings only.`);
            return false;
        }

        return true;
    }

    const handleSelectProduct = ({id: product_id, availability, price, type, walkin_price, category}) => {

        /**
         * Checks the product availability
         */
        if (!checkProductAvailability(availability)) return false;

        if (category == 'golf') {
            setIsGolf(true);

            // Check days golf tee time schedule
            getTeeTimeSchedules();
        } else {
            setIsGolf(false);
            setTeeTimeSchedules([]);
        }
    
        const exists = _.includes(_.map(selectedProducts, 'product_id'), product_id);

        if (!exists) {
            setselectedProducts( prev => [...prev, { product_id: product_id, quantity: 1, type: type, price: price, walkin_price: walkin_price }]);
        } else {
            setselectedProducts( prev => _.filter(prev, (item) =>  item.product_id != product_id));
        }

    }

    const handleChangeProductQuantity = ({id: product_id, availability, price, type, category, walkin_price}, quantity) => {

        /**
         * Checks the product availability
         */
        if (!checkProductAvailability(availability)) return false;

        if (category == 'golf') {
            setIsGolf(true);

            // Check days golf tee time schedule
            getTeeTimeSchedules();
        } else {
            setIsGolf(false);
            setTeeTimeSchedules([]);
        }

        const exists = _.includes(_.map(selectedProducts, 'product_id'), product_id);

        if (quantity > 0) {
            setselectedProducts( prev => [..._.filter(prev, (item) =>  item.product_id != product_id), { product_id: product_id, type: type, quantity: quantity, price: price, walkin_price: walkin_price }]);
        } else if (exists && quantity == 0) {
            setselectedProducts( prev => _.filter(prev, (item) =>  item.product_id != product_id));
        }
    }

    const getTotalRoomCapacity = () => {
        if (selectedRoomTypes.length || selectedPackages.length) {

            const total_pax_with_infant = (parseInt(adultPax || 0) + parseInt(kidPax || 0) + parseInt(infantPax || 0));
            const total_pax = (parseInt(adultPax || 0) + parseInt(kidPax || 0));

            const room_capacity = _.sumBy(selectedRoomTypes, 'capacity');
            const room_max_capacity = _.sumBy(selectedRoomTypes, 'max_capacity');
            const packagesMinPax = _.sumBy(selectedPackages, 'min_adult');
            const packagesMaxPax = _.sumBy(selectedPackages, 'max_adult');

            const canFit = ((room_max_capacity + (packagesMaxPax ? packagesMaxPax : packagesMinPax)) >= total_pax_with_infant);
            const extraPax = (total_pax - room_capacity) < 0 ? 0 : (total_pax - room_capacity);

            console.log('can fit?: ', canFit);
            console.log('extra pax: ', extraPax);

            setpaxFitsRoom({
                canFit: canFit,
                extraPax: extraPax,
                totalRoomCapacity: room_capacity,
                totalRoomMaxCapacity: room_max_capacity,
            });
            // compute the total pax and room capacity
        }
    }

    const handleSelectRoomType = (room, quantity) => {

        const date_of_visit = newBookingForm.getFieldValue('date_of_visit');

        if (!date_of_visit) {
            message.info('Select a date of visit to choose a product.');
            return false;
        }

        // const isOvernight = moment(date_of_visit[1]).isAfter(moment(date_of_visit[0]));

        if (isOvernight(date_of_visit) == false) {
            message.warning('Room Reservation is for overnight booking only. The date of visit is the same as the date of departure.');
            return false;
        }

        const exists = _.find(selectedRoomTypes, { 'room_type_id': room.room_type.id, 'entity': room.entity });

        if (quantity > 0) {
            setselectedRoomTypes( prev => [
                ..._.filter(prev, (item) => { return (item.entity != room.entity || item.room_type_id != room.room_type.id) }),
                // ...prev,
                {room_type_id: room.room_type.id, entity: room.entity, quantity: quantity, capacity: room.room_type.capacity * quantity, max_capacity: room.room_type.max_capacity * quantity}
            ]);
        } else if (exists && quantity <= 0) {
            setselectedRoomTypes( prev => _.filter(prev, (item) => (item.room_type_id != room.room_type.id || item.entity != room.entity)));
        }

    }

    const checkPackageAvailability = (availability) => {
        const date_of_visit = newBookingForm.getFieldValue('date_of_visit');
    
        if (!date_of_visit) {
            message.info('Select a date of visit to choose a package.');
            return false;
        }

        const isAvailable = isAvailableToSelect(date_of_visit, availability);

        if (!isAvailable && availability == 'for_dtt') {
            message.warning(`Package is for DTT bookings only.`);
            return false;
        } else if (!isAvailable && availability == 'for_overnight') {
            message.warning(`Package is for OVERNIGHT bookings only.`);
            return false;
        }

        return true;
    }

    // per_booking package
    const handleChangePackageQuantity = ({id: package_id, availability, selling_price, weekday_rate, weekend_rate, regular_price, type, allowed_days, exclude_days, min_adult, max_adult, arrival_schedules, departure_schedules, camaya_transportation_available, category, walkin_price}, quantity) => {

        const [arrival, departure] = newBookingForm.getFieldValue('date_of_visit') || [null, null];

        if (!arrival && !departure) {
            message.info('Select a date of visit to choose a package.');
            CalendarPickerRef.current.focus();
            return false;
        }

        if (!isDayAllowed(allowed_days, [arrival, departure])) {
            message.info("This package is not allowed to be availed on "+ arrival.format('dddd')+".");
            return false;
        }

        if (isDateExcluded(exclude_days, [arrival, departure])) {
            message.info("This package is not allowed to be availed on "+ arrival.format('YYYY-MM-DD')+".");
            return false;
        }

        setselectedPackages([]);
        setselectedProducts([]);
        setselectedRoomTypes([]);

        if (category == 'golf') {
            setIsGolf(true);

            // Check days golf tee time schedule
            getTeeTimeSchedules();
        } else {
            setIsGolf(false);
            setTeeTimeSchedules([]);
        }

        /**
         * Checks the package availability
         */
        if (!checkPackageAvailability(availability)) return false;

        if (_.find(selectedPackages, i => i.camaya_transportation_available == true) || camaya_transportation_available) {
            setArrivalSchedules(prev => _.uniqBy([...prev, ...arrival_schedules], 'seat_segment_id'));
            setDepartureSchedules(prev => _.uniqBy([...prev, ...departure_schedules], 'seat_segment_id'));
            setmodeOfTransportation('camaya_transportation');
            setlockModeOfTransportation(true);

            newBookingForm.setFieldsValue({mode_of_transportation: 'camaya_transportation'});
        }

        const exists = _.includes(_.map(selectedPackages, 'package_id'), package_id);
        console.log(quantity);
        if (quantity > 0) {

            const days = [...bookingDays];

            if (isOvernightBookingType) {
                days.splice(-1);
            }
            
            let price = 0;

            if (isOvernightBookingType) {
                days.forEach( (value, index) => {
                    if (ovn_weekday.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekday_rate);
                    } else if (ovn_weekend.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekend_rate);
                    } else {
                        price = parseFloat(price) + parseFloat(selling_price);
                    }
                });
            } else {
                days.forEach( (value, index) => {
                    if (dtt_weekday.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekday_rate);
                    } else if (dtt_weekend.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekend_rate);
                    } else {
                        price = parseFloat(price) + parseFloat(selling_price);
                    }
                });
            } 

            setselectedPackages( prev => [..._.filter(prev, (item) =>  item.package_id != package_id), { package_id: package_id, type: type, quantity: quantity, price: price, regular_price:regular_price, walkin_price: walkin_price, min_adult: min_adult * quantity, max_adult: max_adult * quantity, camaya_transportation_available: camaya_transportation_available }]);

        } else if (exists && quantity == 0) {
            setselectedPackages( prev => _.filter(prev, (item) =>  item.package_id != package_id));
        }
    }

    // per_guest_package
    const handleSelectPackage = ({id: package_id, availability, selling_price, weekday_rate, weekend_rate, regular_price, type, allowed_days, exclude_days, arrival_schedules, departure_schedules, camaya_transportation_available,category, walkin_price}) => {

        const [arrival, departure] = newBookingForm.getFieldValue('date_of_visit') || [null, null];

        if (!arrival && !departure) {
            message.info('Select a date of visit to choose a package.');
            CalendarPickerRef.current.focus();
            return false;
        }

        if (!isDayAllowed(allowed_days, [arrival, departure])) {
            message.info("This package is not allowed to be availed on "+ arrival.format('dddd')+".");
            return false;
        }

        if (isDateExcluded(exclude_days, [arrival, departure])) {
            message.info("This package is not allowed to be availed on "+ arrival.format('YYYY-MM-DD')+".");
            return false;
        }

        setselectedPackages([]);
        setselectedProducts([]);
        setselectedRoomTypes([]);

        if (category == 'golf') {
            setIsGolf(true);

            // Check days golf tee time schedule
            getTeeTimeSchedules();
        } else {
            setIsGolf(false);
            setTeeTimeSchedules([]);
        }

        /**
         * Checks the package availability
         */
        if (!checkPackageAvailability(availability)) return false;

        if (_.find(selectedPackages, i => i.camaya_transportation_available == true) || camaya_transportation_available) {
            setArrivalSchedules(prev => _.uniqBy([...prev, ...arrival_schedules], 'seat_segment_id'));
            setDepartureSchedules(prev => _.uniqBy([...prev, ...departure_schedules], 'seat_segment_id'));
            setmodeOfTransportation('camaya_transportation');

            // const bookingFormFieldsValue = newBookingForm.getFieldsValue();
            newBookingForm.setFieldsValue({mode_of_transportation: 'camaya_transportation'});
        }

        const exists = _.includes(_.map(selectedPackages, 'package_id'), package_id);

        if (!exists) {

            const days = [...bookingDays];

            if (isOvernightBookingType) {
                days.splice(-1);
            }
            
            let price = 0;

            if (isOvernightBookingType) {
                days.forEach( (value, index) => {
                    if (ovn_weekday.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekday_rate);
                    } else if (ovn_weekend.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekend_rate);
                    } else {
                        price = parseFloat(price) + parseFloat(selling_price);
                    }
                });
            } else {
                days.forEach( (value, index) => {
                    if (dtt_weekday.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekday_rate);
                    } else if (dtt_weekend.includes(moment(value).format('dddd'))) {
                        price = parseFloat(price) + parseFloat(weekend_rate);
                    } else {
                        price = parseFloat(price) + parseFloat(selling_price);
                    }
                });
            } 

            setselectedPackages( prev => [..._.filter(prev, (item) =>  item.package_id != package_id), { package_id: package_id, quantity: 1, type: type, price: price, regular_price: regular_price, min_adult: 1, max_adult: 10, camaya_transportation_available:camaya_transportation_available, walkin_price: walkin_price }]);
        } else {
            setselectedPackages( prev => _.filter(prev, (item) =>  item.package_id != package_id));
        }

    }

    const handleDateOfVisitChange = (date_of_visit) => {
        if (date_of_visit) {
            setisOvernightBookingType(isOvernight(date_of_visit));
            
            setdateOfVisit([date_of_visit[0], date_of_visit[1]]);
        }

        message.info('Date of visit changed. The inclusions has been reset.');
        setselectedProducts([]);
        setselectedRoomTypes([]);
        setselectedPackages([]);

        setTeeTimeSchedules([]);
        setGuestTeeTime([]);
    }

    const handleModeOfTransportationChange = (value) => {

        setmodeOfTransportation(value);
        setFerryOnly(false);

        if (value == 'camaya_transportation'){
            message.info('Mode of transportation has been changed to "Camaya Transportation". Please select a trip schedule in the inclusions menu to proceed with booking.', 10);

            getAvailableCamayaTransportationSchedulesQuery({
                date_of_visit: dateOfVisit
            },{
                onSuccess: (res) => {
                    console.log(res);
                    setArrivalSchedules(prev => _.uniqBy([...prev, ...res.data.arrival_schedules], 'seat_segment_id'));
                    setDepartureSchedules(prev => _.uniqBy([...prev, ...res.data.departure_schedules], 'seat_segment_id'));
                },
                onError: (e) => {
                    console.log(e);
                }
            })
        } else if (value == 'own_vehicle') {
            newBookingForm.setFieldsValue({guest_vehicles:[
                {
                    vehicle_plate_number: '',
                    vehicle_model: '',
                }
            ]});

            setArrivalSchedules([]);
            setDepartureSchedules([]);
            
        } else {
            setArrivalSchedules([]);
            setDepartureSchedules([]);
        }
        
    }

    const handleReloadSchedules = () => {
        if (modeOfTransportation == 'camaya_transportation'){

            setArrivalSchedules([]);
            setDepartureSchedules([]);

            getAvailableCamayaTransportationSchedulesQuery({
                date_of_visit: dateOfVisit
            },{
                onSuccess: (res) => {
                    console.log(res);
                    setArrivalSchedules(prev => _.uniqBy([...prev, ...res.data.arrival_schedules], 'seat_segment_id'));
                    setDepartureSchedules(prev => _.uniqBy([...prev, ...res.data.departure_schedules], 'seat_segment_id'));
                },
                onError: (e) => {
                    console.log(e);
                }
            })
        } else {
            setArrivalSchedules([]);
            setDepartureSchedules([]);
        }
    }

    const handleProductTypeTagChange = (tag, checked) => {

        const nextSelectedTags = checked ? [...selectedProductTypeTags, tag] : selectedProductTypeTags.filter(t => t !== tag);

        setselectedProductTypeTags(nextSelectedTags);

    }

    const handlePropertyTagChange = (tag, checked) => {

        const nextSelectedTags = checked ? [...selectedPropertyTags, tag] : selectedPropertyTags.filter(t => t !== tag);

        setselectedPropertyTags(nextSelectedTags);

    }

    const handleEntityTagChange = (tag, checked) => {

        const nextSelectedTags = checked ? [...selectedEntityTags, tag] : selectedEntityTags.filter(t => t !== tag);

        setselectedEntityTags(nextSelectedTags);

    }

    const handlePackageTypeTagChange = (tag, checked) => {

        const nextSelectedTags = checked ? [...selectedPackageTypeTags, tag] : selectedPackageTypeTags.filter(t => t !== tag);

        setselectedPackageTypeTags(nextSelectedTags);

    }

    const children = [];
    
    for (let i = 10; i < 36; i++) {
        children.push(<Option key={i.toString(36) + i}>{i.toString(36) + i}</Option>);
    }

    return (
        <div className="fadeIn">

            <Modal
                visible={saveAndSendModalVisible}
                onCancel={()=>setsaveAndSendModalVisible(false)}
                footer={null}
                >
                <Row gutter={[8,8]} className="my-4">
                    <Col xl={12} xs={24}><Button size="large" block onClick={()=>saveBooking({asDraft: false, saveSend: false})}>Save Only</Button></Col>
                    <Col xl={12} xs={24}><Button size="large" type="primary" block onClick={()=>saveBooking({asDraft: false, saveSend: true})}>Save and Send</Button></Col>
                </Row>
            </Modal>

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
                            <Input placeholder="Contact number" type="number"/>
                        </Form.Item>
                    </Col>
                    <Col xl={24}>
                        <Form.Item name="email" label="Email address" rules={[
                            {
                                required: true,
                            },
                            {
                                type: 'email'
                            }
                        ]}>
                            <Input placeholder="Email address"/>
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
                layout="vertical"
                onFinish={e => onFinish(e)}
                onFinishFailed={ e => setsaveAndSendModalVisible(false)}
                scrollToFirstError={true}
                form={newBookingForm}
                autoComplete="off"
                initialValues={{
                    // customer: 1,
                    // date_of_visit: [moment('2020-10-20'), moment('2020-10-25')],
                    // mode_of_transportation: 'undecided',
                    // eta: '7AM',
                    // adult_pax: 1,
                    // kid_pax: null,
                    // infant_pax: null,
                    // adult_guests: [
                    //     {
                    //         first_name: 'Kit',
                    //         last_name: 'Seno',
                    //         age: 30,
                    //         nationality: 'Fil'
                    //     }
                    // ]
                    additional_emails: []
                }}
            >

                <Toolbar />

                <Row gutter={[48,48]}>
                <Col xl={24} xs={24}>
                    <Typography.Title level={4} className="mb-4">Booking Details</Typography.Title>
                    <Row gutter={[32,32]}>
                        <Col xl={12} xs={24}>
                            <Row gutter={[8,8]}>
                                <Col xl={12} xs={24}>
                                    <Form.Item label={`Select a customer`} name="customer" rules={[{required:true}]}>
                                        <Select
                                            showSearch
                                            style={{ width: '100%' }}
                                            placeholder={`Select ${props.isTripping ? 'an agent or customer' : 'a customer'}`}
                                            optionFilterProp="children"
                                            onSearch={onSearch}
                                            size="large"
                                            onChange={(e) => setCustomer(e)}
                                            loading={customerListQuery.isLoading || customerListQuery.isFetching}
                                            disabled={customerListQuery.isLoading || customerListQuery.isFetching}
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
                                                    <Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Option>
                                                ))
                                            }
                                        </Select>
                                    </Form.Item>
                                </Col>
                                <Col xl={12}>
                                    <Form.Item label="Date of visit" name="date_of_visit" rules={[{required:true}]} extra={newBookingForm.getFieldValue('date_of_visit') ? (isOvernightBookingType ? <div className="mt-2"><Icon component={Moon} className="text-purple mr-2"/>Overnight</div> : <div className="mt-2"><Icon component={Sun} className="text-yellow mr-2"/>Day Tour</div>) : ''}>
                                        <RangePicker size="large" style={{width: '100%'}} onChange={handleDateOfVisitChange} />
                                    </Form.Item>
                                </Col>
                                <Col xl={8} xs={8}>
                                    <Form.Item label="Adult pax" name="adult_pax" rules={[{required:true}]}>
                                        <Select style={{ width: '100%' }} placeholder="Adult pax" onChange={(e)=>handleChangePax(e, 'adultPax')}>
                                            <Option value="1">1</Option>
                                            <Option value="2">2</Option>
                                            <Option value="3">3</Option>
                                            <Option value="4">4</Option>
                                            <Option value="5">5</Option>
                                            <Option value="6">6</Option>
                                            <Option value="7">7</Option>
                                            <Option value="8">8</Option>
                                            <Option value="9">9</Option>
                                            <Option value="10">10</Option>

                                            <Option value="11">11</Option>
                                            <Option value="12">12</Option>
                                            <Option value="13">13</Option>
                                            <Option value="14">14</Option>
                                            <Option value="15">15</Option>
                                            <Option value="16">16</Option>
                                            <Option value="17">17</Option>
                                            <Option value="18">18</Option>
                                            <Option value="19">19</Option>
                                            <Option value="20">20</Option>

                                            <Option value="21">21</Option>
                                            <Option value="22">22</Option>
                                            <Option value="23">23</Option>
                                            <Option value="24">24</Option>
                                            <Option value="25">25</Option>
                                            <Option value="26">26</Option>
                                            <Option value="27">27</Option>
                                            <Option value="28">28</Option>
                                            <Option value="29">29</Option>
                                            <Option value="30">30</Option>

                                            <Option value="31">31</Option>
                                            <Option value="32">32</Option>
                                            <Option value="33">33</Option>
                                            <Option value="34">34</Option>
                                            <Option value="35">35</Option>
                                            <Option value="36">36</Option>
                                            <Option value="37">37</Option>
                                            <Option value="38">38</Option>
                                            <Option value="39">39</Option>
                                            <Option value="40">40</Option>

                                            <Option value="41">41</Option>
                                            <Option value="42">42</Option>
                                            <Option value="43">43</Option>
                                            <Option value="44">44</Option>
                                            <Option value="45">45</Option>
                                            <Option value="46">46</Option>
                                            <Option value="47">47</Option>
                                            <Option value="48">48</Option>
                                            <Option value="49">49</Option>
                                            <Option value="50">50</Option>

                                            <Option value="51">51</Option>
                                            <Option value="52">52</Option>
                                            <Option value="53">53</Option>
                                            <Option value="54">54</Option>
                                            <Option value="55">55</Option>
                                            <Option value="56">56</Option>
                                            <Option value="57">57</Option>
                                            <Option value="58">58</Option>
                                            <Option value="59">59</Option>
                                            <Option value="60">60</Option>

                                            <Option value="61">61</Option>
                                            <Option value="62">62</Option>
                                            <Option value="63">63</Option>
                                            <Option value="64">64</Option>
                                            <Option value="65">65</Option>
                                            <Option value="66">66</Option>
                                            <Option value="67">67</Option>
                                            <Option value="68">68</Option>
                                            <Option value="69">69</Option>
                                            <Option value="70">70</Option>

                                            <Option value="71">71</Option>
                                            <Option value="72">72</Option>
                                            <Option value="73">73</Option>
                                            <Option value="74">74</Option>
                                            <Option value="75">75</Option>
                                            <Option value="76">76</Option>
                                            <Option value="77">77</Option>
                                            <Option value="78">78</Option>
                                            <Option value="79">79</Option>
                                            <Option value="80">80</Option>

                                            <Option value="81">81</Option>
                                            <Option value="82">82</Option>
                                            <Option value="83">83</Option>
                                            <Option value="84">84</Option>
                                            <Option value="85">85</Option>
                                            <Option value="86">86</Option>
                                            <Option value="87">87</Option>
                                            <Option value="88">88</Option>
                                            <Option value="89">89</Option>
                                            <Option value="90">90</Option>

                                            <Option value="91">91</Option>
                                            <Option value="92">92</Option>
                                            <Option value="93">93</Option>
                                            <Option value="94">94</Option>
                                            <Option value="95">95</Option>
                                            <Option value="96">96</Option>
                                            <Option value="97">97</Option>
                                            <Option value="98">98</Option>
                                            <Option value="99">99</Option>
                                            <Option value="100">100</Option>

                                        </Select>
                                    </Form.Item>
                                </Col>
                                <Col xl={8} xs={8}>
                                    <Form.Item label="Kid pax" name="kid_pax">
                                        <Select style={{ width: '100%' }} placeholder="Kid pax" onChange={(e)=>handleChangePax(e, 'kidPax')}>
                                            <Option value="0">0</Option>

                                            <Option value="1">1</Option>
                                            <Option value="2">2</Option>
                                            <Option value="3">3</Option>
                                            <Option value="4">4</Option>
                                            <Option value="5">5</Option>

                                        </Select>
                                    </Form.Item>
                                </Col>
                                <Col xl={8} xs={8}>
                                    <Form.Item label="Infant pax" name="infant_pax">
                                        <Select style={{ width: '100%' }} placeholder="Infant pax" onChange={(e)=>handleChangePax(e, 'infantPax')}>
                                            <Option value="0">0</Option>
                                            <Option value="1">1</Option>
                                            <Option value="2">2</Option>
                                            <Option value="3">3</Option>
                                            <Option value="4">4</Option>
                                            <Option value="5">5</Option>
                                        </Select>
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Col>
                        <Col xl={12} xs={24}>
                            <Row gutter={[8,8]}>
                                <Col xl={12} xs={12}>
                                    <Form.Item label="Mode of transportation" name="mode_of_transportation" rules={[{required:true}]}
                                        extra={<Checkbox checked={ferryOnly} onChange={()=> setFerryOnly(!ferryOnly)} disabled={newBookingForm.getFieldValue('mode_of_transportation') != 'camaya_transportation'}>Ferry Departure Only</Checkbox>}
                                        >
                                        <Select disabled={lockModeOfTransportation || !newBookingForm.getFieldValue('date_of_visit')} style={{ width: '100%' }} placeholder={`${!newBookingForm.getFieldValue('date_of_visit') ? 'Select visit date to choose transpo' : 'Transportation'}`} onChange={handleModeOfTransportationChange}>
                                            <Option value="undecided">Undecided</Option>
                                            <Option value="own_vehicle">Own vehicle</Option>
                                            <Option value="camaya_transportation">Camaya transportation</Option>
                                            <Option value="camaya_vehicle">Camaya vehicle</Option>
                                            <Option value="van_rental">Van rental</Option>
                                            <Option value="company_vehicle">Company vehicle</Option>
                                        </Select>
                                    </Form.Item>
                                    <div>
                                    { modeOfTransportation == 'own_vehicle' &&
                                            <>
                                            <Typography.Text strong>Guest vehicles</Typography.Text>
                                            <Form.List name="guest_vehicles">
                                            {
                                                (fields, { add, remove }) => {
                                                    // addPax['adultPax'] = add; removePax['adultPax'] = remove;
                                                    return (
                                                        <div>
                                                            {
                                                                fields.map(field => (
                                                                    <Row key={field.key} gutter={[12,12]}>
                                                                        <Col xl={11} xs={11}>
                                                                            <Form.Item
                                                                                {...field}
                                                                                name={[field.name, 'vehicle_model']}
                                                                                fieldKey={[field.fieldKey, 'vehicle_model']}
                                                                                rules={[{ required: true, message: `#${field.name+1} Vehicle model` }]}
                                                                            >
                                                                                <Input
                                                                                    size="small"
                                                                                    placeholder={`#${field.name + 1} Vehicle model`}
                                                                                />
                                                                            </Form.Item>
                                                                        </Col>
                                                                        <Col xl={12} xs={12}>
                                                                            <Form.Item
                                                                                {...field}
                                                                                name={[field.name, 'vehicle_plate_number']}
                                                                                fieldKey={[field.fieldKey, 'vehicle_plate_number']}
                                                                                rules={[{ required: true, message: `#${field.name+1} Vehicle plate no.` }]}
                                                                            >
                                                                                <Input
                                                                                    size="small"
                                                                                    placeholder={`#${field.name + 1} Vehicle plate no.`}
                                                                                />
                                                                            </Form.Item>
                                                                        </Col>
                                                                        <Col xl={1} xs={1}>
                                                                            {
                                                                                field.name > 0 && <MinusCircleOutlined onClick={() => remove(field.name)} className="mr-2"/>
                                                                            }
                                                                        </Col>
                                                                    </Row>
                                                                ))
                                                            }
                                                            {
                                                                // <Button disabled={fields.length >= adultPax} size="small" onClick={()=>add()}>Add vehicle</Button>
                                                                fields.length < adultPax && <Button size="small" onClick={()=>add()}> Add vehicle</Button>
                                                            }
                                                        </div>
                                                    )
                                                }
                                            }
                                            </Form.List>
                                            </>
                                        }

                                    </div>
                                </Col>
                                <Col xl={12} xs={12}>
                                    <Form.Item label="Estimated time of arrival" name="eta">
                                        <Select style={{ width: '100%' }} placeholder="ETA">
                                            {/* <Option value="6:00 AM">6AM</Option> */}
                                            <Option value="7:00 AM">7AM</Option>
                                            <Option value="8:00 AM">8AM</Option>
                                            <Option value="9:00 AM">9AM</Option>
                                            <Option value="10:00 AM">10AM</Option>
                                            <Option value="11:00 AM">11AM</Option>
                                            <Option value="12:00 PM">12PM</Option>
                                            <Option value="13:00 PM">1PM</Option>
                                            <Option value="14:00 PM">2PM</Option>
                                            <Option value="15:00 PM">3PM</Option>
                                            <Option value="16:00 PM">4PM</Option>
                                            <Option value="17:00 PM">5PM</Option>
                                            <Option value="18:00 PM">6PM</Option>
                                        </Select>
                                    </Form.Item>
                                </Col>
                            </Row>
                            {/* <Divider/> */}
                            <Row gutter={[8,8]} align="bottom">
                                <Col xl={12} xs={12}>
                                    <Form.Item label="Pay until" name="pay_until">
                                        <DatePicker style={{width: '100%'}} placeholder="Date"/>
                                    </Form.Item>
                                </Col>
                                <Col xl={12} xs={12}>
                                    <Form.Item label="Auto cancel booking at" name="auto_cancel_at">
                                        <DatePicker style={{width: '100%'}} placeholder="Date"/>
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Col>

                        <Col xl={12} xs={24}>
                            <Row gutter={[8, 8]}>
                                <Col xl={24} xs={24}>
                                    <Form.Item label="Booking label" name="label">
                                        <Input placeholder="Label" />
                                    </Form.Item>
                                </Col>

                                <Col xl={12} xs={24}>
                                    <Form.Item label="Booking tags" name="tags" rules={[{required:true}]}>
                                        <Select mode="multiple" style={{ width: '100%' }} onChange={(e) => setBookingTags(e)} placeholder="Tags" tokenSeparators={[',',';']}>
                                            <Select.OptGroup label={<small className="text-primary">RE tags</small>}>
                                                <Select.Option value="ESLCC - Sales Agent">ESLCC - Sales Agent</Select.Option>
                                                <Select.Option value="ESLCC - Sales Client">ESLCC - Sales Client</Select.Option>
                                                <Select.Option value="RE - Golf">RE - Golf</Select.Option>
                                                <Select.Option value="SDMB - Sales Director Marketing Budget">SDMB - Sales Director Marketing Budget</Select.Option>
                                                <Select.Option value="Thru Agent - Paying">Thru Agent - Paying</Select.Option>
                                                <Select.Option value="Walk-in - Sales Agent">Walk-in - Sales Agent</Select.Option>
                                                <Select.Option value="Walk-in - Sales Client">Walk-in - Sales Client</Select.Option>
                                            </Select.OptGroup>
                                            <Select.OptGroup label={<small className="text-primary">Homeowner tags</small>}>
                                                <Select.Option value="ESLCC - AFV">ESLCC - AFV</Select.Option>
                                                <Select.Option value="ESLCC - CSV">ESLCC - CSV</Select.Option>
                                                <Select.Option value="ESLCC - HOA">ESLCC - HOA</Select.Option>
                                                <Select.Option value="HOA">HOA</Select.Option>
                                                <Select.Option value="HOA - Access Stub">HOA - Access Stub</Select.Option>
                                                <Select.Option value="HOA - AF Unit Owner">HOA - AF Unit Owner</Select.Option>
                                                <Select.Option value="HOA - Client">HOA - Client</Select.Option>
                                                <Select.Option value="HOA – Gate Access">HOA – Gate Access</Select.Option>
                                                <Select.Option value="HOA - Golf">HOA - Golf</Select.Option>
                                                <Select.Option value="HOA - Member">HOA - Member</Select.Option>
                                                <Select.Option value="HOA - Paying Promo">HOA - Paying Promo</Select.Option>
                                                <Select.Option value="HOA - Voucher">HOA - Voucher</Select.Option>
                                                <Select.Option value="HOA - Walk-in">HOA - Walk-in</Select.Option>
                                                <Select.Option value="HOA - Sales Director Marketing Budget">HOA - Sales Director Marketing Budget</Select.Option>
                                                <Select.Option value="Property Owner (Non-Member)">Property Owner (Non-Member)</Select.Option>
                                                <Select.Option value="Property Owner (HOA Member)">Property Owner (HOA Member)</Select.Option>
                                                <Select.Option value="Property Owner (Dependents)">Property Owner (Dependents)</Select.Option>
                                                <Select.Option value="Property Owner (Guests)">Property Owner (Guests)</Select.Option>
                                            </Select.OptGroup>
                                            <Select.OptGroup label={<small className="text-primary">Commercial tags</small>}>
                                                <Select.Option value="Commercial">Commercial</Select.Option>
                                                <Select.Option value="Commercial - Admin">Commercial - Admin</Select.Option>
                                                <Select.Option value="Commercial - Corre ">Commercial - Corre</Select.Option>
                                                <Select.Option value="Commercial - Golf ">Commercial - Golf</Select.Option>
                                                <Select.Option value="Commercial - Promo">Commecial - Promo</Select.Option>
                                                <Select.Option value="Commercial - Promo (Luventure)">Commercial - Promo (Luventure)</Select.Option>
                                                <Select.Option value="Commercial - Promo (Camaya Summer)">Commercial - Promo (Camaya Summer)</Select.Option>
                                                <Select.Option value="Commercial - Promo (Save Now, Travel Later)">Commercial - Promo (Save Now, Travel Later)</Select.Option>
                                                <Select.Option value="Commercial - Promo (12.12)">Commercial - Promo (12.12)</Select.Option>
                                                <Select.Option value="Commercial - Walk-in">Commercial - Walk-in</Select.Option>
                                                <Select.Option value="Commercial - Website">Commercial - Website</Select.Option>
                                                <Select.Option value="Corporate FIT">Corporate FIT</Select.Option>
                                                <Select.Option value="Corporate Sales">Corporate Sales</Select.Option>
                                                <Select.Option value="CVoucher">CVoucher</Select.Option>
                                                <Select.Option value="DTT - Walk-in">DTT - Walk-in</Select.Option>
                                                <Select.Option value="OTA - Klook">OTA - Klook</Select.Option>
                                                <Select.Option value="Paying - Walk-in">Paying - Walk-in</Select.Option>
                                            </Select.OptGroup>
                                            <Select.OptGroup label={<small className="text-primary">Employee tags</small>}>
                                                <Select.Option value="1Bataan ITS - Employee">1Bataan ITS - Employee</Select.Option>
                                                <Select.Option value="DEV 1 - Employee">DEV 1 - Employee</Select.Option>
                                                {/* <Select.Option value="DEV1 - Employee">DEV1 - Employee</Select.Option> */}
                                                <Select.Option value="ESLCC - Employee">ESLCC - Employee</Select.Option>
                                                <Select.Option value="ESLCC - Employee/Guest">ESLCC - Employee/Guest</Select.Option>
                                                <Select.Option value="ESTLC - Employee">ESTLC - Employee</Select.Option>
                                                <Select.Option value="ESTVC - Employee">ESTVC - Employee</Select.Option>
                                                <Select.Option value="Orion Sky - Employee">Orion Sky - Employee</Select.Option>
                                                <Select.Option value="People Plus - Employee">People Plus - Employee</Select.Option>
                                                <Select.Option value="SLA - Employee">SLA - Employee</Select.Option>
                                                <Select.Option value="DS18 - Employee">DS18 - Employee</Select.Option>
                                                <Select.Option value="DS18 - Events Guest">DS18 - Events Guest</Select.Option>
                                            </Select.OptGroup>
                                            <Select.OptGroup label={<small className="text-primary">Other tags</small>}>
                                                <Select.Option value="DEV 1 - Event/Guest">DEV 1 - Event/Guest</Select.Option>
                                                <Select.Option value="ESLCC - GC">ESLCC - GC</Select.Option>
                                                <Select.Option value="ESLCC - Guest">ESLCC - Guest</Select.Option>
                                                <Select.Option value="ESLCC - Event/Guest">ESLCC - Event/Guest</Select.Option>
                                                <Select.Option value="ESLCC - FOC">ESLCC - FOC</Select.Option>
                                                <Select.Option value="ESTLC - Guest">ESTLC - Guest</Select.Option>
                                                <Select.Option value="ESTLC - Event/Guest">ESTLC - Guest</Select.Option>
                                                <Select.Option value="ESTVC - GC">ESTVC - GC</Select.Option>
                                                <Select.Option value="ESTVC - Guest">ESTVC - Guest</Select.Option>
                                                <Select.Option value="ESTVC - Event/Guest">ESTVC - Event/Guest</Select.Option>
                                                <Select.Option value="Golf Member">Golf Member</Select.Option>
                                                <Select.Option value="House Use">House Use</Select.Option>
                                                <Select.Option value="Magic Leaf - Event/Guest">Magic Leaf - Event/Guest</Select.Option>
                                                <Select.Option value="TA - Rates">TA - Rates</Select.Option>
                                                <Select.Option value="Orion Sky">Orion Sky</Select.Option>
                                                <Select.Option value="Orion Sky - Guest">Orion Sky - Guest</Select.Option>
                                                <Select.Option value="SLA - Event/Guest">SLA - Event/Guest</Select.Option>
                                                <Select.Option value="VIP Guest">VIP Guest</Select.Option>
                                                <Select.Option value="Camaya Golf Voucher">Camaya Golf Voucher</Select.Option>
                                            </Select.OptGroup>
                                        </Select>
                                    </Form.Item>

                                    <Form.Item label="Sales Agent Tagging" name="agent_tag" rules={[{required:false}]}>
                                        <Select
                                            style={{ width: '100%' }}
                                            placeholder="Sales Agent"
                                            onChange={(e) => setSelectedAgent(e)}
                                            showSearch
                                            loading={agentListQuery.isLoading || agentListQuery.isFetching || !agentListQuery.data}
                                            filterOption={(input, option) =>
                                                // console.log(option, input)
                                                option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                            }
                                           >
                                                <Select.Option value="">None</Select.Option>
                                                {
                                                    agentListQuery.data && agentListQuery.data.sales_agents.map( (item, key) => {
                                                        return <Select.Option key={key} value={item.user_id}>{item.first_name+" "+item.last_name}</Select.Option>
                                                    })
                                                }
                                        </Select>
                                    </Form.Item>
                                    <Form.Item label="Sales Director Tagging" name="sd_tag" rules={[{required: bookingTags.includes('SDMB - Sales Director Marketing Budget') }]}>
                                        <Select
                                            style={{ width: '100%' }}
                                            placeholder="Sales Director"
                                            onChange={(e) => setSelectedSalesDirector(e)}
                                            showSearch
                                            disabled={selectedAgent}
                                            value={(selectedSalesDirector)}
                                            loading={agentListQuery.isLoading || agentListQuery.isFetching || !agentListQuery.data}
                                            filterOption={(input, option) =>
                                                // console.log(option, input)
                                                option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                            }
                                           >
                                                <Select.Option value="">None</Select.Option>
                                                {
                                                    agentListQuery.data && agentListQuery.data.sales_directors.map( (item, key) => {
                                                        return <Select.Option key={key} value={item.id}>{item.first_name+" "+item.last_name}</Select.Option>
                                                    })
                                                }
                                        </Select>
                                    </Form.Item>
                                </Col>
                                <Col xl={12}>
                                    <Form.Item label="Source of booking" name="source">
                                        <Select style={{ width: '100%' }} placeholder="Source">
                                            <Option value="call">Call</Option>
                                            <Option value="viber">Viber</Option>
                                            <Option value="facebook_page">Facebook</Option>
                                            <Option value="other">Other</Option>
                                        </Select>
                                    </Form.Item>
                                </Col>
                                
                            </Row>
                        </Col>
                        <Col xl={12} xs={24}>
                            <Row gutter={[8, 8]}>
                                <Col xl={24} xs={24}>
                                    <Form.Item label="Additional emails" name="additional_emails" className="mb-0" rules={[{ max: 10, type:'array', defaultField: { type: 'email' }, }]}>
                                        <Select mode="tags" tokenSeparators={[',',';', ' ']} style={{ width: '100%' }} placeholder="Additional email addresses" onChange={handleAdditionalEmailChange}/>
                                    </Form.Item>
                                </Col>
                                <Col xl={24} xs={24}>
                                    <Form.Item label="Remarks" name="remarks">
                                        <Input.TextArea style={{ width: '100%', borderRadius: '12px' }} placeholder="Remarks"/>
                                    </Form.Item>
                                </Col>
                                <Col xl={24} xs={24}>
                                    <Form.Item label="Billing Instructions" name="billing_instructions">
                                        <Input.TextArea style={{ width: '100%', borderRadius: '12px' }} placeholder="Billing instructions"/>
                                    </Form.Item>
                                </Col>
                            </Row>
                        </Col>
                    </Row>
                </Col>

                <Col xl={24} xs={24}>
                    <Typography.Title level={4} className="mb-4">Inclusions</Typography.Title>
                    <Row gutter={[32,32]} className="p-2">
                        { ferryOnly ? 
                            <Col xl={24} xs={24}>
                                <Alert type="info" message="You're booking Ferry Only. Inclusions are not applicable." />
                            </Col>
                        :
                        <>
                        <Col xl={24}>
                            <Typography.Title level={5} className="mb-3">  
                            <Space>                           
                                <>Products</>    
                                <Button type="primary" icon={<ReloadOutlined />} onClick={() => productListQuery.refetch()} />                            
                                <Input onChange={(e) => {
                                    setProductFilter(e.target.value);
                                }} placeholder="Search Products" style={{width: 200}} />
                                </Space>   
                            </Typography.Title>
                            <div className="mb-2" style={{display: 'flex', justifyContent: 'space-between'}}>
                                <div>
                                <span className="mr-2 text-seconday">Filter:</span>
                                {
                                    productTypeData                                        
                                        .map(tag => (
                                            <Tag.CheckableTag
                                                key={tag}
                                                checked={selectedProductTypeTags.indexOf(tag) > -1}
                                                onChange={checked => handleProductTypeTagChange(tag, checked)}
                                            >{tag}</Tag.CheckableTag>
                                        ))
                                }
                                <Checkbox onChange={() => setShowUnavailableProducts(!showUnavailableProducts)}>Include Unavailable Products</Checkbox>
                                </div>
                                <div>
                                <Space>
                                    <Button onClick={() => setproductGridSize(2)}>small</Button>
                                    <Button onClick={() => setproductGridSize(3)}>normal</Button>
                                    <Button onClick={() => setproductGridSize(4)}>large</Button>
                                </Space>
                                </div>
                            </div>
                            <Row gutter={[8,8]}>
                                {
                                    // db.products.map( (item, key) => (
                                    (productListQuery.data && newBookingForm.getFieldValue('date_of_visit')) ? productListQuery.data
                                        .filter((item) => selectedProductTypeTags.length ? _.includes(selectedProductTypeTags, item.type):true)
                                        .filter((item) => item.status != 'retired')
                                        .filter((item) => showUnavailableProducts ? true : isAvailableToSelect(newBookingForm.getFieldValue('date_of_visit'), item.availability) )
                                        .filter((item) => {    
                                            if (!productFilter) return true;
                                            const re = new RegExp(productFilter, 'i')                                       
                                            return re.test(item.name) || re.test(item.code) || re.test(item.price)
                                        })
                                        .map( (item, key) => (
                                        <Col xl={productGridSize} xs={24} key={key} className="resize-transition">
                                            <Card
                                                bordered={false}
                                                hoverable={true}
                                                className={`card-shadow ${_.includes(_.map(selectedProducts, 'product_id'), item.id) ? 'new-booking-product-selected' : ''}`}
                                                size="small"
                                                style={{opacity: !isAvailableToSelect(newBookingForm.getFieldValue('date_of_visit'), item.availability) ? 0.65 : 1 }}
                                                // cover={<img src={img} style={{width: '100%'}} />}
                                                cover={(item.images && item.images.length) ? 
                                                    <Carousel>
                                                        {
                                                            item.images.map( (image, key) => (
                                                                <div key={key}>
                                                                    <img src={image.image_path} style={{width: '100%'}} />
                                                                </div>
                                                            ))
                                                        }
                                                    </Carousel>
                                                :
                                                    <img src={img} style={{width: '100%'}} />
                                                }
                                                onClick={() => item.type == 'per_guest' ? handleSelectProduct(item) : false}
                                                // extra={_.includes(selectedProducts, item.id) ? <small>added to booking</small> : ''}
                                                actions={[
                                                    item.type == 'per_booking' ? <><small><strong>qty.</strong></small> <InputNumber onChange={(e)=>handleChangeProductQuantity(item, e)} disabled={!newBookingForm.getFieldValue('date_of_visit') || !isAvailableToSelect(newBookingForm.getFieldValue('date_of_visit'), item.availability)} size="small" min={0} value={_.find(selectedProducts, { product_id: item.id }) ? _.find(selectedProducts, { product_id: item.id }).quantity:0}/></> : <><small>per guest</small></>
                                                ]}
                                            >
                                                <Card.Meta
                                                    // title={
                                                    //     <>
                                                    //     <Space size="smaller" direction="vertical">
                                                    //         <span style={{fontSize: '0.8rem'}}>{item.name}</span>
                                                    //         <small>{item.code}</small>
                                                    //     </Space>
                                                    //     <div>
                                                    //         {item.availability == 'for_dtt' && <Icon component={Sun} className="text-yellow"/>}
                                                    //         {item.availability == 'for_overnight' && <Icon component={Moon} className="text-purple"/>}
                                                    //         {item.availability == 'for_dtt_and_overnight' && <Space><Icon component={Sun} className="text-yellow"/><Icon component={Moon} className="text-purple"/></Space>}
                                                    //     </div>
                                                    //     </>
                                                    // }

                                                    title={
                                                        <div style={{position:'relative'}}>
                                                            <div>
                                                                <div style={{lineHeight: 1.2}}>
                                                                    <Typography.Text ellipsis style={{fontSize: '0.8rem'}} title={item.name}>{item.name}</Typography.Text>
                                                                    {/* <br/><small style={{fontSize: '0.2rem'}}>{item.name}</small> */}
                                                                    <br/><small style={{fontSize: '0.5rem', wordBreak:'keep-all'}}>{item.code}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    }
                                                    // description={<>&#8369;{_.round(item.price)}</>}
                                                    description={
                                                        <div style={{display: 'flex', justifyContent: 'space-between'}}>
                                                            <Space>
                                                                {(item.availability == 'for_dtt' || item.availability == 'for_dtt_and_overnight') && <Icon component={Sun} className="text-yellow"/>}
                                                                {(item.availability == 'for_overnight' || item.availability == 'for_dtt_and_overnight') && <Icon component={Moon} className="text-purple"/>}
                                                            </Space>
                                                            <small>
                                                                &#8369; {_.round(item.price)}
                                                            </small>
                                                        </div>
                                                    }
                                                />
                                            </Card>
                                        </Col>
                                    ))
                                    : <Alert className="my-4" type="warning" message="Select date of visit to view available products" />
                                }
                            </Row>

                            <Typography.Title level={5} id="rooms" className="mb-2 mt-4">
                                Rooms {dateOfVisit.length && <Button type="primary" icon={<ReloadOutlined />} onClick={() => roomTypeListQuery.refetch()} />}
                            </Typography.Title>

                            { dateOfVisit.length ?
                                <div className="mb-4">
                                    <Col xl={24}>
                                        <Row>
                                            <Col xl={4}>
                                                <div>
                                                    <span className="mr-2 text-seconday"><strong>Hotel filter:</strong></span>
                                                    {
                                                        propertiesData.map(tag => (
                                                            <Tag.CheckableTag
                                                                key={`${tag}`}
                                                                checked={selectedPropertyTags.indexOf(tag) > -1}
                                                                onChange={checked => handlePropertyTagChange(tag, checked)}
                                                            >{tag}</Tag.CheckableTag>
                                                        ))
                                                    }
                                                </div>
                                            </Col>
                                            <Col xl={20}>
                                                <div>
                                                    <span className="mr-2 text-seconday"><strong>Entity allocation filter:</strong></span>
                                                    {
                                                        ['BPO', 
                                                        'HOA', 
                                                        'RE', 
                                                        'OTA',
                                                        'SD Rudolph Cortez',
                                                        'SD Louie Paule',
                                                        'SD Luz Dizon',
                                                        'SD John Rizaldy Zuno',
                                                        'SD Brian Beltran',
                                                        'SD Jake Tuazon',
                                                        'SD Joey Bayon',
                                                        'SD Grace Laxa',
                                                        'SD Stephen Balbin',
                                                        'SD Maripaul Milanes',
                                                        'SD Danny Ngoho',
                                                        'SD Harry Colo',
                                                        'SD Lhot Quiambao'
                                                        ].map(tag => (
                                                            <Tag.CheckableTag
                                                                key={`${tag}`}
                                                                checked={selectedEntityTags.indexOf(tag) > -1}
                                                                onChange={checked => handleEntityTagChange(tag, checked)}
                                                            >{tag}</Tag.CheckableTag>
                                                        ))
                                                    }
                                                </div>
                                            </Col>
                                        </Row>
                                    </Col>
                                    <div>
                                        <span className="text-secondary">Can pax fit rooms:</span> { (selectedRoomTypes.length || selectedPackages.length) && paxFitsRoom.canFit ? <span className="text-success">Yes</span> : <span className="text-danger">No</span> } 
                                    </div>
                                </div>
                                : ''
                            }
                            <Row gutter={[16,16]}>
                                { !dateOfVisit.length ? <Alert className="my-4" type="warning" message="Select date of visit to view available rooms"/> : '' }
                                {
                                    (dateOfVisit.length && roomTypeListQuery.data) ?
                                        _.orderBy(roomTypeListQuery.data, 'entity', 'asc')
                                            .filter((item) => selectedPropertyTags.length ? _.includes(selectedPropertyTags, item.room_type.property.code) : true)
                                            .filter((item) => selectedEntityTags.length ? _.includes(selectedEntityTags, item.entity) : true)
                                            .map( (item, key) => {

                                                // const remaining_rooms = (item.enabled_rooms_count - (item.booked_rooms)) <= 0 ? 0 : (item.enabled_rooms_count - item.booked_rooms);
                                                // const isRoomFullyBooked = (remaining_rooms <= 0);
                                                const selected_room_type = _.find(selectedRoomTypes, { room_type_id: item.room_type.id, entity: item.entity });

                                                return (
                                                    <Col xl={productGridSize} xs={12} key={key}>
                                                        <Card
                                                            bordered={false}
                                                            hoverable={true}
                                                            className={`card-shadow ${_.find(selectedRoomTypes, { room_type_id: item.room_type.id, entity: item.entity }) ? 'new-booking-room-type-selected' : ''}`}
                                                            size="small"
                                                            style={{opacity: isOvernightBookingType && item.available > 0 ? 1 : 0.65}}
                                                            cover={((item.room_type.images) && item.room_type.images.length) ? 
                                                                <Carousel>
                                                                    {
                                                                        item.room_type.images.map( (image, key) => (
                                                                            <div key={key}>
                                                                                <img src={image.image_path} style={{width: '100%'}} />
                                                                            </div>
                                                                        ))
                                                                    }
                                                                </Carousel>
                                                            :
                                                                <img src={img} style={{width: '100%'}} />
                                                            }
                                                            actions={[
                                                                // <small>- / -</small>,
                                                            <small> {(selected_room_type && selected_room_type.quantity) ? selected_room_type.quantity : 0} / {(item.available)}</small>,
                                                                <>
                                                                    { item.available > 0 ?
                                                                        <InputNumber 
                                                                            max={item.available || 0}
                                                                            style={{width: 60}}
                                                                            onChange={e => handleSelectRoomType(item, e)}
                                                                            disabled={!isOvernightBookingType}
                                                                            size="small"
                                                                            min={0}
                                                                            value={selected_room_type ? selected_room_type.quantity : 0}
                                                                            // defaultValue={0}
                                                                        />
                                                                        :
                                                                        <span>Full</span>
                                                                    }
                                                                </>
                                                            ]}
                                                        >
                                                            <Card.Meta
                                                                title={<><span className="text-danger" style={{fontSize: '0.8rem'}}>{item.entity}</span><br/><span style={{fontSize: '0.8rem'}}>{item.room_type.name}</span><br/><small>{item.room_type.property.name}</small></>}
                                                                // description={item.room_type.rack_rate}
                                                                description={<>&#8369;{_.round(item.room_rate_total || item.room_type.rack_rate)}</>}
                                                            />
                                                        </Card>
                                                    </Col>
                                                )
                                            }) : ''
                                }
                            </Row>
                        </Col>
                        <Col xl={24}>
                            <Typography.Title level={5} className="mt-4 mb-2">
                                <Space>
                                    <>Packages</>
                                    <Button type="primary" icon={<ReloadOutlined />} onClick={() => packageListQuery.refetch()} />
                                    <Input onChange={(e) => {
                                        setPackageFilter(e.target.value);
                                    }} placeholder="Search Package" style={{width: 200}} />
                                </Space>
                            </Typography.Title>

                            <div className="mb-2">
                                <span className="mr-2 text-seconday">Filter</span>
                                {
                                    packageTypeData.map(tag => (
                                        <Tag.CheckableTag
                                            key={tag}
                                            checked={selectedPackageTypeTags.indexOf(tag) > -1}
                                            onChange={checked => handlePackageTypeTagChange(tag, checked)}
                                        >{tag}</Tag.CheckableTag>
                                    ))
                                }                    
                                <Checkbox onChange={() => setShowUnavailablePackages(!showUnavailablePackages)}>Include Unavailable Packages</Checkbox>            
                            </div>                            
                            <Row gutter={[16,16]}>
                                {
                                    packageListQuery.isFetching ?
                                        <><Loading isHeightFull={false} className="mr-2" />Packages...</>
                                    :
                                    (newBookingForm.getFieldValue('date_of_visit') && packageListQuery.data) ? packageListQuery.data
                                        .filter((item) => selectedPackageTypeTags.length ? _.includes(selectedPackageTypeTags, item.type):true)
                                        .filter((item) => item.status != 'ended')
                                        .filter( item => showUnavailablePackages ? true : isAvailableToSelect(newBookingForm.getFieldValue('date_of_visit'), item.availability))
                                        .filter((item) => {    
                                            if (!packageFilter) return true;
                                            const re = new RegExp(packageFilter, 'i')                                       
                                            return re.test(item.name) || re.test(item.code) || re.test(item.selling_price)
                                        })
                                        .map( (item, key) => (
                                        <Col xl={productGridSize} xs={12} key={key}>
                                            <Card
                                                bordered={false}
                                                hoverable={true}
                                                className={`card-shadow ${_.includes(_.map(selectedPackages, 'package_id'), item.id) ? 'new-booking-package-selected' : ''}`}
                                                style={{opacity: !isAvailableToSelect(newBookingForm.getFieldValue('date_of_visit'), item.availability) ? 0.65 : 1 }}
                                                size="small"
                                                // cover={<img src={img} style={{width: '100%'}} />}
                                                cover={(item.images && item.images.length) ? 
                                                    <Carousel>
                                                        {
                                                            item.images.map( (image, key) => (
                                                                <div key={key}>
                                                                    <img src={image.image_path} style={{width: '100%'}} />
                                                                </div>
                                                            ))
                                                        }
                                                    </Carousel>
                                                :
                                                    <img src={img} style={{width: '100%'}} />
                                                }
                                                onClick={() => item.type == 'per_guest' ?  handleSelectPackage(item) : false}
                                                // extra={_.includes(selectedProducts, item.id) ? <small>added to booking</small> : ''}
                                                actions={[
                                                    item.type == 'per_booking' ? <><small><strong>qty.</strong></small> <InputNumber onChange={(e)=>handleChangePackageQuantity(item, e)} disabled={!newBookingForm.getFieldValue('date_of_visit') || !isAvailableToSelect(newBookingForm.getFieldValue('date_of_visit'), item.availability)} size="small" min={0} max={item.available} value={_.find(selectedPackages, { package_id: item.id }) ? _.find(selectedPackages, { package_id: item.id }).quantity:0}/></> : <><small>per guest</small></>
                                                ]}
                                            >
                                                <Card.Meta
                                                    title={
                                                        <div style={{fontSize: '0.8rem', whiteSpace: 'pre-line', height: '80px'}} >{item.name}<br/>
                                                            <small>{item.code}</small>
                                                        </div>
                                                    }
                                                    description={
                                                        <div>
                                                            <Space>
                                                                {(item.availability == 'for_dtt' || item.availability == 'for_dtt_and_overnight') && <Icon component={Sun} className="text-yellow"/>}
                                                                {(item.availability == 'for_overnight' || item.availability == 'for_dtt_and_overnight') && <Icon component={Moon} className="text-purple"/>}
                                                                {/* {item.availability == 'for_dtt_and_overnight' && <Space><Icon component={Sun} className="text-yellow"/><Icon component={Moon} className="text-purple"/></Space>} */}
                                                                {item.mode_of_transportation == 'camaya_transportation' && <Icon component={ShipSolid} className="text-primary"/>}
                                                            </Space>
                                                            <br/>
                                                            <small>
                                                                Weekday rate: {item.weekday_rate ? <>&#8369; {_.round(item.weekday_rate)}</> : "N/A"}<br/>
                                                                Weekend rate: &#8369; {_.round(item.weekend_rate)}<br/>
                                                            </small>
                                                        </div>
                                                    }
                                                />
                                            </Card>
                                        </Col>
                                    )) : <Alert className="my-4" type="warning" message="Select date of visit to view available packages" />
                                }
                            </Row>
                        </Col>
                        </>
                        }
                        <Col xl={24}>
                            {
                                modeOfTransportation == 'camaya_transportation' &&
                                <Card>
                                    <Typography.Title level={5} className="mb-2">Ferry Trip Schedules <ReloadOutlined onClick={()=>handleReloadSchedules()}/></Typography.Title>

                                    { ferryOnly ? <></> :
                                        <><div>Arrival</div>
                                        <Table
                                            size="small"
                                            dataSource={arrivalSchedules && arrivalSchedules}
                                            rowKey="seat_segment_id"
                                            rowSelection={{
                                                onSelect: (record, selected) => {
                                                    // console.log(`record: ${record.seat_segment_id}`, 'selected: ', selected);
                                                    
                                                    setFirstTrip(record);
                                                },
                                                getCheckboxProps: record => ({
                                                    disabled: (record.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) || adultPax == null), // Column configuration not to be checked
                                                    // name: record.available,
                                                }),
                                                preserveSelectedRowKeys: false,
                                                type: 'radio',
                                                selectedRowKeys: (firstTrip.available) < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) ? [] : [firstTrip.seat_segment_id],
                                            }}
                                            onRow={(record, rowIndex) => {
                                                return {
                                                onClick: event => (record.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) || adultPax == null) ? false : setFirstTrip(record), // click row
                                                //   onDoubleClick: event => {}, // double click row
                                                //   onContextMenu: event => {}, // right button click row
                                                };
                                            }}
                                            columns={[
                                                {
                                                    title: "Schedule",
                                                    dataIndex: "schedule",
                                                    key: "schedule",
                                                    render: (text, record) => {
                                                        const departure_time = record.trip_date+" "+record.departure_time;
                                                        return <>
                                                            <Typography.Title level={5}>{moment(departure_time).format('h:mm A')}</Typography.Title>
                                                            {moment(record.trip_date).format('MMM D, YYYY')}
                                                            <div style={{fontWeight:'bold'}}>{record.transportation.name}</div>
                                                        </>
                                                    }
                                                },
                                                {
                                                    title: "Available",
                                                    dataIndex: "available",
                                                    key: "available",
                                                    render: (text, record) => <>{record.available} { record.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) ? <small className="text-warning">(over by {(parseInt(adultPax || 0)+parseInt(kidPax || 0)) - record.available} pax)</small> : <></> }</>
                                                },
                                                {
                                                    title: "Origin",
                                                    dataIndex: "origin",
                                                    key: "origin",
                                                },
                                                {
                                                    title: "Destination",
                                                    dataIndex: "destination",
                                                    key: "destination",
                                                },
                                                {
                                                    title: "Seat class",
                                                    dataIndex: "name",
                                                    key: "name",
                                                },
                                                {
                                                    title: "Trip link",
                                                    dataIndex: "trip_link",
                                                    key: "trip_link",
                                                },
                                                {
                                                    title: "Ticket price",
                                                    render: (text, record) => <>&#8369;{record.rate || 0}</>
                                                    // dataIndex: "ticket_price",
                                                    // key: "ticket_price",
                                                },
                                            ]}
                                        /></>
                                    }
                                    <div>Departure</div>
                                    <Table
                                        size="small"
                                        dataSource={departureSchedules && departureSchedules}
                                        rowKey="seat_segment_id"
                                        rowSelection={{
                                            onSelect: (record, selected) => {
                                                console.log(`record: ${record.seat_segment_id}`, 'selected: ', selected);
                                                
                                                setSecondTrip(record);
                                            },
                                            getCheckboxProps: record => ({
                                                disabled: (record.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) || adultPax == null), // Column configuration not to be checked
                                                // name: record.available,
                                            }),
                                            type: 'radio',
                                            selectedRowKeys: (secondTrip.available) < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) ? [] : [secondTrip.seat_segment_id],
                                        }}
                                        onRow={(record, rowIndex) => {
                                            return {
                                              onClick: event => (record.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) || adultPax == null) ? false : setSecondTrip(record), // click row
                                            //   onDoubleClick: event => {}, // double click row
                                            //   onContextMenu: event => {}, // right button click row
                                            };
                                        }}
                                        columns={[
                                            {
                                                title: "Schedule",
                                                dataIndex: "schedule",
                                                key: "schedule",
                                                render: (text, record) => {
                                                    const departure_time = record.trip_date+" "+record.departure_time;
                                                    return <>
                                                        <Typography.Title level={5}>{moment(departure_time).format('h:mm A')}</Typography.Title>
                                                        {moment(record.trip_date).format('MMM D, YYYY')}
                                                        <div style={{fontWeight:'bold'}}>{record.transportation.name}</div>
                                                    </>
                                                }
                                            },
                                            {
                                                title: "Available",
                                                dataIndex: "available",
                                                key: "available",
                                                render: (text, record) => <>{record.available} { record.available < (parseInt(adultPax || 0)+parseInt(kidPax || 0)) ? <small className="text-warning">(over by {(parseInt(adultPax || 0)+parseInt(kidPax || 0)) - record.available} pax)</small> : <></> }</>
                                            },
                                            {
                                                title: "Origin",
                                                dataIndex: "origin",
                                                key: "origin",
                                            },
                                            {
                                                title: "Destination",
                                                dataIndex: "destination",
                                                key: "destination",
                                            },
                                            {
                                                title: "Seat class",
                                                dataIndex: "name",
                                                key: "name",
                                            },
                                            {
                                                title: "Trip link",
                                                dataIndex: "trip_link",
                                                key: "trip_link",
                                            },
                                            {
                                                title: "Ticket price",
                                                render: (text, record) => <>&#8369;{record.rate || 0}</>
                                                // dataIndex: "ticket_price",
                                                // key: "ticket_price",
                                            },
                                        ]}
                                    />


                                    {/* <Row gutter={[16,16]}>
                                        {
                                            db.trip_schedules && db.trip_schedules.map( (item, key) => (
                                                <Col xl={24} xs={24} key={key}>
                                                    <Card
                                                        bordered={false}
                                                        hoverable={true}
                                                        className="card-shadow"
                                                        size="small"
                                                        // cover={<img src={img} style={{width: '100%'}} />}
                                                    >
                                                        <Card.Meta
                                                            title={<>{item.schedule}<br/><small>{item.transportation}</small></>}
                                                            description={100}
                                                        />
                                                    </Card>
                                                </Col>
                                            ))
                                        }
                                    </Row> */}
                                </Card>
                            }
                        </Col>
                    </Row>
                </Col>
                
                <Col xl={24} xs={24}>
                <Typography.Title level={4} className="mt-4 mb-4">Guests</Typography.Title>
              
              { adultPax && <Divider orientation="left" plain>Adult (12 and above years old)</Divider> }
              <Form.List name="adult_guests">
                  {
                      (fields, { add, remove }) => {
                          addPax['adultPax'] = add; removePax['adultPax'] = remove;
                          return (
                              <div>
                                  {fields.map(field => (
                                      <Row key={field.key} gutter={[12,12]}>
                                          <Col xl={5}>
                                              <Form.Item initialValue="adult" name={[field.name, 'type']} fieldKey={[field.fieldKey, 'type']} noStyle>
                                                  <Input type="hidden" />
                                              </Form.Item>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'first_name']}
                                                  fieldKey={[field.fieldKey, 'first_name']}
                                                  rules={[{ required: true, message: `#${field.name+1} Adult first name is required` }]}
                                                //   noStyle
                                              >
                                                  <Input placeholder={`#${field.name + 1} Adult first name`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={5}>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'last_name']}
                                                  fieldKey={[field.fieldKey, 'last_name']}
                                                  rules={[{ required: true, message: `#${field.name+1} Adult last name is required` }]}
                                                //   noStyle
                                              >
                                                  <Input placeholder={`#${field.name + 1} Last name`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={2}>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'age']}
                                                  fieldKey={[field.fieldKey, 'age']}
                                                //   rules={[{ required: true, message: `#${field.name+1} Adult age is required` }]}
                                                  rules={[{ min: 12, type: 'number', message: `#${field.name+1} Adult age must be 12 to 100`}, { max: 100, type: 'number', message: `#${field.name+1} Adult age must be 12 to 100`}, { required: true, message: `#${field.name+1} Adult age is required` }]}
                                                //   noStyle
                                              >
                                                  <InputNumber placeholder={`#${field.name + 1} Age`} />
                                              </Form.Item>
                                          </Col>

                                          <Col xl={5}>
                                            <Form.Item 
                                                {...field}
                                                name={[field.name, 'nationality']}
                                                fieldKey={[field.fieldKey, 'nationality']}
                                                rules={[{ required: true, message: `#${field.name+1} Adult nationality is required` }]}
                                                noStyle
                                            >
                                                <Select
                                                    showSearch
                                                    style={{ width: '100%' }}
                                                    optionFilterProp="children"
                                                    onSearch={onSearch}
                                                    filterOption={(input, option) =>
                                                        option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                    }
                                                    placeholder="Nationality"
                                                >
                                                    <Select.Option value="Afghan">Afghan</Select.Option>
                                                    <Select.Option value="Albanian">Albanian</Select.Option>
                                                    <Select.Option value="Algerian">Algerian</Select.Option>
                                                    <Select.Option value="American">American</Select.Option>
                                                    <Select.Option value="Andorran">Andorran</Select.Option>
                                                    <Select.Option value="Angolan">Angolan</Select.Option>
                                                    <Select.Option value="Anguillan">Anguillan</Select.Option>
                                                    <Select.Option value="Citizen of Antigua and Barbuda">Citizen of Antigua and Barbuda</Select.Option>
                                                    <Select.Option value="Argentine">Argentine</Select.Option>
                                                    <Select.Option value="Armenian">Armenian</Select.Option>
                                                    <Select.Option value="Australian">Australian</Select.Option>
                                                    <Select.Option value="Austrian">Austrian</Select.Option>
                                                    <Select.Option value="Azerbaijani">Azerbaijani</Select.Option>

                                                    <Select.Option value="Bahamian">Bahamian</Select.Option>
                                                    <Select.Option value="Bahraini">Bahraini</Select.Option>
                                                    <Select.Option value="Bangladeshi">Bangladeshi</Select.Option>
                                                    <Select.Option value="Barbadian">Barbadian</Select.Option>
                                                    <Select.Option value="Belarusian">Belarusian</Select.Option>
                                                    <Select.Option value="Belgian">Belgian</Select.Option>
                                                    <Select.Option value="Belizean">Belizean</Select.Option>
                                                    <Select.Option value="Beninese">Beninese</Select.Option>
                                                    <Select.Option value="Bermudian">Bermudian</Select.Option>
                                                    <Select.Option value="Bhutanese">Bhutanese</Select.Option>
                                                    <Select.Option value="Bolivian">Bolivian</Select.Option>
                                                    <Select.Option value="Citizen of Bosnia and Herzegovina">Citizen of Bosnia and Herzegovina</Select.Option>
                                                    <Select.Option value="Botswanan">Botswanan</Select.Option>
                                                    <Select.Option value="Brazilian">Brazilian</Select.Option>
                                                    <Select.Option value="British">British</Select.Option>
                                                    <Select.Option value="British Virgin Islander">British Virgin Islander</Select.Option>
                                                    <Select.Option value="Bruneian">Bruneian</Select.Option>
                                                    <Select.Option value="Bulgarian">Bulgarian</Select.Option>
                                                    <Select.Option value="Burkinan">Burkinan</Select.Option>
                                                    <Select.Option value="Burmese">Burmese</Select.Option>
                                                    <Select.Option value="Burundian">Burundian</Select.Option>

                                                    <Select.Option value="Cambodian">Cambodian</Select.Option>
                                                    <Select.Option value="Cameroonian">Cameroonian</Select.Option>
                                                    <Select.Option value="Canadian">Canadian</Select.Option>
                                                    <Select.Option value="Cape Verdean">Cape Verdean</Select.Option>
                                                    <Select.Option value="Cayman Islander">Cayman Islander</Select.Option>
                                                    <Select.Option value="Central African">Central African</Select.Option>
                                                    <Select.Option value="Chadian">Chadian</Select.Option>
                                                    <Select.Option value="Chilean">Chilean</Select.Option>
                                                    <Select.Option value="Chinese">Chinese</Select.Option>
                                                    <Select.Option value="Colombian">Colombian</Select.Option>
                                                    <Select.Option value="Comoran">Comoran</Select.Option>
                                                    <Select.Option value="Congolese (Congo)">Congolese (Congo)</Select.Option>
                                                    <Select.Option value="Congolese (DRC)">Congolese (DRC)</Select.Option>
                                                    <Select.Option value="Cook Islander">Cook Islander</Select.Option>
                                                    <Select.Option value="Costa Rican">Costa Rican</Select.Option>
                                                    <Select.Option value="Croatian">Croatian</Select.Option>
                                                    <Select.Option value="Cuban">Cuban</Select.Option>
                                                    <Select.Option value="Cymraes">Cymraes</Select.Option>
                                                    <Select.Option value="Cymro">Cymro</Select.Option>
                                                    <Select.Option value="Cypriot">Cypriot</Select.Option>
                                                    <Select.Option value="Czech">Czech</Select.Option>

                                                    <Select.Option value="Danish">Danish</Select.Option>
                                                    <Select.Option value="Djiboutian">Djiboutian</Select.Option>
                                                    <Select.Option value="Dominican">Dominican</Select.Option>
                                                    <Select.Option value="Citizen of the Dominican Republic">Citizen of the Dominican Republic</Select.Option>
                                                    <Select.Option value="Dutch">Dutch</Select.Option>

                                                    <Select.Option value="East Timorese">East Timorese</Select.Option>
                                                    <Select.Option value="Ecuadorean">Ecuadorean</Select.Option>
                                                    <Select.Option value="Egyptian">Egyptian</Select.Option>
                                                    <Select.Option value="Emirati">Emirati</Select.Option>
                                                    <Select.Option value="English">English</Select.Option>
                                                    <Select.Option value="Equatorial Guinean">Equatorial Guinean</Select.Option>
                                                    <Select.Option value="Eritrean">Eritrean</Select.Option>
                                                    <Select.Option value="Estonian">Estonian</Select.Option>
                                                    <Select.Option value="Ethiopian">Ethiopian</Select.Option>

                                                    <Select.Option value="Faroese">Faroese</Select.Option>
                                                    <Select.Option value="Fijian">Fijian</Select.Option>
                                                    <Select.Option value="Filipino">Filipino</Select.Option>
                                                    <Select.Option value="Finnish">Finnish</Select.Option>
                                                    <Select.Option value="French">French</Select.Option>

                                                    <Select.Option value="Gabonese">Gabonese</Select.Option>
                                                    <Select.Option value="Gambian">Gambian</Select.Option>
                                                    <Select.Option value="Georgian">Georgian</Select.Option>
                                                    <Select.Option value="German">German</Select.Option>
                                                    <Select.Option value="Ghanaian">Ghanaian</Select.Option>
                                                    <Select.Option value="Gibraltarian">Gibraltarian</Select.Option>
                                                    <Select.Option value="Greek">Greek</Select.Option>
                                                    <Select.Option value="Greenlandic">Greenlandic</Select.Option>
                                                    <Select.Option value="Grenadian">Grenadian</Select.Option>
                                                    <Select.Option value="Guamanian">Guamanian</Select.Option>
                                                    <Select.Option value="Guatemalan">Guatemalan</Select.Option>
                                                    <Select.Option value="Citizen of Guinea-Bissau">Citizen of Guinea-Bissau</Select.Option>
                                                    <Select.Option value="Guinean">Guinean</Select.Option>
                                                    <Select.Option value="Guyanese">Guyanese</Select.Option>

                                                    <Select.Option value="Haitian">Haitian</Select.Option>
                                                    <Select.Option value="Honduran">Honduran</Select.Option>
                                                    <Select.Option value="Hong Konger">Hong Konger</Select.Option>
                                                    <Select.Option value="Hungarian">Hungarian</Select.Option>

                                                    <Select.Option value="Icelandic">Icelandic</Select.Option>
                                                    <Select.Option value="Indian">Indian</Select.Option>
                                                    <Select.Option value="Indonesian">Indonesian</Select.Option>
                                                    <Select.Option value="Iranian">Iranian</Select.Option>
                                                    <Select.Option value="Iraqi">Iraqi</Select.Option>
                                                    <Select.Option value="Irish">Irish</Select.Option>
                                                    <Select.Option value="Israeli">Israeli</Select.Option>
                                                    <Select.Option value="Italian">Italian</Select.Option>
                                                    <Select.Option value="Ivorian">Ivorian</Select.Option>

                                                    <Select.Option value="Jamaican">Jamaican</Select.Option>
                                                    <Select.Option value="Japanese">Japanese</Select.Option>
                                                    <Select.Option value="Jordanian">Jordanian</Select.Option>

                                                    <Select.Option value="Kazakh">Kazakh</Select.Option>
                                                    <Select.Option value="Kenyan">Kenyan</Select.Option>
                                                    <Select.Option value="Kittitian">Kittitian</Select.Option>
                                                    <Select.Option value="Citizen of Kiribati">Citizen of Kiribati</Select.Option>
                                                    <Select.Option value="Kosovan">Kosovan</Select.Option>
                                                    <Select.Option value="Kuwaiti">Kuwaiti</Select.Option>
                                                    <Select.Option value="Kyrgyz">Kyrgyz</Select.Option>

                                                    <Select.Option value="Lao">Lao</Select.Option>
                                                    <Select.Option value="Latvian">Latvian</Select.Option>
                                                    <Select.Option value="Lebanese">Lebanese</Select.Option>
                                                    <Select.Option value="Liberian">Liberian</Select.Option>
                                                    <Select.Option value="Libyan">Libyan</Select.Option>
                                                    <Select.Option value="Liechtenstein Citizen">Liechtenstein Citizen</Select.Option>
                                                    <Select.Option value="Lithuanian">Lithuanian</Select.Option>
                                                    <Select.Option value="Luxembourger">Luxembourger</Select.Option>

                                                    <Select.Option value="Macanese">Macanese</Select.Option>
                                                    <Select.Option value="Macedonian">Macedonian</Select.Option>
                                                    <Select.Option value="Malagasy">Malagasy</Select.Option>
                                                    <Select.Option value="Malawian">Malawian</Select.Option>
                                                    <Select.Option value="Malaysian">Malaysian</Select.Option>
                                                    <Select.Option value="Maldivian">Maldivian</Select.Option>
                                                    <Select.Option value="Malian">Malian</Select.Option>
                                                    <Select.Option value="Maltese">Maltese</Select.Option>
                                                    <Select.Option value="Marshallese">Marshallese</Select.Option>
                                                    <Select.Option value="Martiniquais">Martiniquais</Select.Option>
                                                    <Select.Option value="Mauritanian">Mauritanian</Select.Option>
                                                    <Select.Option value="Mauritian">Mauritian</Select.Option>
                                                    <Select.Option value="Mexican">Mexican</Select.Option>
                                                    <Select.Option value="Micronesian">Micronesian</Select.Option>
                                                    <Select.Option value="Moldovan">Moldovan</Select.Option>
                                                    <Select.Option value="Monegasque">Monegasque</Select.Option>
                                                    <Select.Option value="Mongolian">Mongolian</Select.Option>
                                                    <Select.Option value="Montenegrin">Montenegrin</Select.Option>
                                                    <Select.Option value="Montserratian">Montserratian</Select.Option>
                                                    <Select.Option value="Moroccan">Moroccan</Select.Option>
                                                    <Select.Option value="Mosotho">Mosotho</Select.Option>
                                                    <Select.Option value="Mozambican">Mozambican</Select.Option>

                                                    <Select.Option value="Namibian">Namibian</Select.Option>
                                                    <Select.Option value="Nauruan">Nauruan</Select.Option>
                                                    <Select.Option value="Nepalese">Nepalese</Select.Option>
                                                    <Select.Option value="New Zealander">New Zealander</Select.Option>
                                                    <Select.Option value="Nicaraguan">Nicaraguan</Select.Option>
                                                    <Select.Option value="Nigerian">Nigerian</Select.Option>
                                                    <Select.Option value="Nigerien">Nigerien</Select.Option>
                                                    <Select.Option value="Niuean">Niuean</Select.Option>
                                                    <Select.Option value="North Korean">North Korean</Select.Option>
                                                    <Select.Option value="Northern Irish">Northern Irish</Select.Option>
                                                    <Select.Option value="Norwegian">Norwegian</Select.Option>

                                                    <Select.Option value="Omani">Omani</Select.Option>

                                                    <Select.Option value="Pakistani">Pakistani</Select.Option>
                                                    <Select.Option value="Palauan">Palauan</Select.Option>
                                                    <Select.Option value="Palestinian">Palestinian</Select.Option>
                                                    <Select.Option value="Panamanian">Panamanian</Select.Option>
                                                    <Select.Option value="Papua New Guinean">Papua New Guinean</Select.Option>
                                                    <Select.Option value="Paraguayan">Paraguayan</Select.Option>
                                                    <Select.Option value="Peruvian">Peruvian</Select.Option>
                                                    <Select.Option value="Pitcairn Islander">Pitcairn Islander</Select.Option>
                                                    <Select.Option value="Polish">Polish</Select.Option>
                                                    <Select.Option value="Portuguese">Portuguese</Select.Option>
                                                    <Select.Option value="Prydeinig">Prydeinig</Select.Option>
                                                    <Select.Option value="Puerto Rican">Puerto Rican</Select.Option>

                                                    <Select.Option value="Qatari">Qatari</Select.Option>

                                                    <Select.Option value="Romanian">Romanian</Select.Option>
                                                    <Select.Option value="Russian">Russian</Select.Option>
                                                    <Select.Option value="Rwandan">Rwandan</Select.Option>

                                                    <Select.Option value="Salvadorean">Salvadorean</Select.Option>
                                                    <Select.Option value="Sammarinese">Sammarinese</Select.Option>
                                                    <Select.Option value="Samoan">Samoan</Select.Option>
                                                    <Select.Option value="Sao Tomean">Sao Tomean</Select.Option>
                                                    <Select.Option value="Saudi Arabian">Saudi Arabian</Select.Option>
                                                    <Select.Option value="Scottish">Scottish</Select.Option>
                                                    <Select.Option value="Senegalese">Senegalese</Select.Option>
                                                    <Select.Option value="Serbian">Serbian</Select.Option>
                                                    <Select.Option value="Citizen of Seychelles">Citizen of Seychelles</Select.Option>
                                                    <Select.Option value="Sierra Leonean">Sierra Leonean</Select.Option>
                                                    <Select.Option value="Singaporean">Singaporean</Select.Option>
                                                    <Select.Option value="Slovak">Slovak</Select.Option>
                                                    <Select.Option value="Slovenian">Slovenian</Select.Option>
                                                    <Select.Option value="Solomon Islander">Solomon Islander</Select.Option>
                                                    <Select.Option value="Somali">Somali</Select.Option>
                                                    <Select.Option value="South African">South African</Select.Option>
                                                    <Select.Option value="South Korean">South Korean</Select.Option>
                                                    <Select.Option value="South Sudanese">South Sudanese</Select.Option>
                                                    <Select.Option value="Spanish">Spanish</Select.Option>
                                                    <Select.Option value="Sri Lankan">Sri Lankan</Select.Option>
                                                    <Select.Option value="St Helenian">St Helenian</Select.Option>
                                                    <Select.Option value="St Lucian">St Lucian</Select.Option>
                                                    <Select.Option value="Stateless">Stateless</Select.Option>
                                                    <Select.Option value="Sudanese">Sudanese</Select.Option>
                                                    <Select.Option value="Surinamese">Surinamese</Select.Option>
                                                    <Select.Option value="Swazi">Swazi</Select.Option>
                                                    <Select.Option value="Swedish">Nationality</Select.Option>
                                                    <Select.Option value="Swiss">Swiss</Select.Option>
                                                    <Select.Option value="Syrian">Syrian</Select.Option>

                                                    <Select.Option value="Taiwanese">Taiwanese</Select.Option>
                                                    <Select.Option value="Tajik">Tajik</Select.Option>
                                                    <Select.Option value="Tanzanian">Tanzanian</Select.Option>
                                                    <Select.Option value="Thai">Thai</Select.Option>
                                                    <Select.Option value="Togolese">Togolese</Select.Option>
                                                    <Select.Option value="Tongan">Tongan</Select.Option>
                                                    <Select.Option value="Trinidadian">Trinidadian</Select.Option>
                                                    <Select.Option value="Tristanian">Tristanian</Select.Option>
                                                    <Select.Option value="Tunisian">Tunisian</Select.Option>
                                                    <Select.Option value="Turkish">Turkish</Select.Option>
                                                    <Select.Option value="Turkmen">Turkmen</Select.Option>
                                                    <Select.Option value="Turks and Caicos Islander">Turks and Caicos Islander</Select.Option>
                                                    <Select.Option value="Tuvaluan">Tuvaluan</Select.Option>

                                                    <Select.Option value="Ugandan">Ugandan</Select.Option>
                                                    <Select.Option value="Ukrainian">Ukrainian</Select.Option>
                                                    <Select.Option value="Uruguayan">Uruguayan</Select.Option>
                                                    <Select.Option value="Uzbek">Uzbek</Select.Option>

                                                    <Select.Option value="Vatican Citizen">Vatican Citizen</Select.Option>
                                                    <Select.Option value="Citizen of Vanuatu">Citizen of Vanuatu</Select.Option>
                                                    <Select.Option value="Venezuelan">Venezuelan</Select.Option>
                                                    <Select.Option value="Vietnamese">Vietnamese</Select.Option>
                                                    <Select.Option value="Vincentian">Vincentian</Select.Option>

                                                    <Select.Option value="Wallisian">Wallisian</Select.Option>
                                                    <Select.Option value="Welsh">Welsh</Select.Option>

                                                    <Select.Option value="Yemeni">Yemeni</Select.Option>

                                                    <Select.Option value="Zambian">Zambian</Select.Option>
                                                    <Select.Option value="Zimbabwean">Zimbabwean</Select.Option>
                                                </Select>
                                            </Form.Item>
                                          </Col>
                                          
                                          <Col xl={5}>
                                            <Form.Item 
                                                {...field}
                                                name={[field.name, 'guest_tags']}
                                                fieldKey={[field.fieldKey, 'guest_tags']}
                                                // rules={[{ type:'array', defaultField: { type: 'string' }, message: `select only one guest tag`},
                                                //     // {required: true, message: `#${field.name+1} Guest tag is required`}
                                                // ]}
                                            >
                                                <Select 
                                                    placeholder="Guest tag"
                                                    mode="tags"
                                                >
                                                    <Select.Option value="Resident">Resident</Select.Option>
                                                    <Select.Option value="Property Owner">Property Owner</Select.Option>
                                                    <Select.Option value="Investor">Investor</Select.Option>
                                                    <Select.Option value="Accredited RE Agents">Accredited RE Agents</Select.Option>
                                                    <Select.Option value="Supplier">Supplier</Select.Option>
                                                    <Select.Option value="VIP">VIP</Select.Option>
                                                    <Select.Option value="HOA Member">HOA Member</Select.Option>
                                                    <Select.Option value="HOA Member - Dependents">HOA Member Dependents</Select.Option>
                                                    <Select.Option value="HOA Member - Guest">HOA Member Guest</Select.Option>
                                                    <Select.Option value="Golf Member">Golf Member</Select.Option>
                                                    <Select.Option value="Golf Co-Member">Golf Co-Member</Select.Option>
                                                    <Select.Option value="Golf Member's Accompanied">Golf Member's Accompanied</Select.Option>
                                                    <Select.Option value="Golf Guest Member's Unaccompanied">Golf Guest Member's Unaccompanied</Select.Option>
                                                    <Select.Option value="Golf Guest Non-Member">Golf Guest Non-Member</Select.Option>
                                                    <Select.Option value="Property Owner - Non-Member">Property Owner - Non-Member</Select.Option>
                                                    <Select.Option value="Property Owner - HOA Member">Property Owner - HOA Member</Select.Option>
                                                    <Select.Option value="Property Owner - Dependents">Property Owner - Dependents</Select.Option>
                                                    <Select.Option value="Property Owner - Guests">Property Owner - Guests</Select.Option>
                                                </Select>

                                            </Form.Item> 
                                          </Col>
                                          { isGolf &&
                                          <Col xl={5}>
                                              <strong>Golf Tee Time:</strong>
                                            {
                                                        (bookingDays && isGolf) && bookingDays.map( (day, key) => {
                                                            return <Form.Item
                                                                    key={field.name+"_"+key}
                                                                    // {...field}
                                                                    name={[field.name, 'guest_tee_time', key]}
                                                                    fieldKey={[field.fieldKey, 'guest_tee_time', key]}
                                                                    rules={[{ required: true, message: `#${field.name+1} Adult golf tee time is required` }]}
                                                                //   rules={[{ required: true, message: `#${field.name+1} Adult nationality is required` }]}
                                                                //   noStyle
                                                                >
                                                                <Select style={{width: '100%'}} onChange={(value) => handleGuestTeeTimeSelectionChange(day, value, field.name, 'adult')}>
                                                                {/* <Select style={{width: '160px'}}> */}
                                                                    <Select.Option value="">None</Select.Option>
                                                                    {
                                                                        (teeTimeSchedules ? teeTimeSchedules : [])
                                                                        .filter( item => item.date == day)
                                                                        .filter( item => (item.mode_of_transportation == 'all' || item.mode_of_transportation == (modeOfTransportation == 'camaya_transportation' ? 'ferry' : 'land')))
                                                                        .map( (slot, key2) => {

                                                                            const available = (slot.allocation - slot.guests_count) - parseInt(_.filter(guestTeeTime, (item) => (item.tee_time_schedule_id == slot.id)).length);

                                                                            const used_teeTime = (slot.allocation - slot.guests_count) < 4;

                                                                            // return <Select.Option disabled={available <= 0} key={key2} value={slot.id}>{moment(slot.date+' '+slot.time).format('DD MMM Y h:mm A')} ({available} slot{(available) > 1 ? 's' : ''})</Select.Option>

                                                                            return <Select.Option disabled={used_teeTime} key={key2} value={slot.id}>{moment(slot.date+' '+slot.time).format('DD MMM Y h:mm A')} ({available} slot{(available) > 1 ? 's' : ''})</Select.Option>
                                                                        })
                                                                    }
                                                                    {/* <Select.Option>8AM (4 slots)</Select.Option> */}
                                                                </Select>
                                                            </Form.Item>
                                                        })
                                                    }
                                          </Col>
                                            }
                                          <Col xl={1}>
                                             { field.name > 0 && <MinusCircleOutlined
                                                  onClick={() => {
                                                      remove(field.name);
                                                      setAdultPax(prev => {
                                                          newBookingForm.setFieldsValue({ adult_pax: prev - 1 });
                                                          return prev - 1
                                                      });
                                                  }}
                                              />
                                            }
                                          </Col>
                                      </Row>
                                  ))}
                              </div>
                          )
                       }
                  }
              </Form.List> 

              { kidPax > 0 && <Divider orientation="left" plain>Kid (3-11 years old)</Divider> }

              <Form.List name="kid_guests">
                  {
                      (fields, { add, remove }) => {
                          addPax['kidPax'] = add; removePax['kidPax'] = remove;
                          return (
                              <div>
                                  {fields.map(field => (
                                      <Row key={field.key} gutter={[12,12]}>
                                          <Col xl={5}>
                                              <Form.Item initialValue="kid" name={[field.name, 'type']} fieldKey={[field.fieldKey, 'type']} noStyle>
                                                  <Input type="hidden" />
                                              </Form.Item>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'first_name']}
                                                  fieldKey={[field.fieldKey, 'first_name']}
                                                  rules={[{ required: true, message: `#${field.name+1} Kid first name is required` }]}
                                                //   noStyle
                                              >
                                                  <Input placeholder={`#${field.name + 1} Kid first name`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={5}>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'last_name']}
                                                  fieldKey={[field.fieldKey, 'last_name']}
                                                  rules={[{ required: true, message: `#${field.name+1} Kid last name is required` }]}
                                                //   noStyle
                                              >
                                                  <Input placeholder={`#${field.name + 1} Last name`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={2}>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'age']}
                                                  fieldKey={[field.fieldKey, 'age']}
                                                //   rules={[{ required: true, message: `#${field.name+1} Kid age is required` }]}
                                                rules={[{ min: 3, type: 'number', message: `#${field.name+1} Kid age must be 3 - 11`}, { max: 11, type: 'number', message: `#${field.name+1} Kid age must be 3 - 11`},{ required: true, message: `#${field.name+1} Kid age is required` }]}
                                                //   noStyle
                                              >
                                                  <InputNumber placeholder={`#${field.name + 1} Age`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={5}>
                                            <Form.Item 
                                                {...field}
                                                name={[field.name, 'nationality']}
                                                fieldKey={[field.fieldKey, 'nationality']}
                                                rules={[{ required: true, message: `#${field.name+1} Adult nationality is required` }]}
                                                noStyle
                                            >
                                                <Select
                                                    showSearch
                                                    style={{ width: '100%' }}
                                                    optionFilterProp="children"
                                                    onSearch={onSearch}
                                                    filterOption={(input, option) =>
                                                        option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                    }
                                                    placeholder="Nationality"
                                                >
                                                    <Select.Option value="Afghan">Afghan</Select.Option>
                                                    <Select.Option value="Albanian">Albanian</Select.Option>
                                                    <Select.Option value="Algerian">Algerian</Select.Option>
                                                    <Select.Option value="American">American</Select.Option>
                                                    <Select.Option value="Andorran">Andorran</Select.Option>
                                                    <Select.Option value="Angolan">Angolan</Select.Option>
                                                    <Select.Option value="Anguillan">Anguillan</Select.Option>
                                                    <Select.Option value="Citizen of Antigua and Barbuda">Citizen of Antigua and Barbuda</Select.Option>
                                                    <Select.Option value="Argentine">Argentine</Select.Option>
                                                    <Select.Option value="Armenian">Armenian</Select.Option>
                                                    <Select.Option value="Australian">Australian</Select.Option>
                                                    <Select.Option value="Austrian">Austrian</Select.Option>
                                                    <Select.Option value="Azerbaijani">Azerbaijani</Select.Option>

                                                    <Select.Option value="Bahamian">Bahamian</Select.Option>
                                                    <Select.Option value="Bahraini">Bahraini</Select.Option>
                                                    <Select.Option value="Bangladeshi">Bangladeshi</Select.Option>
                                                    <Select.Option value="Barbadian">Barbadian</Select.Option>
                                                    <Select.Option value="Belarusian">Belarusian</Select.Option>
                                                    <Select.Option value="Belgian">Belgian</Select.Option>
                                                    <Select.Option value="Belizean">Belizean</Select.Option>
                                                    <Select.Option value="Beninese">Beninese</Select.Option>
                                                    <Select.Option value="Bermudian">Bermudian</Select.Option>
                                                    <Select.Option value="Bhutanese">Bhutanese</Select.Option>
                                                    <Select.Option value="Bolivian">Bolivian</Select.Option>
                                                    <Select.Option value="Citizen of Bosnia and Herzegovina">Citizen of Bosnia and Herzegovina</Select.Option>
                                                    <Select.Option value="Botswanan">Botswanan</Select.Option>
                                                    <Select.Option value="Brazilian">Brazilian</Select.Option>
                                                    <Select.Option value="British">British</Select.Option>
                                                    <Select.Option value="British Virgin Islander">British Virgin Islander</Select.Option>
                                                    <Select.Option value="Bruneian">Bruneian</Select.Option>
                                                    <Select.Option value="Bulgarian">Bulgarian</Select.Option>
                                                    <Select.Option value="Burkinan">Burkinan</Select.Option>
                                                    <Select.Option value="Burmese">Burmese</Select.Option>
                                                    <Select.Option value="Burundian">Burundian</Select.Option>

                                                    <Select.Option value="Cambodian">Cambodian</Select.Option>
                                                    <Select.Option value="Cameroonian">Cameroonian</Select.Option>
                                                    <Select.Option value="Canadian">Canadian</Select.Option>
                                                    <Select.Option value="Cape Verdean">Cape Verdean</Select.Option>
                                                    <Select.Option value="Cayman Islander">Cayman Islander</Select.Option>
                                                    <Select.Option value="Central African">Central African</Select.Option>
                                                    <Select.Option value="Chadian">Chadian</Select.Option>
                                                    <Select.Option value="Chilean">Chilean</Select.Option>
                                                    <Select.Option value="Chinese">Chinese</Select.Option>
                                                    <Select.Option value="Colombian">Colombian</Select.Option>
                                                    <Select.Option value="Comoran">Comoran</Select.Option>
                                                    <Select.Option value="Congolese (Congo)">Congolese (Congo)</Select.Option>
                                                    <Select.Option value="Congolese (DRC)">Congolese (DRC)</Select.Option>
                                                    <Select.Option value="Cook Islander">Cook Islander</Select.Option>
                                                    <Select.Option value="Costa Rican">Costa Rican</Select.Option>
                                                    <Select.Option value="Croatian">Croatian</Select.Option>
                                                    <Select.Option value="Cuban">Cuban</Select.Option>
                                                    <Select.Option value="Cymraes">Cymraes</Select.Option>
                                                    <Select.Option value="Cymro">Cymro</Select.Option>
                                                    <Select.Option value="Cypriot">Cypriot</Select.Option>
                                                    <Select.Option value="Czech">Czech</Select.Option>

                                                    <Select.Option value="Danish">Danish</Select.Option>
                                                    <Select.Option value="Djiboutian">Djiboutian</Select.Option>
                                                    <Select.Option value="Dominican">Dominican</Select.Option>
                                                    <Select.Option value="Citizen of the Dominican Republic">Citizen of the Dominican Republic</Select.Option>
                                                    <Select.Option value="Dutch">Dutch</Select.Option>

                                                    <Select.Option value="East Timorese">East Timorese</Select.Option>
                                                    <Select.Option value="Ecuadorean">Ecuadorean</Select.Option>
                                                    <Select.Option value="Egyptian">Egyptian</Select.Option>
                                                    <Select.Option value="Emirati">Emirati</Select.Option>
                                                    <Select.Option value="English">English</Select.Option>
                                                    <Select.Option value="Equatorial Guinean">Equatorial Guinean</Select.Option>
                                                    <Select.Option value="Eritrean">Eritrean</Select.Option>
                                                    <Select.Option value="Estonian">Estonian</Select.Option>
                                                    <Select.Option value="Ethiopian">Ethiopian</Select.Option>

                                                    <Select.Option value="Faroese">Faroese</Select.Option>
                                                    <Select.Option value="Fijian">Fijian</Select.Option>
                                                    <Select.Option value="Filipino">Filipino</Select.Option>
                                                    <Select.Option value="Finnish">Finnish</Select.Option>
                                                    <Select.Option value="French">French</Select.Option>

                                                    <Select.Option value="Gabonese">Gabonese</Select.Option>
                                                    <Select.Option value="Gambian">Gambian</Select.Option>
                                                    <Select.Option value="Georgian">Georgian</Select.Option>
                                                    <Select.Option value="German">German</Select.Option>
                                                    <Select.Option value="Ghanaian">Ghanaian</Select.Option>
                                                    <Select.Option value="Gibraltarian">Gibraltarian</Select.Option>
                                                    <Select.Option value="Greek">Greek</Select.Option>
                                                    <Select.Option value="Greenlandic">Greenlandic</Select.Option>
                                                    <Select.Option value="Grenadian">Grenadian</Select.Option>
                                                    <Select.Option value="Guamanian">Guamanian</Select.Option>
                                                    <Select.Option value="Guatemalan">Guatemalan</Select.Option>
                                                    <Select.Option value="Citizen of Guinea-Bissau">Citizen of Guinea-Bissau</Select.Option>
                                                    <Select.Option value="Guinean">Guinean</Select.Option>
                                                    <Select.Option value="Guyanese">Guyanese</Select.Option>

                                                    <Select.Option value="Haitian">Haitian</Select.Option>
                                                    <Select.Option value="Honduran">Honduran</Select.Option>
                                                    <Select.Option value="Hong Konger">Hong Konger</Select.Option>
                                                    <Select.Option value="Hungarian">Hungarian</Select.Option>

                                                    <Select.Option value="Icelandic">Icelandic</Select.Option>
                                                    <Select.Option value="Indian">Indian</Select.Option>
                                                    <Select.Option value="Indonesian">Indonesian</Select.Option>
                                                    <Select.Option value="Iranian">Iranian</Select.Option>
                                                    <Select.Option value="Iraqi">Iraqi</Select.Option>
                                                    <Select.Option value="Irish">Irish</Select.Option>
                                                    <Select.Option value="Israeli">Israeli</Select.Option>
                                                    <Select.Option value="Italian">Italian</Select.Option>
                                                    <Select.Option value="Ivorian">Ivorian</Select.Option>

                                                    <Select.Option value="Jamaican">Jamaican</Select.Option>
                                                    <Select.Option value="Japanese">Japanese</Select.Option>
                                                    <Select.Option value="Jordanian">Jordanian</Select.Option>

                                                    <Select.Option value="Kazakh">Kazakh</Select.Option>
                                                    <Select.Option value="Kenyan">Kenyan</Select.Option>
                                                    <Select.Option value="Kittitian">Kittitian</Select.Option>
                                                    <Select.Option value="Citizen of Kiribati">Citizen of Kiribati</Select.Option>
                                                    <Select.Option value="Kosovan">Kosovan</Select.Option>
                                                    <Select.Option value="Kuwaiti">Kuwaiti</Select.Option>
                                                    <Select.Option value="Kyrgyz">Kyrgyz</Select.Option>

                                                    <Select.Option value="Lao">Lao</Select.Option>
                                                    <Select.Option value="Latvian">Latvian</Select.Option>
                                                    <Select.Option value="Lebanese">Lebanese</Select.Option>
                                                    <Select.Option value="Liberian">Liberian</Select.Option>
                                                    <Select.Option value="Libyan">Libyan</Select.Option>
                                                    <Select.Option value="Liechtenstein Citizen">Liechtenstein Citizen</Select.Option>
                                                    <Select.Option value="Lithuanian">Lithuanian</Select.Option>
                                                    <Select.Option value="Luxembourger">Luxembourger</Select.Option>

                                                    <Select.Option value="Macanese">Macanese</Select.Option>
                                                    <Select.Option value="Macedonian">Macedonian</Select.Option>
                                                    <Select.Option value="Malagasy">Malagasy</Select.Option>
                                                    <Select.Option value="Malawian">Malawian</Select.Option>
                                                    <Select.Option value="Malaysian">Malaysian</Select.Option>
                                                    <Select.Option value="Maldivian">Maldivian</Select.Option>
                                                    <Select.Option value="Malian">Malian</Select.Option>
                                                    <Select.Option value="Maltese">Maltese</Select.Option>
                                                    <Select.Option value="Marshallese">Marshallese</Select.Option>
                                                    <Select.Option value="Martiniquais">Martiniquais</Select.Option>
                                                    <Select.Option value="Mauritanian">Mauritanian</Select.Option>
                                                    <Select.Option value="Mauritian">Mauritian</Select.Option>
                                                    <Select.Option value="Mexican">Mexican</Select.Option>
                                                    <Select.Option value="Micronesian">Micronesian</Select.Option>
                                                    <Select.Option value="Moldovan">Moldovan</Select.Option>
                                                    <Select.Option value="Monegasque">Monegasque</Select.Option>
                                                    <Select.Option value="Mongolian">Mongolian</Select.Option>
                                                    <Select.Option value="Montenegrin">Montenegrin</Select.Option>
                                                    <Select.Option value="Montserratian">Montserratian</Select.Option>
                                                    <Select.Option value="Moroccan">Moroccan</Select.Option>
                                                    <Select.Option value="Mosotho">Mosotho</Select.Option>
                                                    <Select.Option value="Mozambican">Mozambican</Select.Option>

                                                    <Select.Option value="Namibian">Namibian</Select.Option>
                                                    <Select.Option value="Nauruan">Nauruan</Select.Option>
                                                    <Select.Option value="Nepalese">Nepalese</Select.Option>
                                                    <Select.Option value="New Zealander">New Zealander</Select.Option>
                                                    <Select.Option value="Nicaraguan">Nicaraguan</Select.Option>
                                                    <Select.Option value="Nigerian">Nigerian</Select.Option>
                                                    <Select.Option value="Nigerien">Nigerien</Select.Option>
                                                    <Select.Option value="Niuean">Niuean</Select.Option>
                                                    <Select.Option value="North Korean">North Korean</Select.Option>
                                                    <Select.Option value="Northern Irish">Northern Irish</Select.Option>
                                                    <Select.Option value="Norwegian">Norwegian</Select.Option>

                                                    <Select.Option value="Omani">Omani</Select.Option>

                                                    <Select.Option value="Pakistani">Pakistani</Select.Option>
                                                    <Select.Option value="Palauan">Palauan</Select.Option>
                                                    <Select.Option value="Palestinian">Palestinian</Select.Option>
                                                    <Select.Option value="Panamanian">Panamanian</Select.Option>
                                                    <Select.Option value="Papua New Guinean">Papua New Guinean</Select.Option>
                                                    <Select.Option value="Paraguayan">Paraguayan</Select.Option>
                                                    <Select.Option value="Peruvian">Peruvian</Select.Option>
                                                    <Select.Option value="Pitcairn Islander">Pitcairn Islander</Select.Option>
                                                    <Select.Option value="Polish">Polish</Select.Option>
                                                    <Select.Option value="Portuguese">Portuguese</Select.Option>
                                                    <Select.Option value="Prydeinig">Prydeinig</Select.Option>
                                                    <Select.Option value="Puerto Rican">Puerto Rican</Select.Option>

                                                    <Select.Option value="Qatari">Qatari</Select.Option>

                                                    <Select.Option value="Romanian">Romanian</Select.Option>
                                                    <Select.Option value="Russian">Russian</Select.Option>
                                                    <Select.Option value="Rwandan">Rwandan</Select.Option>

                                                    <Select.Option value="Salvadorean">Salvadorean</Select.Option>
                                                    <Select.Option value="Sammarinese">Sammarinese</Select.Option>
                                                    <Select.Option value="Samoan">Samoan</Select.Option>
                                                    <Select.Option value="Sao Tomean">Sao Tomean</Select.Option>
                                                    <Select.Option value="Saudi Arabian">Saudi Arabian</Select.Option>
                                                    <Select.Option value="Scottish">Scottish</Select.Option>
                                                    <Select.Option value="Senegalese">Senegalese</Select.Option>
                                                    <Select.Option value="Serbian">Serbian</Select.Option>
                                                    <Select.Option value="Citizen of Seychelles">Citizen of Seychelles</Select.Option>
                                                    <Select.Option value="Sierra Leonean">Sierra Leonean</Select.Option>
                                                    <Select.Option value="Singaporean">Singaporean</Select.Option>
                                                    <Select.Option value="Slovak">Slovak</Select.Option>
                                                    <Select.Option value="Slovenian">Slovenian</Select.Option>
                                                    <Select.Option value="Solomon Islander">Solomon Islander</Select.Option>
                                                    <Select.Option value="Somali">Somali</Select.Option>
                                                    <Select.Option value="South African">South African</Select.Option>
                                                    <Select.Option value="South Korean">South Korean</Select.Option>
                                                    <Select.Option value="South Sudanese">South Sudanese</Select.Option>
                                                    <Select.Option value="Spanish">Spanish</Select.Option>
                                                    <Select.Option value="Sri Lankan">Sri Lankan</Select.Option>
                                                    <Select.Option value="St Helenian">St Helenian</Select.Option>
                                                    <Select.Option value="St Lucian">St Lucian</Select.Option>
                                                    <Select.Option value="Stateless">Stateless</Select.Option>
                                                    <Select.Option value="Sudanese">Sudanese</Select.Option>
                                                    <Select.Option value="Surinamese">Surinamese</Select.Option>
                                                    <Select.Option value="Swazi">Swazi</Select.Option>
                                                    <Select.Option value="Swedish">Nationality</Select.Option>
                                                    <Select.Option value="Swiss">Swiss</Select.Option>
                                                    <Select.Option value="Syrian">Syrian</Select.Option>

                                                    <Select.Option value="Taiwanese">Taiwanese</Select.Option>
                                                    <Select.Option value="Tajik">Tajik</Select.Option>
                                                    <Select.Option value="Tanzanian">Tanzanian</Select.Option>
                                                    <Select.Option value="Thai">Thai</Select.Option>
                                                    <Select.Option value="Togolese">Togolese</Select.Option>
                                                    <Select.Option value="Tongan">Tongan</Select.Option>
                                                    <Select.Option value="Trinidadian">Trinidadian</Select.Option>
                                                    <Select.Option value="Tristanian">Tristanian</Select.Option>
                                                    <Select.Option value="Tunisian">Tunisian</Select.Option>
                                                    <Select.Option value="Turkish">Turkish</Select.Option>
                                                    <Select.Option value="Turkmen">Turkmen</Select.Option>
                                                    <Select.Option value="Turks and Caicos Islander">Turks and Caicos Islander</Select.Option>
                                                    <Select.Option value="Tuvaluan">Tuvaluan</Select.Option>

                                                    <Select.Option value="Ugandan">Ugandan</Select.Option>
                                                    <Select.Option value="Ukrainian">Ukrainian</Select.Option>
                                                    <Select.Option value="Uruguayan">Uruguayan</Select.Option>
                                                    <Select.Option value="Uzbek">Uzbek</Select.Option>

                                                    <Select.Option value="Vatican Citizen">Vatican Citizen</Select.Option>
                                                    <Select.Option value="Citizen of Vanuatu">Citizen of Vanuatu</Select.Option>
                                                    <Select.Option value="Venezuelan">Venezuelan</Select.Option>
                                                    <Select.Option value="Vietnamese">Vietnamese</Select.Option>
                                                    <Select.Option value="Vincentian">Vincentian</Select.Option>

                                                    <Select.Option value="Wallisian">Wallisian</Select.Option>
                                                    <Select.Option value="Welsh">Welsh</Select.Option>

                                                    <Select.Option value="Yemeni">Yemeni</Select.Option>

                                                    <Select.Option value="Zambian">Zambian</Select.Option>
                                                    <Select.Option value="Zimbabwean">Zimbabwean</Select.Option>
                                                </Select>
                                            </Form.Item>
                                          </Col>
                                          <Col xl={4}>
                                            <Form.Item 
                                                {...field}
                                                name={[field.name, 'guest_tags']}
                                                fieldKey={[field.fieldKey, 'guest_tags']}
                                                // rules={[{ type:'array', defaultField: { type: 'string' }, message: `select only one guest tag`},
                                                    // {required: true, message: `#${field.name+1} Guest tag is required`}
                                                // ]}
                                            >
                                                <Select 
                                                    placeholder="Guest tag"
                                                    mode="tags"
                                                >
                                                    <Select.Option value="Resident">Resident</Select.Option>
                                                    <Select.Option value="Property Owner">Property Owner</Select.Option>
                                                    <Select.Option value="Investor">Investor</Select.Option>
                                                    <Select.Option value="Accredited RE Agents">Accredited RE Agents</Select.Option>
                                                    <Select.Option value="Supplier">Supplier</Select.Option>
                                                    <Select.Option value="VIP">VIP</Select.Option>
                                                    <Select.Option value="HOA Member">HOA Member</Select.Option>
                                                    <Select.Option value="HOA Member - Dependents">HOA Member - Dependents</Select.Option>
                                                    <Select.Option value="HOA Member - Guest">HOA Member - Guest</Select.Option>
                                                    <Select.Option value="Golf Member">Golf Member</Select.Option>
                                                    <Select.Option value="Golf Co-Member">Golf Co-Member</Select.Option>
                                                    <Select.Option value="Golf Member's Accompanied">Golf Member's Accompanied</Select.Option>
                                                    <Select.Option value="Golf Guest Member's Unaccompanied">Golf Guest Member's Unaccompanied</Select.Option>
                                                    <Select.Option value="Golf Guest Non-Member">Golf Guest Non-Member</Select.Option>
                                                    <Select.Option value="Property Owner - Non-Member">Property Owner - Non-Member</Select.Option>
                                                    <Select.Option value="Property Owner - HOA Member">Property Owner - HOA Member</Select.Option>
                                                    <Select.Option value="Property Owner - Dependents">Property Owner - Dependents</Select.Option>
                                                    <Select.Option value="Property Owner - Guests">Property Owner - Guests</Select.Option>
                                                </Select>
                                            </Form.Item> 
                                          </Col>
                                          { isGolf &&
                                          <Col xl={5}>
                                              <strong>Golf Tee Time:</strong>
                                            {
                                                        (bookingDays && isGolf) && bookingDays.map( (day, key) => {
                                                            return <Form.Item
                                                                    key={field.name+"_"+key}
                                                                    // {...field}
                                                                    name={[field.name, 'guest_tee_time', key]}
                                                                    fieldKey={[field.fieldKey, 'guest_tee_time', key]}
                                                                    rules={[{ required: true, message: `#${field.name+1} Kid golf tee time is required` }]}
                                                                //   noStyle
                                                                >
                                                                <Select style={{width: '100%'}} onChange={(value) => handleGuestTeeTimeSelectionChange(day, value, field.name, 'kid')}>
                                                                {/* <Select style={{width: '160px'}}> */}
                                                                    <Select.Option value="">None</Select.Option>
                                                                    {
                                                                        (teeTimeSchedules ? teeTimeSchedules : [])
                                                                        .filter( item => item.date == day)
                                                                        .filter( item => (item.mode_of_transportation == 'all' || item.mode_of_transportation == (modeOfTransportation == 'camaya_transportation' ? 'ferry' : 'land')))
                                                                        .map( (slot, key2) => {

                                                                            const available = (slot.allocation - slot.guests_count) - parseInt(_.filter(guestTeeTime, (item) => (item.tee_time_schedule_id == slot.id)).length);

                                                                            const used_teeTime = (slot.allocation - slot.guests_count) < 4;

                                                                            // return <Select.Option disabled={available <= 0} key={key2} value={slot.id}>{moment(slot.date+' '+slot.time).format('DD MMM Y h:mm A')} ({available} slot{(available) > 1 ? 's' : ''})</Select.Option>

                                                                            return <Select.Option disabled={used_teeTime} key={key2} value={slot.id}>{moment(slot.date+' '+slot.time).format('DD MMM Y h:mm A')} ({available} slot{(available) > 1 ? 's' : ''})</Select.Option>
                                                                        })
                                                                    }
                                                                    {/* <Select.Option>8AM (4 slots)</Select.Option> */}
                                                                </Select>
                                                            </Form.Item>
                                                        })
                                                    }
                                          </Col>
                                            }
                                          <Col xl={1}>
                                              <MinusCircleOutlined
                                                  onClick={() => {
                                                      remove(field.name);
                                                      setKidPax(prev => {
                                                          newBookingForm.setFieldsValue({ kid_pax: prev - 1 });
                                                          return prev - 1
                                                      });
                                                  }}
                                              />
                                          </Col>
                                      </Row>
                                  ))}
                              </div>
                          )
                       }
                  }
              </Form.List>

              { infantPax > 0 && <Divider orientation="left" plain>Infant (0-2 years old)</Divider> }           

              <Form.List name="infant_guests">
                  {
                      (fields, { add, remove }) => {
                          addPax['infantPax'] = add; removePax['infantPax'] = remove;
                          return (
                              <div>
                                  {fields.map(field => (
                                      <Row key={field.key} gutter={[12,12]}>
                                          <Col xl={5}>
                                              <Form.Item initialValue="infant" name={[field.name, 'type']} fieldKey={[field.fieldKey, 'type']} noStyle>
                                                  <Input type="hidden" />
                                              </Form.Item>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'first_name']}
                                                  fieldKey={[field.fieldKey, 'first_name']}
                                                  rules={[{ required: true, message: `#${field.name+1} Infant first name is required` }]}
                                                //   noStyle
                                              >
                                                  <Input placeholder={`#${field.name + 1} Infant first name`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={5}>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'last_name']}
                                                  fieldKey={[field.fieldKey, 'last_name']}
                                                  rules={[{ required: true, message: `#${field.name+1} Infant last name is required` }]}
                                                //   noStyle
                                              >
                                                  <Input placeholder={`#${field.name + 1} Last name`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={2}>
                                              <Form.Item
                                                  {...field}
                                                  name={[field.name, 'age']}
                                                  fieldKey={[field.fieldKey, 'age']}
                                                    // rules={[{ required: true, message: `#${field.name+1} Infant age is required` }]}
                                                    rules={[{ min: 0, type: 'number', message: `#${field.name+1} Infant age must be 0 - 2`}, { max: 2, type: 'number', message: `#${field.name+1} Infant age must be 0 - 2`},{ required: true, message: `#${field.name+1} Infant age is required` }]}
                                                //   noStyle
                                              >
                                                  <InputNumber placeholder={`#${field.name + 1} Age`} />
                                              </Form.Item>
                                          </Col>
                                          <Col xl={5}>
                                            <Form.Item 
                                                {...field}
                                                name={[field.name, 'nationality']}
                                                fieldKey={[field.fieldKey, 'nationality']}
                                                rules={[{ required: true, message: `#${field.name+1} Adult nationality is required` }]}
                                                noStyle
                                            >
                                                <Select
                                                    showSearch
                                                    style={{ width: '100%' }}
                                                    optionFilterProp="children"
                                                    onSearch={onSearch}
                                                    filterOption={(input, option) =>
                                                        option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                    }
                                                    placeholder="Nationality"
                                                >
                                                    <Select.Option value="Afghan">Afghan</Select.Option>
                                                    <Select.Option value="Albanian">Albanian</Select.Option>
                                                    <Select.Option value="Algerian">Algerian</Select.Option>
                                                    <Select.Option value="American">American</Select.Option>
                                                    <Select.Option value="Andorran">Andorran</Select.Option>
                                                    <Select.Option value="Angolan">Angolan</Select.Option>
                                                    <Select.Option value="Anguillan">Anguillan</Select.Option>
                                                    <Select.Option value="Citizen of Antigua and Barbuda">Citizen of Antigua and Barbuda</Select.Option>
                                                    <Select.Option value="Argentine">Argentine</Select.Option>
                                                    <Select.Option value="Armenian">Armenian</Select.Option>
                                                    <Select.Option value="Australian">Australian</Select.Option>
                                                    <Select.Option value="Austrian">Austrian</Select.Option>
                                                    <Select.Option value="Azerbaijani">Azerbaijani</Select.Option>

                                                    <Select.Option value="Bahamian">Bahamian</Select.Option>
                                                    <Select.Option value="Bahraini">Bahraini</Select.Option>
                                                    <Select.Option value="Bangladeshi">Bangladeshi</Select.Option>
                                                    <Select.Option value="Barbadian">Barbadian</Select.Option>
                                                    <Select.Option value="Belarusian">Belarusian</Select.Option>
                                                    <Select.Option value="Belgian">Belgian</Select.Option>
                                                    <Select.Option value="Belizean">Belizean</Select.Option>
                                                    <Select.Option value="Beninese">Beninese</Select.Option>
                                                    <Select.Option value="Bermudian">Bermudian</Select.Option>
                                                    <Select.Option value="Bhutanese">Bhutanese</Select.Option>
                                                    <Select.Option value="Bolivian">Bolivian</Select.Option>
                                                    <Select.Option value="Citizen of Bosnia and Herzegovina">Citizen of Bosnia and Herzegovina</Select.Option>
                                                    <Select.Option value="Botswanan">Botswanan</Select.Option>
                                                    <Select.Option value="Brazilian">Brazilian</Select.Option>
                                                    <Select.Option value="British">British</Select.Option>
                                                    <Select.Option value="British Virgin Islander">British Virgin Islander</Select.Option>
                                                    <Select.Option value="Bruneian">Bruneian</Select.Option>
                                                    <Select.Option value="Bulgarian">Bulgarian</Select.Option>
                                                    <Select.Option value="Burkinan">Burkinan</Select.Option>
                                                    <Select.Option value="Burmese">Burmese</Select.Option>
                                                    <Select.Option value="Burundian">Burundian</Select.Option>

                                                    <Select.Option value="Cambodian">Cambodian</Select.Option>
                                                    <Select.Option value="Cameroonian">Cameroonian</Select.Option>
                                                    <Select.Option value="Canadian">Canadian</Select.Option>
                                                    <Select.Option value="Cape Verdean">Cape Verdean</Select.Option>
                                                    <Select.Option value="Cayman Islander">Cayman Islander</Select.Option>
                                                    <Select.Option value="Central African">Central African</Select.Option>
                                                    <Select.Option value="Chadian">Chadian</Select.Option>
                                                    <Select.Option value="Chilean">Chilean</Select.Option>
                                                    <Select.Option value="Chinese">Chinese</Select.Option>
                                                    <Select.Option value="Colombian">Colombian</Select.Option>
                                                    <Select.Option value="Comoran">Comoran</Select.Option>
                                                    <Select.Option value="Congolese (Congo)">Congolese (Congo)</Select.Option>
                                                    <Select.Option value="Congolese (DRC)">Congolese (DRC)</Select.Option>
                                                    <Select.Option value="Cook Islander">Cook Islander</Select.Option>
                                                    <Select.Option value="Costa Rican">Costa Rican</Select.Option>
                                                    <Select.Option value="Croatian">Croatian</Select.Option>
                                                    <Select.Option value="Cuban">Cuban</Select.Option>
                                                    <Select.Option value="Cymraes">Cymraes</Select.Option>
                                                    <Select.Option value="Cymro">Cymro</Select.Option>
                                                    <Select.Option value="Cypriot">Cypriot</Select.Option>
                                                    <Select.Option value="Czech">Czech</Select.Option>

                                                    <Select.Option value="Danish">Danish</Select.Option>
                                                    <Select.Option value="Djiboutian">Djiboutian</Select.Option>
                                                    <Select.Option value="Dominican">Dominican</Select.Option>
                                                    <Select.Option value="Citizen of the Dominican Republic">Citizen of the Dominican Republic</Select.Option>
                                                    <Select.Option value="Dutch">Dutch</Select.Option>

                                                    <Select.Option value="East Timorese">East Timorese</Select.Option>
                                                    <Select.Option value="Ecuadorean">Ecuadorean</Select.Option>
                                                    <Select.Option value="Egyptian">Egyptian</Select.Option>
                                                    <Select.Option value="Emirati">Emirati</Select.Option>
                                                    <Select.Option value="English">English</Select.Option>
                                                    <Select.Option value="Equatorial Guinean">Equatorial Guinean</Select.Option>
                                                    <Select.Option value="Eritrean">Eritrean</Select.Option>
                                                    <Select.Option value="Estonian">Estonian</Select.Option>
                                                    <Select.Option value="Ethiopian">Ethiopian</Select.Option>

                                                    <Select.Option value="Faroese">Faroese</Select.Option>
                                                    <Select.Option value="Fijian">Fijian</Select.Option>
                                                    <Select.Option value="Filipino">Filipino</Select.Option>
                                                    <Select.Option value="Finnish">Finnish</Select.Option>
                                                    <Select.Option value="French">French</Select.Option>

                                                    <Select.Option value="Gabonese">Gabonese</Select.Option>
                                                    <Select.Option value="Gambian">Gambian</Select.Option>
                                                    <Select.Option value="Georgian">Georgian</Select.Option>
                                                    <Select.Option value="German">German</Select.Option>
                                                    <Select.Option value="Ghanaian">Ghanaian</Select.Option>
                                                    <Select.Option value="Gibraltarian">Gibraltarian</Select.Option>
                                                    <Select.Option value="Greek">Greek</Select.Option>
                                                    <Select.Option value="Greenlandic">Greenlandic</Select.Option>
                                                    <Select.Option value="Grenadian">Grenadian</Select.Option>
                                                    <Select.Option value="Guamanian">Guamanian</Select.Option>
                                                    <Select.Option value="Guatemalan">Guatemalan</Select.Option>
                                                    <Select.Option value="Citizen of Guinea-Bissau">Citizen of Guinea-Bissau</Select.Option>
                                                    <Select.Option value="Guinean">Guinean</Select.Option>
                                                    <Select.Option value="Guyanese">Guyanese</Select.Option>

                                                    <Select.Option value="Haitian">Haitian</Select.Option>
                                                    <Select.Option value="Honduran">Honduran</Select.Option>
                                                    <Select.Option value="Hong Konger">Hong Konger</Select.Option>
                                                    <Select.Option value="Hungarian">Hungarian</Select.Option>

                                                    <Select.Option value="Icelandic">Icelandic</Select.Option>
                                                    <Select.Option value="Indian">Indian</Select.Option>
                                                    <Select.Option value="Indonesian">Indonesian</Select.Option>
                                                    <Select.Option value="Iranian">Iranian</Select.Option>
                                                    <Select.Option value="Iraqi">Iraqi</Select.Option>
                                                    <Select.Option value="Irish">Irish</Select.Option>
                                                    <Select.Option value="Israeli">Israeli</Select.Option>
                                                    <Select.Option value="Italian">Italian</Select.Option>
                                                    <Select.Option value="Ivorian">Ivorian</Select.Option>

                                                    <Select.Option value="Jamaican">Jamaican</Select.Option>
                                                    <Select.Option value="Japanese">Japanese</Select.Option>
                                                    <Select.Option value="Jordanian">Jordanian</Select.Option>

                                                    <Select.Option value="Kazakh">Kazakh</Select.Option>
                                                    <Select.Option value="Kenyan">Kenyan</Select.Option>
                                                    <Select.Option value="Kittitian">Kittitian</Select.Option>
                                                    <Select.Option value="Citizen of Kiribati">Citizen of Kiribati</Select.Option>
                                                    <Select.Option value="Kosovan">Kosovan</Select.Option>
                                                    <Select.Option value="Kuwaiti">Kuwaiti</Select.Option>
                                                    <Select.Option value="Kyrgyz">Kyrgyz</Select.Option>

                                                    <Select.Option value="Lao">Lao</Select.Option>
                                                    <Select.Option value="Latvian">Latvian</Select.Option>
                                                    <Select.Option value="Lebanese">Lebanese</Select.Option>
                                                    <Select.Option value="Liberian">Liberian</Select.Option>
                                                    <Select.Option value="Libyan">Libyan</Select.Option>
                                                    <Select.Option value="Liechtenstein Citizen">Liechtenstein Citizen</Select.Option>
                                                    <Select.Option value="Lithuanian">Lithuanian</Select.Option>
                                                    <Select.Option value="Luxembourger">Luxembourger</Select.Option>

                                                    <Select.Option value="Macanese">Macanese</Select.Option>
                                                    <Select.Option value="Macedonian">Macedonian</Select.Option>
                                                    <Select.Option value="Malagasy">Malagasy</Select.Option>
                                                    <Select.Option value="Malawian">Malawian</Select.Option>
                                                    <Select.Option value="Malaysian">Malaysian</Select.Option>
                                                    <Select.Option value="Maldivian">Maldivian</Select.Option>
                                                    <Select.Option value="Malian">Malian</Select.Option>
                                                    <Select.Option value="Maltese">Maltese</Select.Option>
                                                    <Select.Option value="Marshallese">Marshallese</Select.Option>
                                                    <Select.Option value="Martiniquais">Martiniquais</Select.Option>
                                                    <Select.Option value="Mauritanian">Mauritanian</Select.Option>
                                                    <Select.Option value="Mauritian">Mauritian</Select.Option>
                                                    <Select.Option value="Mexican">Mexican</Select.Option>
                                                    <Select.Option value="Micronesian">Micronesian</Select.Option>
                                                    <Select.Option value="Moldovan">Moldovan</Select.Option>
                                                    <Select.Option value="Monegasque">Monegasque</Select.Option>
                                                    <Select.Option value="Mongolian">Mongolian</Select.Option>
                                                    <Select.Option value="Montenegrin">Montenegrin</Select.Option>
                                                    <Select.Option value="Montserratian">Montserratian</Select.Option>
                                                    <Select.Option value="Moroccan">Moroccan</Select.Option>
                                                    <Select.Option value="Mosotho">Mosotho</Select.Option>
                                                    <Select.Option value="Mozambican">Mozambican</Select.Option>

                                                    <Select.Option value="Namibian">Namibian</Select.Option>
                                                    <Select.Option value="Nauruan">Nauruan</Select.Option>
                                                    <Select.Option value="Nepalese">Nepalese</Select.Option>
                                                    <Select.Option value="New Zealander">New Zealander</Select.Option>
                                                    <Select.Option value="Nicaraguan">Nicaraguan</Select.Option>
                                                    <Select.Option value="Nigerian">Nigerian</Select.Option>
                                                    <Select.Option value="Nigerien">Nigerien</Select.Option>
                                                    <Select.Option value="Niuean">Niuean</Select.Option>
                                                    <Select.Option value="North Korean">North Korean</Select.Option>
                                                    <Select.Option value="Northern Irish">Northern Irish</Select.Option>
                                                    <Select.Option value="Norwegian">Norwegian</Select.Option>

                                                    <Select.Option value="Omani">Omani</Select.Option>

                                                    <Select.Option value="Pakistani">Pakistani</Select.Option>
                                                    <Select.Option value="Palauan">Palauan</Select.Option>
                                                    <Select.Option value="Palestinian">Palestinian</Select.Option>
                                                    <Select.Option value="Panamanian">Panamanian</Select.Option>
                                                    <Select.Option value="Papua New Guinean">Papua New Guinean</Select.Option>
                                                    <Select.Option value="Paraguayan">Paraguayan</Select.Option>
                                                    <Select.Option value="Peruvian">Peruvian</Select.Option>
                                                    <Select.Option value="Pitcairn Islander">Pitcairn Islander</Select.Option>
                                                    <Select.Option value="Polish">Polish</Select.Option>
                                                    <Select.Option value="Portuguese">Portuguese</Select.Option>
                                                    <Select.Option value="Prydeinig">Prydeinig</Select.Option>
                                                    <Select.Option value="Puerto Rican">Puerto Rican</Select.Option>

                                                    <Select.Option value="Qatari">Qatari</Select.Option>

                                                    <Select.Option value="Romanian">Romanian</Select.Option>
                                                    <Select.Option value="Russian">Russian</Select.Option>
                                                    <Select.Option value="Rwandan">Rwandan</Select.Option>

                                                    <Select.Option value="Salvadorean">Salvadorean</Select.Option>
                                                    <Select.Option value="Sammarinese">Sammarinese</Select.Option>
                                                    <Select.Option value="Samoan">Samoan</Select.Option>
                                                    <Select.Option value="Sao Tomean">Sao Tomean</Select.Option>
                                                    <Select.Option value="Saudi Arabian">Saudi Arabian</Select.Option>
                                                    <Select.Option value="Scottish">Scottish</Select.Option>
                                                    <Select.Option value="Senegalese">Senegalese</Select.Option>
                                                    <Select.Option value="Serbian">Serbian</Select.Option>
                                                    <Select.Option value="Citizen of Seychelles">Citizen of Seychelles</Select.Option>
                                                    <Select.Option value="Sierra Leonean">Sierra Leonean</Select.Option>
                                                    <Select.Option value="Singaporean">Singaporean</Select.Option>
                                                    <Select.Option value="Slovak">Slovak</Select.Option>
                                                    <Select.Option value="Slovenian">Slovenian</Select.Option>
                                                    <Select.Option value="Solomon Islander">Solomon Islander</Select.Option>
                                                    <Select.Option value="Somali">Somali</Select.Option>
                                                    <Select.Option value="South African">South African</Select.Option>
                                                    <Select.Option value="South Korean">South Korean</Select.Option>
                                                    <Select.Option value="South Sudanese">South Sudanese</Select.Option>
                                                    <Select.Option value="Spanish">Spanish</Select.Option>
                                                    <Select.Option value="Sri Lankan">Sri Lankan</Select.Option>
                                                    <Select.Option value="St Helenian">St Helenian</Select.Option>
                                                    <Select.Option value="St Lucian">St Lucian</Select.Option>
                                                    <Select.Option value="Stateless">Stateless</Select.Option>
                                                    <Select.Option value="Sudanese">Sudanese</Select.Option>
                                                    <Select.Option value="Surinamese">Surinamese</Select.Option>
                                                    <Select.Option value="Swazi">Swazi</Select.Option>
                                                    <Select.Option value="Swedish">Nationality</Select.Option>
                                                    <Select.Option value="Swiss">Swiss</Select.Option>
                                                    <Select.Option value="Syrian">Syrian</Select.Option>

                                                    <Select.Option value="Taiwanese">Taiwanese</Select.Option>
                                                    <Select.Option value="Tajik">Tajik</Select.Option>
                                                    <Select.Option value="Tanzanian">Tanzanian</Select.Option>
                                                    <Select.Option value="Thai">Thai</Select.Option>
                                                    <Select.Option value="Togolese">Togolese</Select.Option>
                                                    <Select.Option value="Tongan">Tongan</Select.Option>
                                                    <Select.Option value="Trinidadian">Trinidadian</Select.Option>
                                                    <Select.Option value="Tristanian">Tristanian</Select.Option>
                                                    <Select.Option value="Tunisian">Tunisian</Select.Option>
                                                    <Select.Option value="Turkish">Turkish</Select.Option>
                                                    <Select.Option value="Turkmen">Turkmen</Select.Option>
                                                    <Select.Option value="Turks and Caicos Islander">Turks and Caicos Islander</Select.Option>
                                                    <Select.Option value="Tuvaluan">Tuvaluan</Select.Option>

                                                    <Select.Option value="Ugandan">Ugandan</Select.Option>
                                                    <Select.Option value="Ukrainian">Ukrainian</Select.Option>
                                                    <Select.Option value="Uruguayan">Uruguayan</Select.Option>
                                                    <Select.Option value="Uzbek">Uzbek</Select.Option>

                                                    <Select.Option value="Vatican Citizen">Vatican Citizen</Select.Option>
                                                    <Select.Option value="Citizen of Vanuatu">Citizen of Vanuatu</Select.Option>
                                                    <Select.Option value="Venezuelan">Venezuelan</Select.Option>
                                                    <Select.Option value="Vietnamese">Vietnamese</Select.Option>
                                                    <Select.Option value="Vincentian">Vincentian</Select.Option>

                                                    <Select.Option value="Wallisian">Wallisian</Select.Option>
                                                    <Select.Option value="Welsh">Welsh</Select.Option>

                                                    <Select.Option value="Yemeni">Yemeni</Select.Option>

                                                    <Select.Option value="Zambian">Zambian</Select.Option>
                                                    <Select.Option value="Zimbabwean">Zimbabwean</Select.Option>
                                                </Select>
                                            </Form.Item>
                                          </Col>
                                          <Col xl={4}>
                                            <Form.Item 
                                                {...field}
                                                name={[field.name, 'guest_tags']}
                                                fieldKey={[field.fieldKey, 'guest_tags']}
                                                // rules={[{ type:'array', defaultField: { type: 'string' }, message: `select only one guest tag`},
                                                    // {required: true, message: `#${field.name+1} Guest tag is required`}
                                                // ]}
                                            >
                                                <Select 
                                                    placeholder="Guest tag"
                                                    mode="tags"
                                                >
                                                    <Select.Option value="Resident">Resident</Select.Option>
                                                    <Select.Option value="Property Owner">Property Owner</Select.Option>
                                                    <Select.Option value="Investor">Investor</Select.Option>
                                                    <Select.Option value="Accredited RE Agents">Accredited RE Agents</Select.Option>
                                                    <Select.Option value="Supplier">Supplier</Select.Option>
                                                    <Select.Option value="VIP">VIP</Select.Option>
                                                    <Select.Option value="HOA Member">HOA Member</Select.Option>
                                                    <Select.Option value="HOA Member - Dependents">HOA Member Dependents</Select.Option>
                                                    <Select.Option value="HOA Member - Guest">HOA Member Guest</Select.Option>
                                                    <Select.Option value="Golf Member">Golf Member</Select.Option>
                                                    <Select.Option value="Golf Co-Member">Golf Co-Member</Select.Option>
                                                    <Select.Option value="Golf Member's Accompanied">Golf Member's Accompanied</Select.Option>
                                                    <Select.Option value="Golf Guest Member's Unaccompanied">Golf Guest Member's Unaccompanied</Select.Option>
                                                    <Select.Option value="Golf Guest Non-Member">Golf Guest Non-Member</Select.Option>
                                                    <Select.Option value="Property Owner - Non-Member">Property Owner - Non-Member</Select.Option>
                                                    <Select.Option value="Property Owner - HOA Member">Property Owner - HOA Member</Select.Option>
                                                    <Select.Option value="Property Owner - Dependents">Property Owner - Dependents</Select.Option>
                                                    <Select.Option value="Property Owner - Guests">Property Owner - Guests</Select.Option>
                                                </Select>
                                            </Form.Item> 
                                          </Col>
                                          <Col xl={1}>
                                              <MinusCircleOutlined
                                                  onClick={() => {
                                                      remove(field.name);
                                                      setInfantPax(prev => {
                                                          newBookingForm.setFieldsValue({ infant_pax: prev - 1 });
                                                          return prev - 1
                                                      });
                                                  }}
                                              />
                                          </Col>
                                      </Row>
                                  ))}
                              </div>
                          )
                       }
                  }
              </Form.List> 
                </Col>
                
                </Row>             

                <Toolbar />
            </Form>
        </div>
        
    )
}