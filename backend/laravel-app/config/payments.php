<?php

return [
  
  'paypal' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'secret' => env('PAYPAL_CLIENT_SECRET'),

                /**
                *	SDK CONFIGURATIONS
                **/
                'settings' => [

                  //sandbox or live
                  'mode' => env('PAYPAL_MODE','sandbox'),

                  //300 milliseconds
                  'http.ConnectionTimeOut' => 300,

                  
                  'log.LogEnabled' => true,
                  'log.FileName' => storage_path() . '/logs/paypal.log',
                  'log.LogLevel' => 'FINE'
                ]
  ]

];
