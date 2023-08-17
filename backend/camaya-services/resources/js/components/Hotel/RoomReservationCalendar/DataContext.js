import React, { useState } from "react";

const DataContext = React.createContext({});

export default DataContext;

const { Provider } = DataContext;

export const DataProvider = ({ children }) => {

  const [data, setData] = useState([]);

  // function selectItem(group, subgroup, id) {
  //   setData(prevState => {
  //     const returnData = {
  //       ...prevState,
  //       [group]: {
  //         ...prevState[group],
  //         updatedAt: Date.now(),
  //         [subgroup]: {
  //           updatedAt: Date.now(),
  //           items: prevState[group][subgroup].items.map(item => {
  //             if (item.id === id) {
  //               return {
  //                 ...item,
  //                 selected: !item.selected
  //               };
  //             }
  //             return item;
  //           })
  //         }
  //       }
  //     };
  //     return returnData;
  //   });
  // }

  return <Provider value={[data, setData, 
                          // selectItem
                          ]}>{children}</Provider>;
};

// DataProvider.propTypes = {
//   children: PropTypes.node
// };
