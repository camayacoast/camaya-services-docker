<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\PaymentTransaction;
use App\ViberSubscriber;
use Illuminate\Support\Facades\Storage;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as BaseExcel;
use App\Exports\PaymentExport;

class ViberBotController extends Controller
{
    //

    public function activateBot(Request $request)
    {
        // return $request->all();
        if ($request->key != env('ONLINE_PAYMENT_VIBER_BOT_KEY')) {
            return '';
        }
        

        $response = Http::post('https://chatapi.viber.com/pa/set_webhook', [
            'auth_token' => env('ONLINE_PAYMENT_VIBER_BOT_TOKEN'),
            'url' => env('APP_URL')."/api/viberbot/webhook",
        ]);

        return $response;
    }

    public function deactivateBot(Request $request)
    {
        // return $request->all();
        if ($request->key != env('ONLINE_PAYMENT_VIBER_BOT_KEY')) {
            return '';
        }

        $response = Http::post('https://chatapi.viber.com/pa/set_webhook', [
            'auth_token' => env('ONLINE_PAYMENT_VIBER_BOT_TOKEN'),
            'url' => "",
        ]);

        return $response->json();
    }

    public function webhook(Request $request)
    {
        if ($request->event == 'webhook') {
            $webhook_response = [
                'status' => 0,
                'status_message' => 'ok',
                'event_types' => 'delivered'
            ];
            echo json_encode($webhook_response);
            die;
        }

        switch ($request->event) {
            case 'subscribed':
                // Subscribed
            break;
            case 'unsubscribed':
                // Verify the user
               ViberSubscriber::where('viber_id', $request['user_id'])->update([
                    'status' => 'unsubscribed',
                ]);
                die;
            case 'conversation_started':
                // Converstation started
                // error_log($request->sender['id'], 0);
                
                if ($request->subscribed == false) {
                
                    // Create user
                    
                    ViberSubscriber::updateOrInsert(
                        ['viber_id' => $request->user['id']],
                        [
                            'viber_token' => env('ONLINE_PAYMENT_VIBER_BOT_TOKEN'),
                            'name' => $request->user['name'] ?? null,
                            // 'avatar' => $request->sender['avatar'] ?? null,
                            // 'country' => $request->sender['country'] ?? null,
                            'status' => 'unverified',
                        ]);
                    
                    return [
                        'auth_token' => env('ONLINE_PAYMENT_VIBER_BOT_TOKEN'),
                        "min_api_version" => 6,
                        'tracking_data' => 'tracking data',
                        'text' => "Hi, ".$request->user['name']."\n\nWelcome to Camaya Online Payment Viber. Click 'Get Started!' to subscribe.",
                        'type' => 'text',
                        'sender' => [
                            "name" => "Maya",
                            "avatar" => env('APP_URL')."/images/camaya-logo.jpg"
                        ],
                        "keyboard" => [
                          "Type"=>"keyboard",
                          "DefaultHeight" => false,
                          "CustomDefaultHeight" => 40,
                          "InputFieldState" => 'hidden',
                          "Buttons"=>[
                             [
                                "Silent" => true,
                                "BgColor" => "#FFD700",
                                "ActionType"=>"reply",
                                "ActionBody"=>"c4ca4238a0b923820dcc509a6f75849b",
                                "Text"=>'<font size="32"><b>Get Started!</b></font>',
                                "TextSize"=>"small",
                                "Frame" => [
                                    "BorderWidth" => 2,
                                    "BorderColor" => "#DAA520",
                                    "CornerRadius" => 10
                                ]
                             ]
                          ]
                       ]
                    ];
                }
            break;
            case 'message':
                // Message
                $user_message = $request->message['text'];
                $type = 'text';
                
                $message['keyboard'] = [
                  "Type"=>"keyboard",
                  "DefaultHeight" => false,
                  "CustomDefaultHeight" => 40,
                  "InputFieldState" => 'hidden',
                  "Buttons"=>[
                     [
                         "Columns"=> 3,
			                "Rows"=> 1,
                        "Silent" => true,
                        "BgColor" => "#FFD700",
                        "ActionType"=>"reply",
                        "ActionBody"=>"GET_TOTAL_ONLINE_PAYMENTS",
                        "Text"=>'<font size="32"><b>Total Online Payments</b></font>',
                        "TextSize"=>"small",
                        "Frame" => [
                            "BorderWidth" => 2,
                            "BorderColor" => "#DAA520",
                            "CornerRadius" => 10
                        ]
                     ],
                     [
                        "Columns"=> 3,
			            "Rows"=> 1,
                        "Silent" => true,
                        "BgColor" => "#FFD700",
                        "ActionType"=>"reply",
                        "ActionBody"=>"GET_ONLINE_PAYMENTS_THIS_MONTH",
                        "Text"=>'<font size="32"><b>Online Payments This Month</b></font>',
                        "TextSize"=>"small",
                        "Frame" => [
                            "BorderWidth" => 2,
                            "BorderColor" => "#DAA520",
                            "CornerRadius" => 10
                        ]
                    ],
                    [
                        "Columns"=> 3,
			            "Rows"=> 1,
                        "Silent" => true,
                        "BgColor" => "#FFD700",
                        "ActionType"=>"reply",
                        "ActionBody"=>"GET_DAILY_REPORT",
                        "Text"=>'<font size="32"><b>Report today</b></font>',
                        "TextSize"=>"small",
                        "Frame" => [
                            "BorderWidth" => 2,
                            "BorderColor" => "#DAA520",
                            "CornerRadius" => 10
                        ]
                     ]
                  ]
               ];
               
               $subscriber = ViberSubscriber::where('viber_id', $request->sender['id'])->first();
                
                if ($user_message == "c4ca4238a0b923820dcc509a6f75849b" && ($subscriber->status == 'unverified' || $subscriber->status == 'unsubscribed')) {
                    $message['text'] = "Reply the PASSCODE to use my services.";
                    
                    $message['keyboard'] = null;
                } else if ($user_message == "!6292" && ($subscriber->status == 'unverified' || $subscriber->status == 'unsubscribed')) {
                    
                   // Verify the user
                   ViberSubscriber::where('viber_id', $request->sender['id'])
                   ->update([
                        'status' => 'subscribed',
                    ]);
                   
                   $message['text'] = "Thank you! You're now subscribed to channel. Please choose on the menu below.";
                   
                } else if ($subscriber->status == 'subscribed') {
                    
                    switch ($user_message) {
                        case 'GET_TOTAL_ONLINE_PAYMENTS':
                            $total_online_payments = PaymentTransaction::where('status', 'paid')->sum('amount');
                            $message['text'] = 'Total Payments: P'. number_format($total_online_payments,0);
                        break;
                        
                        case 'GET_ONLINE_PAYMENTS_THIS_MONTH':
                            $total_online_payments = PaymentTransaction::where('status', 'paid')->whereMonth('paid_at', date('m'))->sum('amount');
                            $message['text'] = 'Total Monthly Payments: P'. number_format($total_online_payments,0);
                        break;

                        case 'GET_DAILY_REPORT':
                            
                            $file_name = date('Y-m-d H:i:s').' daily-report.xlsx';
                            
                            $file = Excel::store(new PaymentExport, "/reports/".$file_name, 'public');
                            
                            $type = 'file';
                            $message['media'] = env('APP_URL').Storage::url("/reports/".$file_name);
                            // $message['media'] = env('APP_URL')."/images/camaya-logo.jpg";
                            $message['size'] = 10000;
                            $message['file_name'] = $file_name;
                            $message['text'] = "Test";
                            
                        break;
                        
                        default:
                            $message['text'] = "";
                    }
                    
                } else if ($subscriber->status == 'kicked') {
                    $message['text'] = "You are kicked.";
                    $message['keyboard'] = null;
                } else if ($subscriber->status == 'banned') {
                    $message['text'] = "You are banned.";
                    $message['keyboard'] = null;
                } else {
                     $message['text'] = "Reply the PASSCODE to use my services.";
                    $message['keyboard'] = null;
                }
                
                self::sendMessage($request->sender, $message, $type);
            break;
        }
    }
    
    public static function sendMessage($sender, $message, $type)
    {
        
        $data = [
                    "min_api_version" => 6,
                    'tracking_data' => 'tracking data',
                    'auth_token' => env('ONLINE_PAYMENT_VIBER_BOT_TOKEN'),
                    'receiver' => $sender['id'],
                    'text' => $message['text'],
                    'type' => $type,
                    'sender' => [
                        "name" => "Maya",
                        "avatar" => env('APP_URL')."/images/camaya-logo.jpg"
                    ],
        ];

        if ($type == 'file') {
            $data['media'] = $message['media'];
            $data['size'] = $message['size'];
            $data['file_name'] = $message['file_name'];
            $data['text'] = "";
        }

        if (isset($message['keyboard'])) {
            $data['keyboard'] = $message['keyboard'];
        }
        
        $response = Http::post('https://chatapi.viber.com/pa/send_message', $data);
    
        die;
    }
}
