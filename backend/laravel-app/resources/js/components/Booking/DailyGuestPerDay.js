import React, {useState, useEffect} from 'react'
import DashboardService from 'services/Booking/DashboardService';

import moment from 'moment-timezone'
moment.tz.setDefault('Asia/Manila');

import { Typography, Select, Form, Modal, Button, Row, Col, message, DatePicker, Alert, InputNumber, notification, Table, Input } from 'antd'
import { EditOutlined, EyeOutlined, LoadingOutlined } from '@ant-design/icons';

const months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
const years = [moment().add(-1,'year').year(), moment().year(), moment().add(1,'year').year()];

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

const getDays = (params) => {
    
    let days = [];

    if (params) {

        params.forEach( (d, i) => {
            days.push(moment(d).clone().format('dddd').toLocaleLowerCase());
        });
    }

    return _.uniq(days);

}

const DailyGuestLimitForm = ({formName, onFinish}) => {

    const [selectedDates, setSelectedDates] = useState([]);
    const [days, setDays] = useState([]);

    useEffect(() => {
        // console.log(selectedDates);

        if (selectedDates && selectedDates.length > 1 && (selectedDates[0] && selectedDates[1])) {
            setDays(getDays(_.uniq(enumerateDaysBetweenDates(selectedDates[0], selectedDates[1]))));
        }

    }, [selectedDates]);

    useEffect(() => {
        //
        formName.setFieldsValue({
            daily_guest_limits: [
                {
                    category: 'Admin',
                },
                {
                    category: 'Commercial',
                },
                {
                    category: 'Sales',
                }
            ]
        })
    },[]);

    return (
        <Form
            layout="vertical"
            form={formName}
            onFinish={onFinish}
        >
            <Row gutter={[8,8]}>
                <Col xl={24}>
                    <Alert type="info" message="This form will only create new records and skip existing dates with limit. Edit existing records in the calendar view."/>
                </Col>
                <Col xl={9}>
                    <Form.Item label="Date range" name="range" rules={[{ required: true }]}>
                        <DatePicker.RangePicker onCalendarChange={(e) => setSelectedDates(e)} />
                    </Form.Item>
                </Col>
                <Col xl={24}>
                    <Form.List rules={[{required:true}]} name="daily_guest_limits">
                        {(fields, {add, remove}) => (
                            <>
                            {fields.map(field => (
                                <Row gutter={[12,12]} key={field.key}>
                                    <Col xl={4}>
                                        <Form.Item label='Category' disabled={true} name={[field.name, 'category']} rules={[{ required: true }]}>
                                            <Select disabled>
                                                <Select.Option value="Admin">Admin</Select.Option>
                                                <Select.Option value="Commercial">Commercial</Select.Option>
                                                <Select.Option value="Sales">Sales</Select.Option>
                                            </Select>
                                        </Form.Item>
                                    </Col>
                                    <Col xl={20}>
                                        <Row>
                                            <Col xl={1}></Col>
                                            {
                                                days.includes('monday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Monday" name={[field.name, 'monday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                            {
                                                days.includes('tuesday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Tuesday" name={[field.name, 'tuesday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                            {
                                                days.includes('wednesday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Wednesday" name={[field.name, 'wednesday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                            {
                                                days.includes('thursday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Thursday" name={[field.name, 'thursday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                            {
                                                days.includes('friday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Friday" name={[field.name, 'friday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                            {
                                                days.includes('saturday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Saturday" name={[field.name, 'saturday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                            {
                                                days.includes('sunday') &&
                                                <Col xl={3}>
                                                    <Form.Item label="Sunday" name={[field.name, 'sunday']}>
                                                        <InputNumber min={0} max={1000} />
                                                    </Form.Item>
                                                </Col>
                                            }
                                        </Row>
                                    </Col>
                                    
                                </Row>
                            ))}
                            </>
                        )}
                    </Form.List>
                </Col>
            </Row>
            <Button className="mt-3 ant-btn ant-btn-primary" htmlType="submit">Save</Button>
        </Form>
    )
}

export default () => {

    const [selectedMonth, setSelectedMonth] = useState(moment().format('MMMM'));
    const [selectedYear, setSelectedYear] = useState(moment().format('YYYY'));
    // const [period, setPeriod] = useState(enumerateDaysBetweenDates(moment(selectedMonth+" "+selectedYear).startOf('month').format('YYYY-MM-DD'), moment(selectedMonth+" "+selectedYear).endOf('month').format('YYYY-MM-DD' )));
    const [period, setPeriod] = useState([]);
    const [dailyGuestModalVisible, setDailyGuestModalVisible] = useState(false);
    const [editDailyGuestModalVisible, setEditDailyGuestModalVisible] = useState(false);
    // const [remarksModalVisible, setRemarksModalVisible] = useState(false);

    const [dailyGuestForm] = Form.useForm();
    const [editDailyGuestForm] = Form.useForm();
    const [remarksForm] = Form.useForm();


    const dailyGuestLimitPerDayQuery = DashboardService.dailyGuestLimitPerDay(selectedMonth, selectedYear);

    const [dailyGuestPerDayQuery, { isLoading: dailyGuestPerDayQueryIsLoading, reset: dailyGuestPerDayQueryReset }] = DashboardService.generateDailyGuestPerDay();
    const [editDailyGuestPerDayQuery, { isLoading: editDailyGuestPerDayQueryIsLoading, reset: editDailyGuestPerDayQueryReset }] = DashboardService.editDailyGuestPerDay();
    // const [updateRemarksQuery, { isLoading: updateRemarksQueryIsLoading, reset: updateRemarksQueryReset }] = DashboardService.updateRemarks();


    useEffect(() => {
        // console.log('changed')
        const d = moment(selectedMonth+" "+selectedYear).startOf('month').format('ddd').toLocaleLowerCase();
        const add_days = {mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6, sun: 0};

        setPeriod(enumerateDaysBetweenDates(moment(selectedMonth+" "+selectedYear).startOf('month').subtract(add_days[d], 'days').format('YYYY-MM-DD'), moment(selectedMonth+" "+selectedYear).endOf('month').format('YYYY-MM-DD' )))

        /**
         * On change of month and year,
         * Update calendar view of Daily Guest Limit Per Day
         */
        // Do it here
        dailyGuestLimitPerDayQuery.refetch();

        console.log(moment(selectedMonth+" "+selectedYear).startOf('month').format('ddd'));

        // Set daily limit records here from query

    }, [selectedMonth, selectedYear])

    const onDailyGuestFormFinish = values => {
        console.log(values);

        if (dailyGuestPerDayQueryIsLoading) return false;

        let ans = confirm("Are you sure you want to save daily guest limit?");

        if (!ans) {

            // message.warning("")

            return false;
        }

        dailyGuestPerDayQuery(values, {
            onSuccess: (res) => {
                console.log(res);

                dailyGuestForm.resetFields();
                setDailyGuestModalVisible(false);
                dailyGuestLimitPerDayQuery.refetch()
            
                if (res.data.saved_data.length) {
                    notification.success({
                        message: `${res.data.saved_data.length} new daily guest limit added`,
                        description:
                            ``
                    });
                }

                if (res.data.skipped.length) {
                    Modal.confirm({
                        title: 'Skipped saving record',
                        width: 500,
                        content: (
                            <>
                                {res.data.saved_data && <Alert success className="mb-2" message={`${res.data.saved_data.length} records saved.`} /> }
                                <Alert className="mb-2" message="Skipped daily guest limit. These items already exist in our records. Please apply corresponding change manually."/>
                                <Table
                                    dataSource={res.data.skipped}
                                    rowKey="id"
                                    columns={[
                                        {
                                            title: 'Category',
                                            dataIndex: 'category',
                                            key: 'category',
                                        },
                                        {
                                            title: 'Date',
                                            dataIndex: 'date',
                                            key: 'date',
                                            render: (text, record) => <>{moment(record.date).format('YYYY-MM-DD')}</>
                                        },
                                        {
                                            title: 'Limit',
                                            render: (text, record) => <>{record.limit}</>
                                        },
                                    ]}
                                    />
                            </>
                        )
                    })
                }


            },
            onError: (e) => {
                console.log(e)
                dailyGuestPerDayQueryReset();
                message.warning(e.message);
            }
        });

    }

    const handleEditDailyGuestLimit = (date, records, note) => {
        // console.log(date, records)
        setEditDailyGuestModalVisible(true);

        editDailyGuestForm.setFieldsValue({
            date: date,
            admin: _.find(records, i => i.category == 'Admin')?.limit ?? null,
            commercial: _.find(records, i => i.category == 'Commercial')?.limit ?? null,
            sales: _.find(records, i => i.category == 'Sales')?.limit ?? null,
            remarks: note?.note,
        });

    }

    const handleRemarksClick = (date, record) => {
        // console.log(date, record)
        setRemarksModalVisible(true);

        remarksForm.setFieldsValue({
            date: date,
            remarks: record?.note,
        });

    }

    return (<>
    
        <Typography.Title level={4}>Daily Guest Limit</Typography.Title>

        <Select style={{width:300}} value={selectedMonth} onChange={m => setSelectedMonth(m)}>
            {
                months.map( month => <Select.Option key={month} value={month}>{month}</Select.Option>)
            }
        </Select>

        <Select className="ml-2" style={{width:100}} value={selectedYear} onChange={y => setSelectedYear(y)}>
            {
                years.map( year => <Select.Option key={year} value={year}>{year}</Select.Option>)
            }
        </Select>

        <Button style={{float: 'right'}} onClick={() => setDailyGuestModalVisible(true)}>Generate Daily Guest Limit Per Day</Button>

        
        <div style={{marginTop:36, borderRadius: 8, border:'solid 1px gainsboro', overflow:'hidden'}}>
        <table style={{width: '100%'}}>
            <tbody>
            {
                _.chunk(period, 7).map( (week, weekNum) => {

                    return <tr key={weekNum}>{ week.map( (d,index) => {

                        const records = _.filter(dailyGuestLimitPerDayQuery.data ? dailyGuestLimitPerDayQuery.data.guest_limits : [], i => i.date == d);

                        const note = _.find(dailyGuestLimitPerDayQuery.data ? dailyGuestLimitPerDayQuery.data.notes : [], i => moment(i.date).format('YYYY-MM-DD') == moment(d).format('YYYY-MM-DD'));
                    
                        return (<td key={index} valign="top" className="p-4" style={{borderRight:'solid 1px gainsboro', borderBottom:'solid 1px gainsboro'}}>
                                <div style={{marginBottom:12, position: 'relative'}}>
                                    { moment().format('D MMM ddd') == moment(d).format('D MMM ddd') ? <div style={{position: 'absolute', top: -16, left: 0, color: 'green'}}><small>today</small></div> : <></>}
                                    <small><b style={{ color: moment().format('D MMM ddd') == moment(d).format('D MMM ddd') ? 'green' : moment(d).format('MMMM') != selectedMonth ? 'gainsboro' : '' }}>{moment(d).format('D MMM ddd')}</b></small>
                                    { moment(d).format('MMMM') == selectedMonth ? <Button disabled={moment(d).isBefore(moment().format('YYYY-MM-DD'))} onClick={()=>handleEditDailyGuestLimit(moment(d).format('YYYY-MM-DD'), records, note)} icon={<EditOutlined/>} size="small" style={{float: 'right'}} /> : '' }
                                </div>

                                { moment(d).format('MMMM') == selectedMonth ?
                                    <>
                                        <Typography.Paragraph ellipsis={true} className="mt-2" style={{fontSize: '0.75rem', width: 80, height: 20}}>{note ? (note.note) : <>&nbsp;</>}</Typography.Paragraph>

                                        {(dailyGuestLimitPerDayQuery.isFetching) ?
                                            <div style={{textAlign: 'right'}}>
                                                <small>Admin:</small> <small><LoadingOutlined spin size="small" /></small><br/>
                                                <small>Commercial:</small> <small><LoadingOutlined spin size="small" /></small><br/>
                                                <small>Sales:</small> <small><LoadingOutlined spin size="small" /></small><br/>
                                            </div>
                                        :
                                            <div style={{textAlign: 'right'}}>
                                                <small>Admin: <span className='text-danger'>{ _.find(records, i => i.category == 'Admin')?.limit >= 0 ? _.find(records, i => i.category == 'Admin')?.limit : '-' }</span></small><br/>
                                                <small>Commercial: <span className='text-danger'>{ _.find(records, i => i.category == 'Commercial')?.limit >= 0 ? _.find(records, i => i.category == 'Commercial')?.limit : '-' }</span></small><br/>
                                                <small>Sales: <span className='text-danger'>{ _.find(records, i => i.category == 'Sales')?.limit >= 0 ? _.find(records, i => i.category == 'Sales')?.limit : '-' }</span></small><br/>
                                            </div>
                                        }
                                        
                                        <div style={{textAlign:'right', lineHeight: 1, borderBottom: 'dashed 1px gainsboro'}} className="mt-2 mb-2"><small className='text-secondary'>total</small>&nbsp;<b className='text-warning'>{_.sumBy(records, i => i.limit)}</b></div>
                                    </> : <></>
                                }
                                {/* <Button onClick={() => handleRemarksClick(moment(d).format('YYYY-MM-DD'), note)} icon={<EditOutlined/>} size="small" className={`mt-0 ${note ? 'text-primary' : ''}`}><small>&nbsp; remarks</small></Button> */}
                            </td>)} ) }</tr>

                })
            }
            </tbody>
        </table>
        </div>
            
        
        { dailyGuestModalVisible &&
            <Modal
                title="Generate Daily Guest Limit"
                visible={dailyGuestModalVisible}
                onCancel={()=>setDailyGuestModalVisible(false)}
                footer={null}
                width={1000}
            >
                <DailyGuestLimitForm
                    formName={dailyGuestForm}
                    onFinish={onDailyGuestFormFinish}
                />
            </Modal>
        }

        {/* { editDailyGuestModalVisible && */}
            <Modal
                title="Edit Daily Guest Limit"
                visible={editDailyGuestModalVisible}
                onCancel={() => setEditDailyGuestModalVisible(false)}
                footer={null}
                width={500}
            >
                <>
                    <Form
                        form={editDailyGuestForm}
                        layout="vertical"
                        onFinish={(values) => {
                            if (editDailyGuestPerDayQueryIsLoading) return false;

                            let ans = confirm("Are you sure you want to update daily guest limit?");

                            if (!ans) {
                                return false;
                            }

                            editDailyGuestPerDayQuery(values, {
                                onSuccess: (res) => {
                                    console.log(res);

                                    editDailyGuestForm.resetFields();
                                    setEditDailyGuestModalVisible(false);
                                    dailyGuestLimitPerDayQuery.refetch()
                                
                                    if (res.data) {
                                        notification.success({
                                            message: `Updated daily guest limit for ${values.date}`,
                                            description:
                                                ``
                                        });
                                    }

                                },
                                onError: (e) => {
                                    console.log(e)
                                    editDailyGuestPerDayQueryReset();
                                    message.warning(e.message);
                                }
                            });
                        }}
                    >
                        <Form.Item label="Date" name='date'>
                            <Input readOnly />
                        </Form.Item>

                        <Row gutter={[12, 12]}>
                            <Col xl={8}>
                                <Form.Item label="Admin" name='admin' rules={[{required: true}, {type: 'number', min: 0}]}>
                                    <InputNumber />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Commercial" name='commercial' rules={[{required: true}, {type: 'number', min: 0}]}>
                                    <InputNumber />
                                </Form.Item>
                            </Col>
                            <Col xl={8}>
                                <Form.Item label="Sales" name='sales' rules={[{required: true}, {type: 'number', min: 0}]}>
                                    <InputNumber />
                                </Form.Item>
                            </Col>
                        </Row>

                        <Form.Item label="Remarks" name='remarks'>
                            <Input.TextArea style={{borderRadius: 6}} rows={5} />
                        </Form.Item>

                        <Button htmlType='submit'>Save</Button>
                    </Form>
                </>
            </Modal>

            {/* <Modal
                title="Remarks"
                visible={remarksModalVisible}
                onCancel={() => setRemarksModalVisible(false)}
                footer={null}
                width={500}
            >
                <>
                    <Form
                        form={remarksForm}
                        layout="vertical"
                        onFinish={(values) => {
                            if (updateRemarksQueryIsLoading) return false;

                            let ans = confirm("Are you sure you want to update daily guest limit remarks?");

                            if (!ans) {
                                return false;
                            }

                            updateRemarksQuery(values, {
                                onSuccess: (res) => {
                                    console.log(res);

                                    remarksForm.resetFields();
                                    setRemarksModalVisible(false);
                                    dailyGuestLimitPerDayQuery.refetch()
                                
                                    if (res.data) {
                                        notification.success({
                                            message: `Updated daily guest limit remarks for ${values.date}`,
                                            description:
                                                ``
                                        });
                                    }

                                },
                                onError: (e) => {
                                    console.log(e)
                                    updateRemarksQueryReset();
                                    message.warning(e.message);
                                }
                            });
                        }}
                    >
                        <Form.Item label="Date" name='date'>
                            <Input readOnly />
                        </Form.Item>

                        <Form.Item label="Remarks" name='remarks'>
                            <Input.TextArea style={{borderRadius: 6}} rows={5} />
                        </Form.Item>

                        <Button htmlType='submit'>Save</Button>
                    </Form>
                </>
            </Modal> */}
        {/* } */}
        
        
    </>
    )
} 