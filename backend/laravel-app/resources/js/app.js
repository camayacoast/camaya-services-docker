import React from 'react'

import AuthService from 'services/AuthService'

import { Offline, Online } from 'react-detect-offline'

function App(props) {

  const storageSubscription = (e) => {

    const token = localStorage.getItem('token');

    if (e.key === 'token') {

        props.dispatch(AuthService.checkAuth(token))
        .then( (res) => {
          // console.log(res)
        })
        .catch( (res) => {
          AuthService.logout();
        });

    }
  }

  React.useEffect( () => {

    document.title = `${process.env.APP_NAME} ${props.children.props.title ? `- ${props.children.props.title}`:''}`;
    
    window.addEventListener('storage', storageSubscription);

    return (()=>{
      window.removeEventListener('storage', storageSubscription); 
    });
  },[]);

  return (
    <main>
      {/* <Offline>
        <div style={{position: 'fixed', width: '100%', zIndex: '1000', color: 'white', background: 'orange', padding: '8px'}}>
          <div style={{textAlign: 'center'}}>
            You're offline right now. Check your connection.
          </div>
        </div>
      </Offline> */}
      {props.children}
    </main>
  );

}

export default App;