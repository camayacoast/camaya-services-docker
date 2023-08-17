<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class ViberSubscriber extends Model
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'viber_token',
        'viber_id',
        'name',
        'avatar',
        'country',
        'status',
    ];


    public static function broadcastMessage($message, $type)
    {

        $boardcast_list = self::where('status', 'subscribed')
                                ->select('viber_id')
                                ->get()
                                ->pluck('viber_id');
        
        $data = [
                    "min_api_version" => 6,
                    'auth_token' => env('ONLINE_PAYMENT_VIBER_BOT_TOKEN'),
                    'broadcast_list' => $boardcast_list,
                    'type' => $type,
                    'sender' => [
                        "name" => "Maya",
                        "avatar" => env('APP_URL')."/images/camaya-logo.jpg"
                    ],
        ];

        $data['text'] = $message['text'];
        
        $keyboard = [
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
                     ]
                  ]
               ];
        
        if (isset($keyboard)) {
            $data['keyboard'] = $keyboard;
        }
        
        $response = Http::post('https://chatapi.viber.com/pa/broadcast_message', $data);
        
        return 'OK';
    }
}
