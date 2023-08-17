import React, {useState} from 'react'
import RoleService from 'services/RoleService'
import UserService from 'services/UserService'
import TransportationService from 'services/Transportation'
import RouteService from 'services/Transportation/Route'
import ScheduleService from 'services/Transportation/ScheduleService'
import TranportationReportService from 'services/Transportation/Report'

import TransportationLocationService from 'services/Transportation/Location'
import TransportationRouteService from 'services/Transportation/Route'

import moment from 'moment'

import db from 'common/db.json'

import {Menu, Dropdown, Table, Typography, Modal, Form, Select, Input, InputNumber, Button, Row, Col, Card, DatePicker, message, Alert, Divider, Tag, TimePicker, notification, Descriptions, Space} from 'antd'
import {CloseCircleOutlined, PrinterOutlined, ReloadOutlined, EditOutlined, EllipsisOutlined} from '@ant-design/icons'
import { queryCache } from 'react-query'

const GenerateSchedules = (props) => {

    // Get queries
    const roleListQuery = RoleService.list();
    // const userListQuery = UserService.list();
    const transportationListQuery = TransportationService.list();
    const routeListQuery = RouteService.list();

    const [userList, setUserList] = useState(queryCache.getQueryData("users"));
    
    // Post, Put queries
    const [generateScheduleQuery, {isLoading: generateScheduleQueryIsLoading}] = ScheduleService.generateSchedule();

    // States
    const [selectedTransportation, setSelectedTransportation] = React.useState({});
    const [totalAllocation, settotalAllocation] = React.useState(0);
    const [noScheduleDates, setnoScheduleDates] = React.useState([]);

    const [generateScheduleForm] = Form.useForm();

    React.useEffect( () => {
        generateScheduleForm.resetFields();
        generateScheduleForm.setFieldsValue({
            seat_allocations: [],
        });
    },[props.generateSchedulesModalVisible])

    const handleChangeTransportation = (transportation_id) => {
        const transportation = _.find(transportationListQuery.data, i => i.id == transportation_id);

        generateScheduleForm.setFieldsValue({
            ...generateScheduleForm.getFieldsValue,
            seat_allocations: []
        })

        setSelectedTransportation(transportation);
    }

    const handleAllocationQuantityChange = (index, quantity) => {

        const total = _.sumBy(generateScheduleForm.getFieldValue('seat_allocations'), 'quantity');

        // seat_segments: seat_allocations[index].seat_segments,
        // on change of allocation, update all seat segments allocated to zero
        let seat_allocations = [...generateScheduleForm.getFieldValue('seat_allocations')];

        const seat_segments = _.map(seat_allocations[index]['seat_segments'], i => {
            // console.log(i);
            return {
                ...i,
                allocated: 0
            }
        });

        seat_allocations[index] = {
                ...seat_allocations[index],
                quantity: (total > selectedTransportation.active_seats_count) ? selectedTransportation.active_seats_count - (total - quantity) : quantity,
                seat_segments: seat_segments
        };

        generateScheduleForm.setFieldsValue({
            seat_allocations: [...seat_allocations]
        });

        settotalAllocation(total);
    }

    const handleSegmentQuantityChange = (parent_index, index, quantity) => {

        console.log(generateScheduleForm.getFieldValue('seat_allocations')[parent_index]['seat_segments']);

        const total = _.sumBy(generateScheduleForm.getFieldValue('seat_allocations')[parent_index]['seat_segments'], 'allocated');
        const max_allocation = generateScheduleForm.getFieldValue('seat_allocations')[parent_index].quantity;
        console.log(total, max_allocation);
        if (total > max_allocation) {
            console.log('full');

            let seat_allocations = [...generateScheduleForm.getFieldValue('seat_allocations')];

            // let seat_segments = [...generateScheduleForm.getFieldValue('seat_allocations')[parent_index]['seat_segments']];

            seat_allocations[parent_index]['seat_segments'][index] = {

                ...seat_allocations[parent_index]['seat_segments'][index],

                allocated: max_allocation - (total - quantity),

                // allowed_roles: seat_segments[index].allowed_roles,
                // allowd_users: seat_segments[index].allowed_users,
                // name: seat_segments[index].name,
                // trip_link: seat_segments[index].trip_link,
                // status: seat_segments[index].status,
                // booking_type: seat_segments[index].booking_type,
             };
             

            generateScheduleForm.setFieldsValue({
                seat_allocations: seat_allocations
            });

            // return false;
        }

    }

    const onFinish = (values) => {
        console.log(values);

        const newValues = {
            ...values,
            no_schedule_dates: noScheduleDates,
        }

        if (generateScheduleQueryIsLoading) return false;

        generateScheduleQuery(newValues, {
            onSuccess: res => {
                console.log(res);

                setSelectedTransportation({});

                generateScheduleForm.resetFields();

                props.setgenerateSchedulesModalVisible(false);

                setnoScheduleDates([]);

                notification.success({
                    message: 'Created schedule success!',
                    description:
                      '',
                  });
            },
            onError: e => {
                console.log(e);

                if (e.errors && e.errors.seat_allocations) message.warning(e.errors.seat_allocations);
                if (e && e.message) message.warning(e.message);

                const modal = Modal;
                setnoScheduleDates(e.no_schedule_dates);
                
                if (e.no_schedule_dates.length > 0) {
                    modal.confirm({
                        title: "Date range have existing schedules",
                        content: (
                            <>
                                <Alert type="warning" className="mb-2" message="Here are the dates and trip number with existing schedules." />
                                {
                                    e.data && e.data.map( (item, key) => {
                                        return <Tag color="orange" key={key} className="mr-1">{item.trip_date} : {item.trip_number}</Tag>
                                    })
                                }
                                <Divider/>
                                <Alert type="primary" message="Here are dates without schedule yet. Would you like to proceed on creating schedules for this date/s?" />
                                {
                                    e.no_schedule_dates && e.no_schedule_dates.map( (date, key) => {
                                        return <Tag color="green" key={key} className="mr-1">{date}</Tag>
                                    })
                                }
                            </>
                        ),
                        onOk: () => {
                            console.log('test');
                            generateScheduleForm.submit();
                        }
                    });
                }

            }
        })
    }

    return (
        <Form layout="vertical" form={generateScheduleForm} onFinish={onFinish}>
            <Row gutter={[8,8]}>
                <Col xl={6}>
                    <Form.Item label="Transportation" name="transportation_id" rules={[{required:true}]}>
                        <Select onChange={(e)=>handleChangeTransportation(e)}>
                            {
                                transportationListQuery.data && transportationListQuery.data.map((item, key) => {
                                    return <Select.Option key={key} value={item.id}>{item.name} ({item.capacity}) ({item.active_seats_count} active seats)</Select.Option>
                                })
                            }
                            <Select.Option value="100">Non existing transpo</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={6}>
                    <Form.Item label="Route" name="route_id" rules={[{required:true}]}>
                        <Select>
                            {
                                routeListQuery.data && routeListQuery.data.map((item, key) => {
                                    return <Select.Option key={key} value={item.id}><span className="text-primary">{item.origin.code} &ndash;&gt; {item.destination.code}</span><br/>{item.origin.name} &ndash;&gt; {item.destination.name}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={4}>
                    <Form.Item label="Schedule date range" name="date_range" rules={[{required:true}]}>
                        <DatePicker.RangePicker/>
                    </Form.Item>
                </Col>
                <Col xl={4}>
                    <Form.Item label="Departure time" name="departure_time" rules={[{required:true}]}>
                        <TimePicker disabledSeconds={() => _.range(60)} minuteStep={5}/>
                    </Form.Item>
                </Col>
                <Col xl={4}>
                    <Form.Item label="Arrival time" name="arrival_time" rules={[{required:true}]}>
                        <TimePicker disabledSeconds={() => _.range(60)} minuteStep={5}/>
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Typography.Title level={4}>Seat allocations</Typography.Title>
                    <Form.List name="seat_allocations">
                        {
                            (fields, {add, remove}) => (
                                <>
                                    {fields.map(field => (
                                        <Card key={field.name} className="mb-2" style={{border: 'solid 1px limegreen'}}>
                                            <Row gutter={[8,8]}>
                                                <Col xl={1}>
                                                    <Button onClick={()=>remove(field.name)} type="link" icon={<CloseCircleOutlined/>} />
                                                </Col>
                                                <Col xl={6}>
                                                    <Form.Item {...field} label={`#${field.name+1} Allocation name`} name={[field.name, 'name']} rules={[{required:true}]}>
                                                        <Input placeholder="BPO / HOA / RE / OTA" />
                                                    </Form.Item>
                                                </Col>
                                                <Col xl={4}>
                                                    <Form.Item initialValue={0} extra={selectedTransportation.active_seats_count} {...field} label={`#${field.name+1} Quantity`} name={[field.name, 'quantity']} rules={[{required:true}]}>
                                                        <InputNumber onChange={(e)=>handleAllocationQuantityChange(field.name, e)} min={0} />
                                                    </Form.Item>
                                                </Col>
                                                <Col xl={13}>
                                                    <Form.Item {...field} label={`#${field.name+1} Allowed roles to manage the allocation`} name={[field.name, 'allowed_roles']}>
                                                        <Select mode="multiple">
                                                            {
                                                                roleListQuery.data && roleListQuery.data.map((item, key) => {
                                                                    return <Select.Option key={key} value={item.name}>{item.name}</Select.Option>
                                                                })
                                                            }
                                                        </Select>
                                                    </Form.Item>
                                                </Col>
                                                <Col xl={24}>
                                                <Form.List name={[field.name, 'seat_segments']}>
                                                    {
                                                        (seatSegments, {add, remove}) => (
                                                            <>
                                                            <table style={{width:'100%'}}>
                                                                <thead>
                                                                    <tr>
                                                                        <th>&nbsp;</th>
                                                                        <th style={{fontSize: '0.8rem'}}><span className="text-danger mr-1">*</span>Segment</th>
                                                                        <th style={{fontSize: '0.8rem'}}><span className="text-danger mr-1">*</span>Allocated</th>
                                                                        <th style={{fontSize: '0.8rem'}}><span className="text-danger mr-1">*</span>Rate</th>
                                                                        <th style={{fontSize: '0.8rem'}}><span className="text-danger mr-1">*</span>Type</th>
                                                                        <th style={{fontSize: '0.8rem'}}><span className="text-danger mr-1">*</span>Status</th>
                                                                        <th style={{fontSize: '0.8rem'}}>Link</th>
                                                                        <th style={{fontSize: '0.8rem'}}>Allowed roles to use the allocation</th>
                                                                        <th style={{fontSize: '0.8rem'}}>Allowed users to use the allocation</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                {
                                                                    seatSegments.map(seatSegment => (
                                                                        // <Card key={seatSegment.name} size="small" className="card-shadow mb-2">
                                                                        //     <Row gutter={[8,8]}>
                                                                        //         <Col xl={1}>
                                                                        //             <Button onClick={()=>remove(seatSegment.name)} type="link" icon={<CloseCircleOutlined/>} />
                                                                        //         </Col>
                                                                        //         <Col xl={6}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Segment / Class`} name={[seatSegment.name, 'name']} rules={[{required:true}]}>
                                                                        //                 <Input placeholder="Seat segment" />
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //         <Col xl={4}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Allocated`} name={[seatSegment.name, 'allocated']} rules={[{required:true}]}>
                                                                        //                 <InputNumber onChange={(e) => handleSegmentQuantityChange(field.name, seatSegment.name, e)} min={0} />
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //         <Col xl={6}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Booking type`} name={[seatSegment.name, 'booking_type']} rules={[{required:true}]}>
                                                                        //                 <Select mode="multiple">
                                                                        //                     <Select.Option value="DT">Day tour</Select.Option>
                                                                        //                     <Select.Option value="ON">Overnight</Select.Option>
                                                                        //                 </Select>
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //         <Col xl={6}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Status`} name={[seatSegment.name, 'status']} rules={[{required:true}]}>
                                                                        //                 <Select>
                                                                        //                     <Select.Option value="published">Published</Select.Option>
                                                                        //                     <Select.Option value="unpublished">Unpublished</Select.Option>
                                                                        //                     <Select.Option value="locked">Locked</Select.Option>
                                                                        //                 </Select>
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //         <Col xl={6}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Trip link`} name={[seatSegment.name, 'trip_link']}>
                                                                        //                 <Select>
                                                                        //                     <Select.Option value="LINK1">Link 1</Select.Option>
                                                                        //                     <Select.Option value="LINK2">Link 2</Select.Option>
                                                                        //                     <Select.Option value="LINK3">Link 3</Select.Option>
                                                                        //                     <Select.Option value="LINK4">Link 4</Select.Option>
                                                                        //                     <Select.Option value="LINK5">Link 5</Select.Option>
                                                                        //                     <Select.Option value="LINK6">Link 6</Select.Option>
                                                                        //                     <Select.Option value="LINK7">Link 7</Select.Option>
                                                                        //                     <Select.Option value="LINK8">Link 8</Select.Option>
                                                                        //                     <Select.Option value="LINK9">Link 9</Select.Option>
                                                                        //                     <Select.Option value="LINK10">Link 10</Select.Option>
                                                                        //                 </Select>
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //         <Col xl={9}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Allowed roles to use the allocation`} name={[seatSegment.name, 'allowed_roles']}>
                                                                        //                 <Select mode="multiple">
                                                                        //                     {
                                                                        //                         roleListQuery.data && roleListQuery.data.map((item, key) => {
                                                                        //                             return <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                                                                        //                         })
                                                                        //                     }
                                                                        //                 </Select>
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //         <Col xl={9}>
                                                                        //             <Form.Item {...seatSegment} label={`#${seatSegment.name+1} Allowed users to use the allocation`} name={[seatSegment.name, 'allowed_users']}>
                                                                        //                 <Select
                                                                        //                     showSearch
                                                                        //                     optionFilterProp="children"
                                                                        //                     filterOption={(input, option) =>
                                                                        //                         option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                                        //                     }
                                                                        //                     mode="multiple">
                                                                        //                     {
                                                                        //                         userListQuery.data && userListQuery.data.map((item, key) => {
                                                                        //                             return <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name}`}</Select.Option>
                                                                        //                         })
                                                                        //                     }
                                                                        //                 </Select>
                                                                        //             </Form.Item>
                                                                        //         </Col>
                                                                        //     </Row>
                                                                        // </Card>
                                                                        <tr key={seatSegment.name}>
                                                                                <td valign="top">
                                                                                    <Button onClick={()=>remove(seatSegment.name)} type="link" icon={<CloseCircleOutlined/>} />
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item {...seatSegment} name={[seatSegment.name, 'name']} rules={[{required:true}]}>
                                                                                        <Input placeholder={`#${seatSegment.name + 1} Seat segment`} />
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item initialValue={0} {...seatSegment} name={[seatSegment.name, 'allocated']} rules={[{required:true}]}>
                                                                                        <InputNumber onChange={(e) => handleSegmentQuantityChange(field.name, seatSegment.name, e)} min={0} />
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item initialValue={0} {...seatSegment} name={[seatSegment.name, 'rate']} rules={[{required:true}]}>
                                                                                        <InputNumber min={0} />
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item {...seatSegment} name={[seatSegment.name, 'booking_type']} rules={[{required:true}]}>
                                                                                        <Select style={{minWidth:'100px', width:'100%'}} mode="multiple" placeholder="Booking type">
                                                                                            <Select.Option value="DT">Day tour</Select.Option>
                                                                                            <Select.Option value="ON">Overnight</Select.Option>
                                                                                        </Select>
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item {...seatSegment} name={[seatSegment.name, 'status']} rules={[{required:true}]}>
                                                                                        <Select style={{width:'100%'}} placeholder="Status">
                                                                                            <Select.Option value="published">Published</Select.Option>
                                                                                            <Select.Option value="unpublished">Unpublished</Select.Option>
                                                                                            <Select.Option value="locked">Locked</Select.Option>
                                                                                        </Select>
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item {...seatSegment} name={[seatSegment.name, 'trip_link']}>
                                                                                        <Select style={{width:'100%'}} placeholder="Trip link">
                                                                                            <Select.Option value="">No link</Select.Option>
                                                                                            <Select.Option value="LINK1">Link 1</Select.Option>
                                                                                            <Select.Option value="LINK2">Link 2</Select.Option>
                                                                                            <Select.Option value="LINK3">Link 3</Select.Option>
                                                                                            <Select.Option value="LINK4">Link 4</Select.Option>
                                                                                            <Select.Option value="LINK5">Link 5</Select.Option>
                                                                                            <Select.Option value="LINK6">Link 6</Select.Option>
                                                                                            <Select.Option value="LINK7">Link 7</Select.Option>
                                                                                            <Select.Option value="LINK8">Link 8</Select.Option>
                                                                                            <Select.Option value="LINK9">Link 9</Select.Option>
                                                                                            <Select.Option value="LINK10">Link 10</Select.Option>
                                                                                            <Select.Option value="LINK11">Link 11</Select.Option>
                                                                                            <Select.Option value="LINK12">Link 12</Select.Option>
                                                                                            <Select.Option value="LINK13">Link 13</Select.Option>
                                                                                            <Select.Option value="LINK14">Link 14</Select.Option>
                                                                                            <Select.Option value="LINK15">Link 15</Select.Option>
                                                                                            <Select.Option value="LINK16">Link 16</Select.Option>
                                                                                            <Select.Option value="LINK17">Link 17</Select.Option>
                                                                                            <Select.Option value="LINK18">Link 18</Select.Option>
                                                                                            <Select.Option value="LINK19">Link 19</Select.Option>
                                                                                            <Select.Option value="LINK20">Link 20</Select.Option>
                                                                                            <Select.Option value="LINK21">Link 21</Select.Option>
                                                                                            <Select.Option value="LINK22">Link 22</Select.Option>
                                                                                            <Select.Option value="LINK23">Link 23</Select.Option>
                                                                                            <Select.Option value="LINK24">Link 24</Select.Option>
                                                                                            <Select.Option value="LINK25">Link 25</Select.Option>
                                                                                            <Select.Option value="LINK26">Link 26</Select.Option>
                                                                                            <Select.Option value="LINK27">Link 27</Select.Option>
                                                                                            <Select.Option value="LINK28">Link 28</Select.Option>
                                                                                            <Select.Option value="LINK29">Link 29</Select.Option>
                                                                                            <Select.Option value="LINK30">Link 30</Select.Option>

                                                                                            <Select.Option value="LINK31">Link 31</Select.Option>
                                                                                            <Select.Option value="LINK32">Link 32</Select.Option>
                                                                                            <Select.Option value="LINK33">Link 33</Select.Option>
                                                                                            <Select.Option value="LINK34">Link 34</Select.Option>
                                                                                            <Select.Option value="LINK35">Link 35</Select.Option>
                                                                                            <Select.Option value="LINK36">Link 36</Select.Option>
                                                                                            <Select.Option value="LINK37">Link 37</Select.Option>
                                                                                            <Select.Option value="LINK38">Link 38</Select.Option>
                                                                                            <Select.Option value="LINK39">Link 39</Select.Option>
                                                                                            <Select.Option value="LINK40">Link 40</Select.Option>
                                                                                            <Select.Option value="LINK41">Link 41</Select.Option>
                                                                                            <Select.Option value="LINK42">Link 42</Select.Option>
                                                                                            <Select.Option value="LINK43">Link 43</Select.Option>
                                                                                            <Select.Option value="LINK44">Link 44</Select.Option>
                                                                                            <Select.Option value="LINK45">Link 45</Select.Option>
                                                                                            <Select.Option value="LINK46">Link 46</Select.Option>
                                                                                            <Select.Option value="LINK47">Link 47</Select.Option>
                                                                                            <Select.Option value="LINK48">Link 48</Select.Option>
                                                                                            <Select.Option value="LINK49">Link 49</Select.Option>
                                                                                            <Select.Option value="LINK50">Link 50</Select.Option>
                                                                                            <Select.Option value="LINK51">Link 51</Select.Option>
                                                                                            <Select.Option value="LINK52">Link 52</Select.Option>
                                                                                            <Select.Option value="LINK53">Link 53</Select.Option>
                                                                                            <Select.Option value="LINK54">Link 54</Select.Option>
                                                                                            <Select.Option value="LINK55">Link 55</Select.Option>
                                                                                            <Select.Option value="LINK56">Link 56</Select.Option>
                                                                                            <Select.Option value="LINK57">Link 57</Select.Option>
                                                                                            <Select.Option value="LINK58">Link 58</Select.Option>
                                                                                            <Select.Option value="LINK59">Link 59</Select.Option>
                                                                                            <Select.Option value="LINK60">Link 60</Select.Option>
                                                                                        </Select>
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    <Form.Item {...seatSegment} name={[seatSegment.name, 'allowed_roles']}>
                                                                                        <Select mode="multiple" style={{width:'100%'}}>
                                                                                            {
                                                                                                roleListQuery.data && roleListQuery.data.map((item, key) => {
                                                                                                    return <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                                                                                                })
                                                                                            }
                                                                                        </Select>
                                                                                    </Form.Item>
                                                                                </td>
                                                                                <td>
                                                                                    {/* <Form.Item {...seatSegment} name={[seatSegment.name, 'allowed_users']}>
                                                                                        <Select
                                                                                            style={{width:'100%'}}
                                                                                            showSearch
                                                                                            optionFilterProp="children"
                                                                                            filterOption={(input, option) =>
                                                                                                option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                                                            }
                                                                                            mode="multiple">
                                                                                            {
                                                                                                userListQuery.data && userListQuery.data
                                                                                                .filter( i => i.roles[0].name == 'POC Agent' || i.roles[0].name == 'Property Consultant' || i.roles[0].name == 'Sales Director')
                                                                                                .map((item, key) => {
                                                                                                    return <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name}`}</Select.Option>
                                                                                                })
                                                                                            }
                                                                                        </Select>
                                                                                    </Form.Item> */}

                                                                                    <Form.Item {...seatSegment} name={[seatSegment.name, 'allowed_users']}>
                                                                                        <Select
                                                                                            style={{width:'100%'}}
                                                                                            showSearch
                                                                                            optionFilterProp="children"
                                                                                            filterOption={(input, option) =>
                                                                                                option.children.toLowerCase().indexOf(input.toLowerCase()) >= 0
                                                                                            }
                                                                                            mode="multiple">
                                                                                            {
                                                                                                userList && userList
                                                                                                // .filter( i => i.roles[0].name == 'POC Agent' || i.roles[0].name == 'Property Consultant' || i.roles[0].name == 'Sales Director')
                                                                                                .filter( i=> {
                                                                                                    if (i.roles.length > 0 && (i.roles[0].name == 'POC Agent' || i.roles[0].name == 'Sales Director')) { 
                                                                                                    return true; } else { return false; }
                                                                                               })
                                                                                                .map((item, key) => {
                                                                                                    return <Select.Option key={key} value={item.id}>{`${item.first_name} ${item.last_name} ${item.email}`}</Select.Option>
                                                                                                })
                                                                                            }
                                                                                        </Select>
                                                                                    </Form.Item>
                                                                                </td>
                                                                        </tr>
                                                                    ))
                                                                }
                                                                </tbody>
                                                                </table>
                                                                <Button size="small" onClick={()=>add()}>Add seat segment</Button>
                                                            </>
                                                        )
                                                    }
                                                </Form.List>
                                                </Col>
                                            </Row>
                                        </Card>
                                    ))}
                                <Button onClick={()=>add()}>Add seat allocation</Button>
                                </>
                            )
                        }
                    </Form.List>
                </Col>
                <Col xl={24} align="right">
                        <Button onClick={()=>Modal.confirm({
                            title: "Are you sure?",
                            content: "You're about to generate transportation schedule.",
                            onOk: () => generateScheduleForm.submit()
                        })}>Generate Schedule</Button>
                </Col>
            </Row>
            
        </Form>
    )
}

const AddSegmentForm = ({item, schedule, setaddSeatSegmentModalVisible, refetchSchedule}) => {

    const [addSeatSegmentQuery, {isLoading:addSeatSegmentQueryIsLoading, reset: addSeatSegmentQueryReset}] = ScheduleService.addSeatSegment();

    // Forms
    const [newSeatSegmentForm] = Form.useForm();

    const onNewSeatSegmentFormFinish = (values) => {
        console.log(values);
        // Query

        if (addSeatSegmentQueryIsLoading) {
            message.info("Loading...");
            return false;
        }

        addSeatSegmentQuery(values, {
            onSuccess: (res) => {
                console.log(res);
                newSeatSegmentForm.resetFields();
                notification.success({
                    message: 'Saved new seat segment!'
                });

                refetchSchedule();

                setaddSeatSegmentModalVisible(false);
            },
            onError: (e) => {
                console.log(e);

                notification.error({
                    message: e.message
                });
                // addSeatSegmentQueryReset();
            }
        })
        
    }


    return (
    <>
        <Row gutter={[8,8]}>
            <Col xl={12}>
            <Descriptions bordered>
                <Descriptions.Item span={3} label="Schedule">{schedule.trip_date} {schedule.start_time}</Descriptions.Item>
                <Descriptions.Item span={3} label="Origin">{schedule.origin}</Descriptions.Item>
                <Descriptions.Item span={3} label="Destination">{schedule.destination}</Descriptions.Item>
                <Descriptions.Item span={3} label="Allocation name">{item.name}</Descriptions.Item>
                <Descriptions.Item label="Allocation quantity">{item.quantity}</Descriptions.Item>
                <Descriptions.Item style={{color: 'limegreen'}} label="Open">{item.quantity - _.sumBy(item.segments, 'allocated')}</Descriptions.Item>
            </Descriptions>
        </Col>
        <Col xl={12}>
        <Form form={newSeatSegmentForm} layout="vertical" onFinish={onNewSeatSegmentFormFinish} initialValues={{
            seat_allocation_id: item.id,
            trip_number: schedule.trip_number,
        }}>
            <Row gutter={[8,8]}>
                <Col xl={24}>
                    <Form.Item name="seat_allocation_id" noStyle rules={[{
                        required:true
                    }]}>
                        <Input hidden />
                    </Form.Item>
                    <Form.Item name="trip_number" noStyle rules={[{
                        required:true
                    }]}>
                        <Input hidden />
                    </Form.Item>
                    <Form.Item name="name" label="Segment name" rules={[{
                        required:true
                    }]}>
                        <Input />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="allocated" label="Allocated" rules={[{
                        required:true
                    }]}>
                        <Select>
                            {
                                _.map(_.range((item.quantity - _.sumBy(item.segments, 'allocated')+1)), (i,key) => {
                                    return <Select.Option key={key} value={i}>{i}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="rate" label="Rate" rules={[{
                        required:true
                    }]}>
                        <InputNumber min={0}/>
                    </Form.Item>
                </Col>
                <Col xl={16}>
                    <Form.Item name="booking_type" label="Booking type" rules={[{
                        required: true
                    }]}>
                        <Select mode="multiple">
                            <Select.Option value="DT">Day tour</Select.Option>
                            <Select.Option value="ON">Overnight</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="status" label="Status" rules={[{
                        required: true
                    }]}>
                        <Select>
                            <Select.Option value="published">Published</Select.Option>
                            <Select.Option value="unpublished">Unpublished</Select.Option>
                            <Select.Option value="locked">Locked</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="trip_link" label="Trip link">
                        <Select placeholder="Trip link">
                            <Select.Option value="">No link</Select.Option>
                            <Select.Option value="LINK1">Link 1</Select.Option>
                            <Select.Option value="LINK2">Link 2</Select.Option>
                            <Select.Option value="LINK3">Link 3</Select.Option>
                            <Select.Option value="LINK4">Link 4</Select.Option>
                            <Select.Option value="LINK5">Link 5</Select.Option>
                            <Select.Option value="LINK6">Link 6</Select.Option>
                            <Select.Option value="LINK7">Link 7</Select.Option>
                            <Select.Option value="LINK8">Link 8</Select.Option>
                            <Select.Option value="LINK9">Link 9</Select.Option>
                            <Select.Option value="LINK10">Link 10</Select.Option>
                            <Select.Option value="LINK11">Link 11</Select.Option>
                            <Select.Option value="LINK12">Link 12</Select.Option>
                            <Select.Option value="LINK13">Link 13</Select.Option>
                            <Select.Option value="LINK14">Link 14</Select.Option>
                            <Select.Option value="LINK15">Link 15</Select.Option>
                            <Select.Option value="LINK16">Link 16</Select.Option>
                            <Select.Option value="LINK17">Link 17</Select.Option>
                            <Select.Option value="LINK18">Link 18</Select.Option>
                            <Select.Option value="LINK19">Link 19</Select.Option>
                            <Select.Option value="LINK20">Link 20</Select.Option>
                            <Select.Option value="LINK21">Link 21</Select.Option>
                            <Select.Option value="LINK22">Link 22</Select.Option>
                            <Select.Option value="LINK23">Link 23</Select.Option>
                            <Select.Option value="LINK24">Link 24</Select.Option>
                            <Select.Option value="LINK25">Link 25</Select.Option>
                            <Select.Option value="LINK26">Link 26</Select.Option>
                            <Select.Option value="LINK27">Link 27</Select.Option>
                            <Select.Option value="LINK28">Link 28</Select.Option>
                            <Select.Option value="LINK29">Link 29</Select.Option>
                            <Select.Option value="LINK30">Link 30</Select.Option>

                            <Select.Option value="LINK31">Link 31</Select.Option>
                            <Select.Option value="LINK32">Link 32</Select.Option>
                            <Select.Option value="LINK33">Link 33</Select.Option>
                            <Select.Option value="LINK34">Link 34</Select.Option>
                            <Select.Option value="LINK35">Link 35</Select.Option>
                            <Select.Option value="LINK36">Link 36</Select.Option>
                            <Select.Option value="LINK37">Link 37</Select.Option>
                            <Select.Option value="LINK38">Link 38</Select.Option>
                            <Select.Option value="LINK39">Link 39</Select.Option>
                            <Select.Option value="LINK40">Link 40</Select.Option>
                            <Select.Option value="LINK41">Link 41</Select.Option>
                            <Select.Option value="LINK42">Link 42</Select.Option>
                            <Select.Option value="LINK43">Link 43</Select.Option>
                            <Select.Option value="LINK44">Link 44</Select.Option>
                            <Select.Option value="LINK45">Link 45</Select.Option>
                            <Select.Option value="LINK46">Link 46</Select.Option>
                            <Select.Option value="LINK47">Link 47</Select.Option>
                            <Select.Option value="LINK48">Link 48</Select.Option>
                            <Select.Option value="LINK49">Link 49</Select.Option>
                            <Select.Option value="LINK50">Link 50</Select.Option>
                            <Select.Option value="LINK51">Link 51</Select.Option>
                            <Select.Option value="LINK52">Link 52</Select.Option>
                            <Select.Option value="LINK53">Link 53</Select.Option>
                            <Select.Option value="LINK54">Link 54</Select.Option>
                            <Select.Option value="LINK55">Link 55</Select.Option>
                            <Select.Option value="LINK56">Link 56</Select.Option>
                            <Select.Option value="LINK57">Link 57</Select.Option>
                            <Select.Option value="LINK58">Link 58</Select.Option>
                            <Select.Option value="LINK59">Link 59</Select.Option>
                            <Select.Option value="LINK60">Link 60</Select.Option>
                        </Select>
                    </Form.Item>
                </Col>
            </Row>
            <Row>
                <Col xl={24} align="right"><Button htmlType="submit">Save</Button></Col>
            </Row>
        </Form>
        </Col>
    </Row>
    </>)
}

const AddSeatAllocationForm = ({item, setaddSeatAllocationModalVisible, refetchSchedule}) => {

    // Get
    const roleListQuery = RoleService.list();
    // Post
    const [addSeatAllocationQuery, {isLoading:addSeatAllocationQueryIsLoading, reset: addSeatAllocationQueryReset}] = ScheduleService.addSeatAllocation();

    // Forms
    const [newSeatAllocationForm] = Form.useForm();

    const onNewSeatAllocationFormFinish = (values) => {
        console.log(values);
        // Query

        if (addSeatAllocationQueryIsLoading) {
            message.info("Loading...");
            return false;
        }

        addSeatAllocationQuery(values, {
            onSuccess: (res) => {
                console.log(res);
                newSeatAllocationForm.resetFields();
                notification.success({
                    message: 'Saved new seat allocation!'
                });

                refetchSchedule();

                setaddSeatAllocationModalVisible(false);
            },
            onError: (e) => {
                console.log(e);

                notification.error({
                    message: e.message
                });
                // addSeatAllocationQueryReset();
            }
        })
        
    }


    return (
    <>
        <Row gutter={[8,8]}>
            <Col xl={12}>
            <Descriptions bordered>
                <Descriptions.Item span={3} label="Schedule">{item.trip_date} {item.start_time}</Descriptions.Item>
                <Descriptions.Item span={3} label="Origin">{item.origin}</Descriptions.Item>
                <Descriptions.Item span={3} label="Destination">{item.destination}</Descriptions.Item>
                <Descriptions.Item style={{color: 'limegreen'}} label="Open">{item.transportation.active_seats_count - item.allocated_seat}</Descriptions.Item>
            </Descriptions>
        </Col>
        <Col xl={12}>
        <Form form={newSeatAllocationForm} layout="vertical" onFinish={onNewSeatAllocationFormFinish} initialValues={{
            schedule_id: item.id,
        }}>
            <Row gutter={[8,8]}>
                <Col xl={24}>
                    <Form.Item name="schedule_id" noStyle rules={[{
                        required:true
                    }]}>
                        <Input hidden />
                    </Form.Item>
                    <Form.Item name="name" label="Allocation name" rules={[{
                        required:true
                    }]}>
                        <Input />
                    </Form.Item>
                </Col>
                <Col xl={8}>
                    <Form.Item name="quantity" label="Quantity" rules={[{
                        required:true
                    }]}>
                        <Select>
                            {
                                _.map(_.range(((item.transportation.active_seats_count - item.allocated_seat)+1)), (i,key) => {
                                    return <Select.Option key={key} value={i}>{i}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Form.Item label={`Allowed roles to manage the allocation`} name={'allowed_roles'}>
                        <Select mode="multiple">
                            {
                                roleListQuery.data && roleListQuery.data.map((item, key) => {
                                    return <Select.Option key={key} value={item.name}>{item.name}</Select.Option>
                                })
                            }
                        </Select>
                    </Form.Item>
                </Col>
            </Row>
            <Row>
                <Col xl={24} align="right"><Button htmlType="submit">Save</Button></Col>
            </Row>
        </Form>
        </Col>
    </Row>
    </>)
}

export default function Page(props) {

    // States
    const [date, setDate] = React.useState(moment().format('YYYY-MM-DD'));
    const [generateSchedulesModalVisible, setgenerateSchedulesModalVisible] = React.useState(false);
    const [addSeatSegmentModalVisible, setaddSeatSegmentModalVisible] = React.useState(false);
    const [addSeatSegmentData, setAddSeatSegmentData] = React.useState({});
    const [addSeatAllocationModalVisible, setaddSeatAllocationModalVisible] = React.useState(false);
    const [addSeatAllocationData, setAddSeatAllocationData] = React.useState({});

    // Refs

    // Get
    const roleListQuery = RoleService.list();
    const userListQuery = UserService.list();
    const scheduleListQuery = ScheduleService.list(date);

    // Post, Put
    const [updateSeatAllocationAllowedRolesQuery, {isLoading:updateSeatAllocationAllowedRolesQueryIsLoading}] = ScheduleService.updateSeatAllocationAllowedRoles();
    const [updateSeatSegmentBookingTypesQuery, {isLoading:updateSeatSegmentBookingTypesQueryIsLoading}] = ScheduleService.updateSeatSegmentBookingTypes();
    const [updateSeatSegmentAllowedRolesQuery, {isLoading:updateSeatSegmentAllowedRolesQueryIsLoading}] = ScheduleService.updateSeatSegmentAllowedRoles();
    const [updateSeatSegmentAllowedUsersQuery, {isLoading:updateSeatSegmentAllowedUsersQueryIsLoading}] = ScheduleService.updateSeatSegmentAllowedUsers();
    const [updateSeatSegmentStatusQuery, {isLoading:updateSeatSegmentStatusQueryIsLoading}] = ScheduleService.updateSeatSegmentStatus();
    const [updateSeatSegmentLinkQuery, {isLoading:updateSeatSegmentLinkQueryIsLoading}] = ScheduleService.updateSeatSegmentLink();
    const [updateScheduleStatusQuery, {isLoading:updateScheduleStatusQueryIsLoading}] = ScheduleService.updateScheduleStatus();
    const [updateSeatAllocationQuantityQuery, {isLoading: updateSeatAllocationQuantityQueryIsLoading}] = ScheduleService.updateSeatAllocationQuantity();
    const [updateSeatSegmentAllocatedQuery, {isLoading: updateSeatSegmentAllocatedQueryIsLoading}] = ScheduleService.updateSeatSegmentAllocated();
    // const [printManifestQuery, {isLoading: printManifestQueryIsLoading}] = ScheduleService.printManifest();
    const [updateSeatSegmentRateQuery, {isLoading: updateSeatSegmentRateQueryIsLoading}] = ScheduleService.updateSeatSegmentRate();
    const downloadReport = TranportationReportService.ferryPassengersManifestoDownload;

    const [updateScheduleQuery, {isLoading:updateScheduleQueryIsLoading, reset: updateScheduleQueryReset}] = ScheduleService.updateSchedule();

    React.useEffect( () => {
        scheduleListQuery.refetch();
    },[date]);

    const handleSeatAllocationAllowedRoles = (id, roles) => {
        // console.log(id, roles);

        if (updateSeatAllocationAllowedRolesQueryIsLoading) return false;

        updateSeatAllocationAllowedRolesQuery({
            id: id,
            new_roles: roles,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat allocation allowed roles update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleSeatSegmentBookingTypesChange = (id, booking_types) => {
        // console.log(id, new_booking_types);

        if (updateSeatSegmentBookingTypesQueryIsLoading) return false;

        updateSeatSegmentBookingTypesQuery({
            id: id,
            new_booking_types: booking_types,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat segment booking types update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleSeatSegmentAllowedRolesChange = (id, roles) => {

        if (updateSeatSegmentAllowedRolesQueryIsLoading) return false;

        updateSeatSegmentAllowedRolesQuery({
            id: id,
            new_roles: roles,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat segment allowed roles update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleSeatSegmentAllowedUsersChange = (id, users) => {

        if (updateSeatSegmentAllowedUsersQueryIsLoading) return false;

        updateSeatSegmentAllowedUsersQuery({
            id: id,
            new_users: users,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat segment allowed users update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleSeatSegmentStatusChange = (id, status) => {

        if (updateSeatSegmentStatusQueryIsLoading) return false;

        updateSeatSegmentStatusQuery({
            id: id,
            new_status: status,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat segment status update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleSeatSegmentLinkChange = (id, link) => {

        if (updateSeatSegmentLinkQueryIsLoading) return false;

        updateSeatSegmentLinkQuery({
            id: id,
            new_link: link,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat segment link update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleScheduleStatusChange = (id, status) => {

        let ans;

        if (status == 'cancelled') {
            ans = confirm("Are you sure you want to cancel schedule? Cancelling schedule will REMOVE all passenger trips.");
        }

        if (!ans && status == 'cancelled') {
            return false;
        }

        if (updateScheduleStatusQueryIsLoading) return false;

        updateScheduleStatusQuery({
            id: id,
            new_status: status,
        }, {
            onSuccess: (res) => {
                // console.log(res);

                queryCache.setQueryData("schedules", oldData => {

                    const index = oldData.findIndex( i => i.id == id );

                    oldData[index] = {
                        ...oldData[index],
                        status: status
                    }

                    return [...oldData]
                })

                message.success('Schedule status update successful!');
            },
            onError: (e) => {
                console.log(e);
            }
        })
    }

    const handleAddSeatSegment = (item, schedule) => {
        // console.log(item, schedule);
        
        setAddSeatSegmentData({
            item: item,
            schedule: schedule,
        });
        
        setaddSeatSegmentModalVisible(true);
    }

    const handleAddSeatAllocation = (item) => {
        // console.log(item);
        
        setAddSeatAllocationData(item);
        
        setaddSeatAllocationModalVisible(true);
    }

    const handleSeatAllocationQuantityChange = (seat_allocation_id, quantity) => {
        // console.log(seat_allocation_id, quantity);

        if (updateSeatAllocationQuantityQueryIsLoading) return false;

        updateSeatAllocationQuantityQuery({
            seat_allocation_id: seat_allocation_id,
            quantity: quantity,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat allocation quantity update successful!');
                scheduleListQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
                message.error('Seat allocation quantity update failed!');
            }
        })
        
    }

    const handleSeatSegmentAllocatedChange = (seat_segment_id, allocated) => {
        console.log(seat_segment_id, allocated);

        if (updateSeatSegmentAllocatedQueryIsLoading) return false;

        updateSeatSegmentAllocatedQuery({
            seat_segment_id: seat_segment_id,
            allocated: allocated,
        }, {
            onSuccess: (res) => {
                // console.log(res);
                message.success('Seat segment allocated update successful!');
                scheduleListQuery.refetch();
            },
            onError: (e) => {
                console.log(e);
                message.error('Seat segment allocated update failed!');
            }
        })
    }

    const handleChangeRate = (id, rate) => {
        console.log(id);

        if (updateSeatSegmentRateQueryIsLoading) return false;

        function isInt(n){
            return Number(n) === n && n % 1 === 0;
        }
        
        function isFloat(n){
            return Number(n) === n && n % 1 !== 0;
        }

        if (isInt(rate) || isFloat(rate)) {
            // console.log(rate);
            updateSeatSegmentRateQuery({
                id: id,
                rate: rate,
            }, {
                onSuccess: (res) => {
                    console.log(res);
                },
                onError: (e) => {
                    console.log(e);
                }
            })
        }
    }

    const [statusForPrinting, setStatusForPrinting] = React.useState([]);
    const [printManifestModalVisible, setprintManifestModalVisible] = React.useState(false);
    const [tripNumberToPrint, settripNumberToPrint] = React.useState(null);

    const handlePrintManifest = (trip_number, status) => {

        const formData = {
            tripNumbers: trip_number,
            status: status,
            };
        
        const dateFilename = `${trip_number}-(${status.join(',')})`;

        downloadReport(formData, dateFilename);
    }

    const [updateScheduleModalVisible, setUpdateScheduleModalVisible] = React.useState(false);
    const transportationListQuery = TransportationService.list();
    
    const [updateScheduleForm] = Form.useForm();

    React.useEffect( () => {
        if (!updateScheduleModalVisible) {
            updateScheduleForm.resetFields();
        } else {

           updateScheduleForm.setFieldsValue();
        }
    },[updateScheduleModalVisible])

    const onUpdateScheduleFormFinish = (values) => {

        if (updateScheduleQueryIsLoading) {
            message.warning('Saving in progress...');
            return false;
        }

        console.log(values, moment(values.start_time).format('YYYY-MM-DD HH:mm:ss'));

        const newValues = {
            ...values,
            start_time: moment(values.start_time).format('YYYY-MM-DD HH:mm:ss'),
            end_time: moment(values.end_time).format('YYYY-MM-DD HH:mm:ss'),
        }
        
        updateScheduleQuery(newValues, {
            onSuccess: res => {
                console.log(res);

                updateScheduleForm.resetFields();

                setUpdateScheduleModalVisible(false);
                scheduleListQuery.refetch();

                notification.success({
                    message: 'Update schedule success!',
                    description:
                      '',
                  });
            },
            
            onError: (e) => {
                message.error(e.error);
                updateScheduleQueryReset();
            }
        });
    }

    const DropdownMenu = (record) => {
    
        const handleTripNumberClick = (record) => {
            console.log(record);
            setprintManifestModalVisible(true);
            settripNumberToPrint(record.trip_number);
        }

        const handleUpdateSchedule = (record) => {
            updateScheduleForm.resetFields();
            updateScheduleForm.setFieldsValue({
                trip_number: record.trip_number,
                trip_date: record.trip_date,
                start_time: moment(record.trip_date+" "+record.start_time),
                end_time: moment(record.trip_date+" "+record.end_time),
                origin: record.origin,
                destination: record.destination,
            });
            setUpdateScheduleModalVisible(true);
        }
        
        return (
          <Menu>
            <Menu.Item onClick={ () => handleTripNumberClick(record)}>Print manifest</Menu.Item>
            <Menu.Item onClick={ () => handleUpdateSchedule(record)}>Edit trip details</Menu.Item>
          </Menu>
        )
    }

    const locationListQuery = TransportationLocationService.list();
    const routeListQuery = TransportationRouteService.list();

    const [routeList, setrouteList] = React.useState(routeListQuery.data);

    const classPerStatus = {
        for_review: "schedule-list for-review",
        active: "schedule-list active",
        closed: "schedule-list closed",
        cancelled: "schedule-list cancelled",
    }


    React.useEffect( () => {
        setrouteList(routeListQuery.data);
    }, [routeListQuery.data]);

    return (
        <div className="mt-2">

            <Modal
                visible={printManifestModalVisible}
                title="Print manifest"
                onCancel={()=>setprintManifestModalVisible(false)}
                footer={null}
            >
                <p>Select status to print</p>
                <Select value={statusForPrinting} onChange={e=>setStatusForPrinting(e)} placeholder="Select status" mode="multiple" className="mt-2" style={{width: '100%'}}>
                    <Select.Option value="arriving">Confirmed booking</Select.Option>
                    <Select.Option value="pending">Pending</Select.Option>
                    <Select.Option value="checked_in">Checked-in</Select.Option>
                    <Select.Option value="boarded">Boarded</Select.Option>
                    <Select.Option value="no_show">No show</Select.Option>
                    <Select.Option value="cancelled">Cancelled</Select.Option>
                </Select>
                <div style={{textAlign: 'right'}}>
                    <Button type="primary" className="mt-4" style={{marginLeft:'auto'}} disabled={statusForPrinting.length == 0} onClick={()=>handlePrintManifest(tripNumberToPrint, statusForPrinting)}>
                        <PrinterOutlined/> Print
                    </Button>
                </div>
            </Modal>

            <Modal
                title={<Typography.Title level={4}>Edit trip details</Typography.Title>}
                visible={updateScheduleModalVisible}
                onOk={() => updateScheduleForm.submit()}
                onCancel={()=>setUpdateScheduleModalVisible(false)}
                footer={null}
            >
                <Form
                    form={updateScheduleForm}
                    onFinish={onUpdateScheduleFormFinish}
                    layout="vertical"
                    scrollToFirstError={true}
                >
                    <Row>
                        <Form.Item name="id" noStyle>
                            <Input type="hidden" />
                        </Form.Item>
                        <Col xl={12}>
                            <Form.Item
                                name="trip_number"
                                label="Trip Number"
                            >
                                <Input disabled/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="trip_date"
                                label="Trip Date"
                            >
                                <Input disabled/>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="start_time"
                                label="ETD"
                                rules={[{required:true}]}
                            >
                               <TimePicker disabledSeconds={() => _.range(60)} minuteStep={5} />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="end_time"
                                label="ETA"
                                rules={[{required:true}]}
                            >
                                <TimePicker disabledSeconds={() => _.range(60)} minuteStep={5} />
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="origin"
                                label="Origin"
                                rules={[{ required: true } ]}
                            >
                                <Select>
                                    {
                                        locationListQuery.data && locationListQuery.data.map( (item, key) => (
                                            <Select.Option key={key} value={item.code}>{item.code}</Select.Option>
                                        ))
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        <Col xl={12}>
                            <Form.Item
                                name="destination"
                                label="Destination"
                                rules={[{ required: true } ]}
                            >
                                <Select>
                                    {
                                        locationListQuery.data && locationListQuery.data.map( (item, key) => (
                                            <Select.Option key={key} value={item.code}>{item.code}</Select.Option>
                                        ))
                                    }
                                </Select>
                            </Form.Item>
                        </Col>
                        {/* <Col xl={24}>
                            <Form.Item 
                                label="Route (Origin -> Destination)" 
                                name="route_id" 
                                rules={[{required:true}]}>
                                <Select>
                                    {
                                        routeListQuery.data && routeListQuery.data.map((item, key) => {
                                            return <Select.Option key={key} value={item.id}><span className="text-primary">{item.origin.code} &ndash;&gt; {item.destination.code}</span><br/>{item.origin.name} &ndash;&gt; {item.destination.name}</Select.Option>
                                        })
                                    }
                                </Select>
                            </Form.Item>
                        </Col> */}
                        {/* <Col xl={24}>
                            <Form.Item 
                                name="transportation_id"
                                label="Vessel"
                            >
                                { 
                                    transportationListQuery.data && transportationListQuery.data.map((item, key) => {
                                        return  <Input key={key} value={item.name} disabled/>
                                    })
                                }
                            </Form.Item>
                        </Col> */}
                        <Col xl={24} align="right">
                            <Button htmlType='submit'>Save</Button>
                        </Col>
                    </Row>
                </Form>
            </Modal>

            <Modal
                visible={generateSchedulesModalVisible}
                title="Generate Transportation Schedules"
                width="98%"
                onCancel={()=>setgenerateSchedulesModalVisible(false)}
                footer={null}
                >
                    <GenerateSchedules generateSchedulesModalVisible={generateSchedulesModalVisible} setgenerateSchedulesModalVisible={setgenerateSchedulesModalVisible}/>
            </Modal>

            <Modal
                visible={addSeatSegmentModalVisible}
                title="Add seat segment"
                onCancel={()=>setaddSeatSegmentModalVisible(false)}
                footer={null}
                width={1000}
                >
                    <AddSegmentForm refetchSchedule={() => scheduleListQuery.refetch()} setaddSeatSegmentModalVisible={setaddSeatSegmentModalVisible} item={addSeatSegmentData.item} schedule={addSeatSegmentData.schedule} />
            </Modal>

            <Modal
                visible={addSeatAllocationModalVisible}
                title="Add seat allocation"
                onCancel={()=>setaddSeatAllocationModalVisible(false)}
                footer={null}
                width={1000}
                >
                    <AddSeatAllocationForm refetchSchedule={() => scheduleListQuery.refetch()} setaddSeatAllocationModalVisible={setaddSeatAllocationModalVisible} item={addSeatAllocationData} />
            </Modal>

            <div style={{display:'flex', alignItems:'center', justifyContent:'space-between'}}>
                <Typography.Title level={4}>Select Schedules</Typography.Title>
                
                <div><Button onClick={()=>setgenerateSchedulesModalVisible(true)}>Generate Schedules</Button><Button className="ml-2" type="primary" onClick={() => scheduleListQuery.refetch() }><ReloadOutlined /></Button></div>
            </div>
            <DatePicker allowClear={false} defaultValue={moment()} onChange={e => setDate(e.format('YYYY-MM-DD'))}/>

            <Table
                style={{borderRadius: 8}}
                dataSource={scheduleListQuery.data && scheduleListQuery.data}
                loading={scheduleListQuery.isFetching}
                rowKey="id"
                rowClassName={(record, index) => classPerStatus[record.status]}
                columns={[
                    {
                        title: 'Trip number',
                        // dataIndex: 'trip_number',
                        // key: 'trip_number',
                        render: (text, record) => <b>{record.trip_number}</b>
                    },
                    // {
                    //     title: 'Trip date',
                    //     dataIndex: 'trip_date',
                    //     key: 'trip_date'
                    // },
                    {
                        title: 'Trip date / ETD ~ ETA',
                        render: (text, record) => <>
                            <div>{record.trip_date}</div>
                            <small>{moment(record.trip_date+" "+record.start_time).format("hh:mmA") + " ~ " + moment(record.trip_date+" "+record.end_time).format("hh:mmA")}</small>

                        </>
                    },
                    // {
                    //     title: 'ETA',
                    //     dataIndex: 'end_time',
                    //     key: 'end_time'
                    // },
                    {
                        title: 'Active seat',
                        render: (text, record) => <><div>{record.transportation.active_seats_count}</div><small>(open: {record.transportation.active_seats_count - record.allocated_seat})</small></>
                    },
                    {
                        title: 'Alloc. seat',
                        dataIndex: 'allocated_seat',
                        key: 'allocated_seat'
                    },
                    {
                        title: 'Total used',
                        render: (text, record) => <>
                            <div>{_.sumBy(record.seat_segments, 'used')}</div>
                            <div><small>(boarded: {record.boarded_adult_kid} infant: {record.boarded - record.boarded_adult_kid })</small></div>
                            <div><small>(checked-in: {record.checked_in_adult_kid} infant: {record.checked_in - record.checked_in_adult_kid })</small></div> 
                        </>
                    },
                    {
                        title: 'Route',
                        render: (text, record) => record.origin +" -> "+ record.destination
                    },
                    // {
                    //     title: 'Origin',
                    //     dataIndex: 'origin',
                    //     key: 'origin',
                    // },
                    // {
                    //     title: 'Destination',
                    //     dataIndex: 'destination',
                    //     key: 'destination'
                    // },
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
                        render: (text, record) => {
                            return <Select onChange={(e) => handleScheduleStatusChange(record.id, e)} style={{width: '100%'}} value={record.status}>
                                        <Select.Option value="for_review"><span style={{color:'orange'}}>For review</span></Select.Option>
                                        <Select.Option value="active"><span style={{color:'limegreen'}}>Active</span></Select.Option>
                                        <Select.Option value="closed"><span style={{color:'crimson'}}>Closed</span></Select.Option>
                                        <Select.Option value="cancelled"><span style={{color:'crimson'}}>Cancelled</span></Select.Option>
                                    </Select>
                        },
                        filters: [
                            { text: 'For Review', value: 'for_review' },
                            { text: 'Active', value: 'active' },
                            { text: 'Closed', value: 'closed' },
                            { text: 'Cancelled', value: 'cancelled' },
                        ],
                        defaultFilteredValue: ['for_review', 'active'],
                        onFilter: (value, record) => record.status.includes(value),
                    },
                    {
                        title: 'Action',
                        render: (text, record) => <Dropdown overlay={DropdownMenu(record)} placement="bottomLeft"><Button icon={<EllipsisOutlined/>} /></Dropdown>,
                      },
                ]}
                expandable={{
                    expandedRowRender: record => 
                        <>
                            <>
                                <Typography.Text strong className="mr-2">Allocations</Typography.Text>
                                <Button size="small" onClick={() => handleAddSeatAllocation(record)}>Add allocation to {record.trip_number}</Button>
                            </>
                            <Table
                                size="small"
                                rowKey="id"
                                dataSource={record.seat_allocations}
                                columns={[
                                    {
                                        title: 'Name',
                                        dataIndex: 'name',
                                        key: 'name',
                                    },
                                    {
                                        title: 'Quantity',
                                        dataIndex: 'quantity',
                                        key: 'quantity',
                                        render: (text, allocationRecord) => <>
                                            <Select onChange={(e) => handleSeatAllocationQuantityChange(allocationRecord.id, e)} defaultValue={allocationRecord.quantity}>  
                                                {
                                                    _.map(_.range(parseInt(_.sumBy(allocationRecord.segments, 'allocated')),(record.transportation.active_seats_count - record.allocated_seat + 1 + (allocationRecord.quantity))), (i,key) => {
                                                        return <Select.Option key={key} value={i}>{i}</Select.Option>
                                                    })
                                                }
                                            </Select>
                                        </>
                                    },
                                    {
                                        title: 'Total allocated',
                                        dataIndex: 'total_used',
                                        key: 'total_used',
                                        render: (text, allocationRecord) => <>{_.sumBy(allocationRecord.segments, 'allocated')}</>
                                    },
                                    {
                                        title: 'Total used',
                                        dataIndex: 'total_used',
                                        key: 'total_used',
                                        render: (text, allocationRecord) => <>{_.sumBy(allocationRecord.segments, 'used')}</>
                                    },
                                    {
                                        title: 'Open',
                                        dataIndex: 'open',
                                        key: 'open',
                                        render: (text, allocationRecord) => <>{allocationRecord.quantity - _.sumBy(allocationRecord.segments, 'allocated')}</>
                                    },
                                    {
                                        title: 'Allowed roles to manage',
                                        dataIndex: 'allowed_roles',
                                        key: 'allowed_roles',
                                        render: (text, allocationRecord) => <Select onChange={(e) => handleSeatAllocationAllowedRoles(allocationRecord.id, e)} style={{width:'100%'}} defaultValue={allocationRecord.allowed_roles || []} mode="multiple">
                                                        {
                                                            roleListQuery.data && roleListQuery.data.map((item, key) => {
                                                                return <Select.Option key={key} value={item.name}>{item.name}</Select.Option>
                                                            })
                                                        }
                                                    </Select>
                                    }
                                ]}
                                expandable={{
                                    expandedRowRender: segmentRecord => {
                                        return <>
                                            <>
                                                <Typography.Text strong className="mr-2">{segmentRecord.name} Segments / Class</Typography.Text>
                                                <Button size="small" onClick={() => handleAddSeatSegment(segmentRecord, record)}>Add segment to {segmentRecord.name}</Button>
                                            </>
                                            <Card size="small" className="mt-2">
                                            <Table
                                                
                                                rowKey="id"
                                                size="small"
                                                dataSource={segmentRecord.segments}
                                                columns={[
                                                    {
                                                        title: 'Status',
                                                        dataIndex: 'status',
                                                        key: 'status',
                                                        render: (text, segment) => {
                                                            return <Select onChange={(e) => handleSeatSegmentStatusChange(segment.id, e)} defaultValue={segment.status}>
                                                                <Select.Option value="published"><span style={{color: 'limegreen'}}>Published</span></Select.Option>
                                                                <Select.Option value="unpublished"><span style={{color: 'orange'}}>Unpublished</span></Select.Option>
                                                                <Select.Option value="locked"><span style={{color: '#000'}}>Locked</span></Select.Option>
                                                            </Select>
                                                        }
                                                    },
                                                    {
                                                        title: 'Name',
                                                        dataIndex: 'name',
                                                        key: 'name',
                                                    },
                                                    {
                                                        title: 'Allocated',
                                                        dataIndex: 'allocated',
                                                        key: 'allocated',
                                                        render: (text, segment) => <>
                                                            <Select onChange={(e) => handleSeatSegmentAllocatedChange(segment.id, e)} defaultValue={segment.allocated}>  
                                                                {
                                                                    _.map(_.range(segment.used,(segmentRecord.quantity - _.sumBy(segmentRecord.segments, 'allocated') + 1 + segment.allocated)), (i,key) => {
                                                                        return <Select.Option key={key} value={i}>{i}</Select.Option>
                                                                    })
                                                                }
                                                                {/* <Select.Option value={-1}>-1</Select.Option> */}
                                                                {/* <Select.Option value={3}>3</Select.Option> */}
                                                            </Select>
                                                        </>
                                                    },
                                                    {
                                                        title: 'Active',
                                                        dataIndex: 'active',
                                                        key: 'active',
                                                    },
                                                    {
                                                        title: 'Used',
                                                        dataIndex: 'used',
                                                        key: 'used',
                                                    },
                                                    {
                                                        title: <><small className="text-danger">*</small> Rate</>,
                                                        // dataIndex: 'rate',
                                                        key: 'rate',
                                                        render: (text, segment) => {
                                                            return <><InputNumber required defaultValue={segment.rate} min={0} onChange={e => handleChangeRate(segment.id, e)} /></>
                                                        }
                                                    },
                                                    {
                                                        title: 'Booking type',
                                                        dataIndex: 'booking_type',
                                                        key: 'booking_type',
                                                        render: (text, segment) => <>
                                                            <Select onChange={(e) => handleSeatSegmentBookingTypesChange(segment.id, e)} style={{width:'100%'}} defaultValue={segment.booking_type} mode="multiple">
                                                                <Select.Option value="DT">Day Tour</Select.Option>
                                                                <Select.Option value="ON">Overnight</Select.Option>
                                                            </Select>
                                                        </>
                                                    },
                                                    {
                                                        title: 'Allowed roles to use',
                                                        dataIndex: 'allowed_roles',
                                                        key: 'allowed_roles',
                                                        render: (text, segment) => <>
                                                            <Select onChange={(e) => handleSeatSegmentAllowedRolesChange(segment.id, e)} style={{width:'100%'}} defaultValue={_.map(segment.allowed_roles, 'role_id') || []} mode="multiple">
                                                                {
                                                                    roleListQuery.data && roleListQuery.data.map((item, key) => {
                                                                        return <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                                                                    })
                                                                }
                                                            </Select>
                                                        </>
                                                    },
                                                    {
                                                        title: 'Allowed users to use',
                                                        dataIndex: 'allowed_users',
                                                        key: 'allowed_users',
                                                        render: (text, segment) => <>
                                                            <Select  onChange={(e) => handleSeatSegmentAllowedUsersChange(segment.id, e)} style={{width:'100%'}} defaultValue={_.map(segment.allowed_users, 'user_id')} mode="multiple">
                                                                {
                                                                    userListQuery.data && userListQuery.data
                                                                    .filter( i => i.roles[0].name == 'POC Agent' || i.roles[0].name == 'Sales Director')
                                                                    .map((item, key) => {
                                                                        return <Select.Option key={key} value={item.id}>{item.first_name} {item.last_name}</Select.Option>
                                                                    })
                                                                }
                                                            </Select>
                                                        </>
                                                    },
                                                    {
                                                        title: 'Link',
                                                        dataIndex: 'link',
                                                        key: 'link',
                                                        render: (text, segment) => {
                                                                    return <Select defaultValue={segment.trip_link} onChange={(e) => handleSeatSegmentLinkChange(segment.id, e)} style={{width:'100%'}} placeholder="Trip link">
                                                                        <Select.Option value="">No link</Select.Option>
                                                                        <Select.Option value="LINK1">Link 1</Select.Option>
                                                                        <Select.Option value="LINK2">Link 2</Select.Option>
                                                                        <Select.Option value="LINK3">Link 3</Select.Option>
                                                                        <Select.Option value="LINK4">Link 4</Select.Option>
                                                                        <Select.Option value="LINK5">Link 5</Select.Option>
                                                                        <Select.Option value="LINK6">Link 6</Select.Option>
                                                                        <Select.Option value="LINK7">Link 7</Select.Option>
                                                                        <Select.Option value="LINK8">Link 8</Select.Option>
                                                                        <Select.Option value="LINK9">Link 9</Select.Option>
                                                                        <Select.Option value="LINK10">Link 10</Select.Option>
                                                                        <Select.Option value="LINK11">Link 11</Select.Option>
                                                                        <Select.Option value="LINK12">Link 12</Select.Option>
                                                                        <Select.Option value="LINK13">Link 13</Select.Option>
                                                                        <Select.Option value="LINK14">Link 14</Select.Option>
                                                                        <Select.Option value="LINK15">Link 15</Select.Option>
                                                                        <Select.Option value="LINK16">Link 16</Select.Option>
                                                                        <Select.Option value="LINK17">Link 17</Select.Option>
                                                                        <Select.Option value="LINK18">Link 18</Select.Option>
                                                                        <Select.Option value="LINK19">Link 19</Select.Option>
                                                                        <Select.Option value="LINK20">Link 20</Select.Option>
                                                                        <Select.Option value="LINK21">Link 21</Select.Option>
                                                                        <Select.Option value="LINK22">Link 22</Select.Option>
                                                                        <Select.Option value="LINK23">Link 23</Select.Option>
                                                                        <Select.Option value="LINK24">Link 24</Select.Option>
                                                                        <Select.Option value="LINK25">Link 25</Select.Option>
                                                                        <Select.Option value="LINK26">Link 26</Select.Option>
                                                                        <Select.Option value="LINK27">Link 27</Select.Option>
                                                                        <Select.Option value="LINK28">Link 28</Select.Option>
                                                                        <Select.Option value="LINK29">Link 29</Select.Option>
                                                                        <Select.Option value="LINK30">Link 30</Select.Option>

                                                                        <Select.Option value="LINK31">Link 31</Select.Option>
                                                                        <Select.Option value="LINK32">Link 32</Select.Option>
                                                                        <Select.Option value="LINK33">Link 33</Select.Option>
                                                                        <Select.Option value="LINK34">Link 34</Select.Option>
                                                                        <Select.Option value="LINK35">Link 35</Select.Option>
                                                                        <Select.Option value="LINK36">Link 36</Select.Option>
                                                                        <Select.Option value="LINK37">Link 37</Select.Option>
                                                                        <Select.Option value="LINK38">Link 38</Select.Option>
                                                                        <Select.Option value="LINK39">Link 39</Select.Option>
                                                                        <Select.Option value="LINK40">Link 40</Select.Option>
                                                                        <Select.Option value="LINK41">Link 41</Select.Option>
                                                                        <Select.Option value="LINK42">Link 42</Select.Option>
                                                                        <Select.Option value="LINK43">Link 43</Select.Option>
                                                                        <Select.Option value="LINK44">Link 44</Select.Option>
                                                                        <Select.Option value="LINK45">Link 45</Select.Option>
                                                                        <Select.Option value="LINK46">Link 46</Select.Option>
                                                                        <Select.Option value="LINK47">Link 47</Select.Option>
                                                                        <Select.Option value="LINK48">Link 48</Select.Option>
                                                                        <Select.Option value="LINK49">Link 49</Select.Option>
                                                                        <Select.Option value="LINK50">Link 50</Select.Option>
                                                                        <Select.Option value="LINK51">Link 51</Select.Option>
                                                                        <Select.Option value="LINK52">Link 52</Select.Option>
                                                                        <Select.Option value="LINK53">Link 53</Select.Option>
                                                                        <Select.Option value="LINK54">Link 54</Select.Option>
                                                                        <Select.Option value="LINK55">Link 55</Select.Option>
                                                                        <Select.Option value="LINK56">Link 56</Select.Option>
                                                                        <Select.Option value="LINK57">Link 57</Select.Option>
                                                                        <Select.Option value="LINK58">Link 58</Select.Option>
                                                                        <Select.Option value="LINK59">Link 59</Select.Option>
                                                                        <Select.Option value="LINK60">Link 60</Select.Option>
                                                                    </Select>
                                                        }
                                                    }
                                                ]}
                                            />
                                            </Card>
                                        </>
                                    }
                                }}
                             />
                        </>,
                    // rowExpandable: record => record.seat_segments.length !== 0,
                }}
            />
        </div>
    )
}