import React from 'react'
import UserServices from 'services/UserService'
import RoleServices from 'services/RoleService'
import moment from 'moment'
import { queryCache } from 'react-query'

import { Table, Input, message, Row, Col, Button, Drawer, Form, notification, Alert, Select, Menu, Dropdown, Typography } from 'antd'
import Icon, { EllipsisOutlined, LoadingOutlined } from '@ant-design/icons'

function List(props) {

  const { status, data, isFetching, refetch: userRefetch } = UserServices.list();
  const { data: roles } = RoleServices.list();

  const [newUserQuery, {isLoading, error}] = UserServices.create();
  const [updateUserTypeQuery, {isLoading: updateUserTypeQueryIsLoading, error: updateUserTypeQueryError}] = UserServices.updateUserType();
  const [updateUserRoleQuery, {isLoading: updateUserRoleQueryIsLoading, error: updateUserRoleQueryError}] = UserServices.updateUserRole();
  const [updateUserFirstNameQuery, {isLoading: updateUserFirstNameQueryisLoading, error: updateUserFirstNameQueryError}] = UserServices.updateUserFirstName();
  const [updateUserLastNameQuery, {isLoading: updateUserLastNameQueryisLoading, error: updateUserLastNameQueryError}] = UserServices.updateUserLastName();
  const [resetPasswordQuery, { isLoading: resetPasswordQueryIsLoading, reset: resetPasswordQueryReset}] = UserServices.resetPassword();


  const [searchQuery, setSearchQuery] = React.useState("");
  const [displayData, setDisplayData] = React.useState([]);
  const [newUserDrawer, setNewUserDrawer] = React.useState(false);

  const [newUserForm] = Form.useForm();

  

  const handleResetPasswordClick = (id) => {
    let answer = confirm("Reset user password? This will send a reset password email to the user's email address.")

    console.log(answer, id);

    if (resetPasswordQueryIsLoading) {
      return false;
    }

    if (answer) {
      resetPasswordQuery({
        user_id: id
      }, {
        onSuccess: (res) => {
          notification.success({
            message: 'Reset Password Success!',
            description:
              'An email has been sent to the user for the password details.',
          });
          resetPasswordQueryReset();
        },
        onError: (e) => {
          message.danger(e.error);
          resetPasswordQueryReset();
        },
      })
    }
  }

  const DropdownMenu = (record, key) => {
    
    return (
      <Menu>
        <Menu.Item 
          icon={resetPasswordQueryIsLoading ? <LoadingOutlined/>:<></>} 
          disabled={resetPasswordQueryIsLoading} 
          onClick={ () => handleResetPasswordClick(record.id) }
          key={key}
        >
          Reset password
        </Menu.Item>
      </Menu>
    )
  }

  const handleUserFirstNameChange = (id, first_name) => {
    // console.log(id, first_name);

    updateUserFirstNameQuery({
        id: id,
        first_name: first_name,
    }, {
        onSuccess: (res) => {
            // console.log(res);
            message.success("Update user first_name successful!");
            userRefetch();
        },
        onError: (e) => {
            console.log(e);
        }
    })
  }

  const handleUserLastNameChange = (id, last_name) => {
    // console.log(id, last_name);

    updateUserLastNameQuery({
        id: id,
        last_name: last_name,
    }, {
        onSuccess: (res) => {
            // console.log(res);
            message.success("Update user last_name successful!");
            userRefetch();
        },
        onError: (e) => {
            console.log(e);
        }
    })
  }

  const columns = [
    // {
    //   title: 'ID',
    //   dataIndex: 'id',
    //   key: 'id',
    // },
    {
      title: 'First name',
      dataIndex: 'first_name',
      key: 'first_name',
      responsive: ['lg'],
      render: (text, record) => <Typography.Paragraph editable={{ onChange: (e) =>  handleUserFirstNameChange(record.id, e) }} required>{record.first_name ? record.first_name : ''}</Typography.Paragraph>
    },
    {
      title: 'Last name',
      dataIndex: 'last_name',
      key: 'last_name',
      render: (text, record) => <Typography.Paragraph editable={{ onChange: (e) =>  handleUserLastNameChange(record.id, e) }} required>{record.last_name ? record.last_name : ''}</Typography.Paragraph>
    },
    {
      title: 'Email',
      dataIndex: 'email',
      key: 'email',
    },
    {
      title: 'User type',
      dataIndex: 'user_type',
      key: 'user_type',
      render: (text, record) => <Select disabled={_.includes(_.map(record.roles, 'name'), 'super-admin')} onChange={(e) => handleUserTypeChange(record.id, e)} defaultValue={record.user_type}>
                                    <Select.Option value="admin">Admin</Select.Option>
                                    <Select.Option value="agent">Agent</Select.Option>
                                    <Select.Option value="client">Client</Select.Option>
                                    <Select.Option value="customer">Customer</Select.Option>
                                </Select>
    },
    {
      title: 'Roles',
      dataIndex: 'roles',
      key: 'roles',
      // render: (text, record) => <>{_.map(record.roles, 'name').join(', ')}</>,
      render: (text, record) => <Select onChange={(e) => handleRoleChange(record.id, e)} disabled={_.includes(_.map(record.roles, 'name'), 'super-admin')} style={{width:'100%'}} defaultValue={_.map(record.roles, 'name')}>
                                  {
                                    roles && roles.map( (item, key) => {
                                      return <Select.Option key={key} value={item.name}>{item.name}</Select.Option>
                                    })
                                  }
                                </Select>
    },
    {
      title: 'Date Created',
      dataIndex: 'created_at',
      key: 'created_at',
      render: text => moment(text).format('MMM D, YYYY h:mm:ss a'),
    },
    ////
    {
      title: 'Action',
      render: (text, record, key) => <Dropdown overlay={DropdownMenu(record, key)} placement="bottomLeft"><Button icon={<EllipsisOutlined/>} /></Dropdown>,
    },
  ];

  React.useEffect( () => {
      const syncMessage = () => message.loading('Syncing..', 0);
    
      if (!isFetching) {
        message.destroy();
      } else {
        syncMessage();
      }

      if (searchQuery && !isFetching) handleSearch(searchQuery);

      return ( () => {
        message.destroy();
      });

  }, [isFetching]);
  
  const handleSearch = (search) => {
    if (search != "") {
      const _searchQuery = search != '' ? search.toLowerCase() : '';

      
      const filteredData = _.filter(data, (i) => {
        const searchValue =  i.first_name.toLowerCase() + ' ' + i.last_name.toLowerCase() + ' ' + i.email.toLowerCase(); 
        return searchValue.indexOf(_searchQuery) !== -1;
      });
      
      setDisplayData(filteredData);
      setSearchQuery(_searchQuery);
    } else {
      setDisplayData(data);
      setSearchQuery("");
    }
  }
  

  const onFinish = (values) => {
    // console.log(values);

    newUserQuery(values, {
        onSuccess: (data) => {

          console.log(data);

          queryCache.setQueryData(['users', { email: values.email }], data);
  
          notification.success({
              message: 'Created new user!',
              description:
                  `You have successfully created user ${values.first_name} ${values.last_name}`,
          });
  
          // Reset Forms
          newUserForm.resetFields();
  
          // Close Drawer
          setNewUserDrawer(false);

        },
    });
  }

  const handleUserTypeChange = (id, value) => {

    updateUserTypeQuery({ id: id, user_type: value }, {
      onSuccess: (res) => {

        queryCache.setQueryData(['users', { id: id }], res.data);

        notification.success({
            message: 'Updated user type!',
            description:
                ``,
        });

      },
      onError: (e) => console.log(e),
    });

  }

  const handleRoleChange = (id, value) => {
    console.log(id, value);

    updateUserRoleQuery({ id: id, role: value }, {
      onSuccess: (res) => {

        queryCache.setQueryData(['users', { id: id }], res.data);

        notification.success({
            message: 'Updated role!',
            description:
                ``,
        });

      },
      onError: (e) => console.log(e),
    });
  }

  return (
      <div>
        {/* Drawer */}
        <Drawer
          title="Create a new user"
          visible={newUserDrawer}
          onClose={() => setNewUserDrawer(false)}
          width={500}
        >
          <Form
            layout="vertical"
            onFinish={onFinish}
            form={newUserForm}
          >
            <Row gutter={12}>
              <Col span={12}>
                <Form.Item
                  name="email"
                  label="Email"
                  rules={[{ required: true }]}
                >
                  <Input />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="first_name"
                  label="First name"
                  rules={[{ required: true }]}
                >
                  <Input />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="middle_name"
                  label="Middle name"
                  // rules={[{ required: true }]}
                >
                  <Input />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="last_name"
                  label="Last name"
                  rules={[{ required: true }]}
                >
                  <Input />
                </Form.Item>
              </Col>
              <Col span={12}>
                <Form.Item
                  name="user_type"
                  label="User type"
                  rules={[{ required: true }]}
                >
                  <Select>
                    <Select.Option value="admin">Admin</Select.Option>
                    <Select.Option value="agent">Agent</Select.Option>
                    {/* <Select.Option value="sales_manager">Sales Manager</Select.Option> */}
                    {/* <Select.Option value="sales_director">Sales Director</Select.Option> */}
                    <Select.Option value="client">Client</Select.Option>
                    <Select.Option value="customer">Customer</Select.Option>
                  </Select>
                </Form.Item>
              </Col>
              <Col span={12}>
                  <Form.Item
                    name="role"
                    label="Role"
                    rules={[{ required: true }]}
                  >
                    <Select>
                    {
                          roles && roles.map( (item, key) => (
                              <Select.Option key={key} value={item.id}>{item.name}</Select.Option>
                          ))
                    }
                    </Select>
                  </Form.Item>
              </Col>
              <Col span={24}>
                <Button htmlType="submit" type="ghost" block className="mt-5" loading={isLoading}>Create</Button>
              </Col>
            </Row>
          </Form>

          <div>
            {
              error && 
              <Alert
                message={error && error.errors.email}
                type="warning"
                showIcon
                className="mt-2"
              />
              
            }
          </div>
        </Drawer>
        {/* Toolbar */}
        <div className="mb-2" style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
          <div style={{width: '240px'}}>
            <Input
              placeholder={`Search`}              
              onPressEnter={(e) => handleSearch(e.target.value)}
              onChange={(e) => handleSearch(e.target.value)}
            />
          </div>
          <div>
            <Button type="primary" onClick={()=>setNewUserDrawer(true)}>New User</Button>
          </div>
        </div>
        {/* <Card className="card-shadow"> */}
            <Table 
              loading={status === 'loading'}
              columns={columns}
              dataSource={searchQuery != "" ? displayData : data}
              rowKey="id"
              rowClassName="table-row"
            />
        {/* </Card> */}
      </div>
    );
}

export default List;