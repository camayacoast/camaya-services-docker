import React from 'react'
// import { queryCache } from 'react-query'
import PaymentServices from 'services/PaymentService'
import { currencyFormat } from 'utils/Common'
import moment from 'moment'

import { Table, message, Tag, Card, Input} from 'antd'

const Payments = ({setPaymentId}) => {

    const { status, data, error, isFetching } = PaymentServices.list();

    const [searchQuery, setSearchQuery] = React.useState("");
    const [displayData, setDisplayData] = React.useState([]);

    const columns = [
      // {
      //   title: 'ID',
      //   dataIndex: 'id',
      //   key: 'id',
      // },
      {
        title: 'Transaction ID',
        dataIndex: 'transaction_id',
        key: 'transaction_id',
      },
      {
        title: 'First Name',
        dataIndex: 'first_name',
        key: 'first_name',
      },
      {
        title: 'Last Name',
        dataIndex: 'last_name',
        key: 'last_name',
      },
      {
        title: 'Items',
        key: 'items',
        dataIndex: 'items',
        render: items => (
          <>
            {items.map(item => {
              return (
                <Tag color="blue" key={item.item_transaction_id}>
                  {item.item.toUpperCase()}
                </Tag>
              );
            })}
          </>
        ),
      },
      {
        title: 'Total',
        dataIndex: 'total',
        key: 'total',
        render: (text) => currencyFormat(text)
      },
      {
        title: 'Payment Date',
        dataIndex: 'paid_at',
        key: 'paid_at',
        render: text => moment(text).format('MMM D, YYYY h:mm:ss a')
      },
      {
        title: 'Date Created',
        dataIndex: 'created_at',
        key: 'created_at',
        render: text => moment(text).format('MMM D, YYYY h:mm:ss a')
      },
      // {
      //   title: 'Item',
      //   dataIndex: 'item',
      //   key: 'item',
      //   render: (text, record) => <a href="#" onClick={() => setPaymentId(record.id)}>{text}</a>,
      // },
    ];

    React.useEffect( () => {
        const syncMessage = () => message.loading('Syncing..', 0);
      
        if (!isFetching) {
          message.destroy();
        } else {
          syncMessage();
        }

        if (searchQuery && !isFetching) handleSearch(searchQuery);

    }, [isFetching]);
    
    const handleSearch = (search) => {
      if (search != "") {
        const _searchQuery = search != '' ? search.toLowerCase() : '';

        
        const filteredData = _.filter(data, (i) => {
          const searchValue = i.transaction_id.toLowerCase() + ' ' + i.first_name.toLowerCase() + ' ' + i.last_name.toLowerCase() + ' ' + _.split(i.item_transaction_ids, ",").join(" ").toLowerCase(); 
          return searchValue.indexOf(_searchQuery) !== -1;
        });
        
        setDisplayData(filteredData);
        setSearchQuery(_searchQuery);
      } else {
        setDisplayData(data);
        setSearchQuery("");
      }
    }

    return (
        <div>
          <h1>Payments</h1>
          <div>
            <Input
              placeholder={`Search`}              
              onPressEnter={() => handleSearch(searchQuery)}
              onChange={(e) => handleSearch(e.target.value)}
            />
          </div>
          {/* <Card className="card-shadow"> */}
              <Table 
                loading={status === 'loading'}
                columns={columns}
                dataSource={searchQuery != "" ? displayData : data}
                rowKey="transaction_id"
                rowClassName="table-row"
                expandable={{
                  expandedRowRender: record =>
                    <Table
                      size="small"
                      pagination={false}
                      rowKey="item_transaction_id"
                      columns={[{
                        title: 'Item Transaction ID',
                        dataIndex: 'item_transaction_id',
                        key: 'item_transaction_id',
                      },
                      {
                        title: 'Item',
                        dataIndex: 'item',
                        key: 'item',
                      },
                      {
                        title: 'Payment Channel',
                        dataIndex: 'payment_channel',
                        key: 'payment_channel',
                      },
                      {
                        title: 'Amount',
                        dataIndex: 'amount',
                        key: 'amount',
                        render: (text) => currencyFormat(text)
                      }]}
                      dataSource={record.items}
                    />,
                }}
              />
          {/* </Card> */}
        </div>
      );

}

const Payment = ({ paymentId, setPaymentId }) => {
    const { status, data, error, isFetching } = PaymentServices.item(paymentId);

    return (
      <div>
        <div>
          <a onClick={() => setPaymentId(null)} href="#">
            Back
          </a>
        </div>
        {!paymentId || status === "loading" ? (
          "Loading..."
        ) : status === "error" ? (
          <span>Error: {error.message}</span>
        ) : (
          <>
            <h1>{data.item_transaction_id}</h1>
            <div>
              <p>{data.item}</p>
            </div>
            <div>{isFetching ? "Background Updating..." : " "}</div>
          </>
        )}
      </div>
    );
}

function Page(props) {

  const [paymentId, setPaymentId] = React.useState(null);
    
    return (
        <div className="fadeIn">
            {paymentId ? (
              <Payment paymentId={paymentId} setPaymentId={setPaymentId} />
            ) : (
              <Payments setPaymentId={setPaymentId}/>
            )}
        </div>
    )
    
}

export default Page;