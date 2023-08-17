
import React from "react"
import { Router, Switch } from "react-router-dom"
import { createBrowserHistory } from "history"
import web from 'routes/web'
import PublicRoute from 'routes/Public'
import AuthenticatedRoute from 'routes/Authenticated'

const Routes = () => (
    <Router history={createBrowserHistory()}>
        <Switch>
            {
                web.map((route, i) => {
                    if (route.auth) {
                        return <AuthenticatedRoute key={i} {...route}/>
                    } else {
                        return <PublicRoute key={i} {...route}/>
                    }
                })
            }
        </Switch>
    </Router>
);

export default Routes;