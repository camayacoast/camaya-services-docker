<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\PaymentTransaction;
use App\User;
use App\ViberSubscriber;
use App\ClientProfile;
use Illuminate\Support\Facades\Http;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;

use Config;
use Validator;
use Carbon\Carbon;

use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentTransaction as PaymentTransactionMail;
use App\Mail\GolfPaymentTransaction as GolfPaymentTransactionMail;
use App\Mail\BankPayment as BankPaymentMail;

use DB;

class PaymentController extends Controller
{
    //

    protected $items = [
        'hoa_membership_fee' => 5000,
        'hoa_monthly_dues' => 10,
        'hoa_monthly_dues_promo' => 7*12,
        'hoa_monthly_dues_6months_promo' => 7*6,
        'fmf_privilege_activation_fee' => 20000,
        'fmf_monthly_dues' => 750,
        'fmf_monthly_dues_promo' => 500*12,
        'golf_membership_fee' => 100000,
        'golf_monthly_dues' => 1999,
        'golf_membership_fee_promo' => 30000,
        'golf_monthly_dues_promo' => 999*12,

        'non_hoa_3yr_golf_membership_fee' => 150000,
        'non_hoa_3yr_golf_membership_fee_promo' => 99000,
        'non_hoa_golf_monthly_dues' => 1999,
        'non_hoa_3yr_golf_dues_promo' => 999*36,
        'non_hoa_golf_annual_dues_promo' => 1499*12,

        'non_hoa_golf_membership_fee_2021' => 99000,
        'non_hoa_advance_golf_dues_12_months' => 999*12,
    ];

    public function __construct()
    {
        $paypal_config = Config::get('payments.paypal');
        $this->_api_context = new ApiContext(new OAuthTokenCredential($paypal_config['client_id'],$paypal_config['secret']));
        $this->_api_context->setConfig($paypal_config['settings']);
    }

    public function list()
    {

        $transactions = PaymentTransaction::join('users', 'payment_transactions.user_id', '=', 'users.id')
            // ->whereNotNull('paid_at')
            ->with('items')
            ->select(
                'payment_transactions.transaction_id',
                'payment_transactions.user_id',
                'users.first_name',
                'users.last_name',
                'users.email',
                DB::raw('SUM(payment_transactions.amount) as total'),
                'payment_transactions.paid_at',
                'payment_transactions.created_at',
                DB::raw('GROUP_CONCAT(item_transaction_id) as item_transaction_ids')
            )
            ->groupBy('payment_transactions.transaction_id', 'payment_transactions.user_id', 'payment_transactions.paid_at', 'payment_transactions.created_at')
            ->get();


        return $transactions;
    }

    public function item(Request $request)
    {
        return PaymentTransaction::where('id', $request->id)->first();
    }

    public function initiatePayment(Request $request)
    {

        // return $request->all();
        return "Payment portal closed.";

        $url = $request->provider == 'golf-paypal' ? env('CAMAYA_GOLF_PORTAL') : env('CAMAYA_PAYMENT_PORTAL');

        // get the area from users property details
        $area = count($request->user()->clientProperties) ? $request->user()->clientProperties[0]['area'] : '';

        // initialize amount
        $total_amount = 0;

        // Sum it up
        foreach ($request->items as $item) {
            if ($item == 'hoa_monthly_dues' || $item == 'hoa_monthly_dues_promo' || $item == 'hoa_monthly_dues_6months_promo') {
                $total_amount = $total_amount + $this->items[$item] * $area;
            } else if ($item == 'non_hoa_3yr_golf_membership_fee' || $item == 'non_hoa_3yr_golf_membership_fee_promo') {
                $total_amount = $total_amount + ($this->items[$item] * ($request->payment_option == 'FULL' ? 0.9 : 0.3));
            } else {
                $total_amount = $total_amount + $this->items[$item];
            }
        }

        // Check if the total_amount ordered is equal to the item database
        if ($total_amount != $request->amount) {
            return 'An error occured please make another transaction.';
        }

        if ($total_amount < 750 || $total_amount > 500000) {
            return 'Amount out of range.';
        }

        switch ($request->provider) {
            case 'paypal':
                // return $request->all();

                if (env('CAMAYA_PAYMENT_PORTAL_ENABLE') == false) {
                    return "Payment portal closed.";
                }
        
                $validator = Validator::make($request->all(), [
                        // '' => 'required',
                        'amount' => 'required|integer|between:750,500000',
                ]);
        
                if ($validator->fails()) {
        
                    echo "<div style=\"display: flex; justify-content: center; align-items: center; height: 100vh;\">";
                        echo "<div style=\"font-family: 'Arial', sans-serif; padding: 50px; border: solid 1px gainsboro;\">";
                        echo "<p>Your payment could not continue.</p>";
        
                        foreach($validator->errors()->all() as $error) {
                        echo $error;
                        }
        
                        echo "<p style='margin-top: 50px'><a href='".$url."'>Go back to payment portal</a></p>";
                        echo "</div>";
                    echo "</div>";
        
                    exit;
                }

                $payer = new Payer();
                $payer->setPaymentMethod("paypal");
        
                $createTransactionId = substr(strtoupper(md5(rand() * time())), 0, 10);
        
                $filtered_items = collect($this->items)->only($request->items);

                $paypal_items = [];
                $item_transactions = [];
                
                foreach ($filtered_items as $key => $amount) {

                    $createItemTransactionId = substr(strtoupper(md5(rand() * time())), 0, 20);
                    $price = (($key == 'hoa_monthly_dues' || $key == 'hoa_monthly_dues_promo' || $key == 'hoa_monthly_dues_6months_promo') ? $this->items[$key] * $area : $this->items[$key]);
                    $newItem = new Item();
                    $newItem->setName('Payment: '. $key .' - '. $createTransactionId .' - '. $createItemTransactionId .' - '. $request->user()->first_name .' '. $request->user()->last_name)
                    ->setDescription('Item: '. $key)
                    ->setCurrency('PHP')
                    ->setQuantity(1)
                    ->setSku($key." (".$price.")") // Similar to `item_number` in Classic API
                    ->setPrice($price);

                    $item_transactions[] = [
                        'transaction_id' => $createTransactionId,
                        'item_transaction_id' => $createItemTransactionId,
                        'item' => $key,
                        'status' => 'created',
                        'payment_channel' => 'paypal',
                        'remarks' => '',
                        'amount' => $price
                    ];

                    $paypal_items[] = $newItem;
                }
        
                $itemList = new ItemList();
                $itemList->setItems($paypal_items);
        
                $details = new Details();
                $details->setShipping(0)
                    ->setTax(0)
                    ->setSubtotal($total_amount);
        
                $amount = new Amount();
                $amount->setCurrency("PHP")
                    ->setTotal($total_amount)
                    ->setDetails($details);
        
                $transaction = new Transaction();
                $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription("Online Payment")
                    ->setCustom($request->email)
                    ->setNoteToPayee($createTransactionId)
                    ->setInvoiceNumber(uniqid());
        
                // $baseUrl = getBaseUrl();
                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl(route('payment.status', ['provider' => $request->provider, 'transaction_id' => $createTransactionId]))
                    ->setCancelUrl(route('payment.cancel', ['provider' => $request->provider, 'transaction_id' => $createTransactionId, 'cancel_transaction' => 1]));
        
                $payment = new Payment();
                $payment->setIntent("sale")
                    ->setPayer($payer)
                    ->setRedirectUrls($redirectUrls)
                    ->setTransactions(array($transaction));
        
                $req = clone $payment;
                
                try {

                    $user = User::find($request->user()->id);

                    $payment_history = [];

                    foreach ($item_transactions as $i) {
                        $payment_history[] = new PaymentTransaction([
                            'transaction_id' => $i['transaction_id'],
                            'item_transaction_id' => $i['item_transaction_id'],
                            'item' => $i['item'],
                            'status' => $i['status'],
                            'payment_channel' => $i['payment_channel'],
                            'remarks' => $i['remarks'],
                            'amount' => $i['amount']
                        ]);
                    }

                    $user->paymentHistory()->saveMany($payment_history);
        
                    $payment->create($this->_api_context);
                } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                    // echo ("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
                    echo 'Exception: ' .  $ex->getMessage() . PHP_EOL;
                    echo "<br><br>";
                    echo "Error: ". $ex->getData();
                    echo "<br><br>";
                    // echo $request;
                    exit(1);
                }
        
                $approvalUrl = $payment->getApprovalLink();
                // ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
                $redirect_url = $approvalUrl ? $approvalUrl : '';
                
                return $redirect_url;
        
                // return $payment;
            case 'golf-paypal':
                    // return $request->provider;
    
                    if (env('CAMAYA_PAYMENT_PORTAL_ENABLE') == false) {
                        return "Payment portal closed.";
                    }
            
                    $validator = Validator::make($request->all(), [
                            // '' => 'required',
                            'amount' => 'required|integer|between:750,500000',
                    ]);
            
                    if ($validator->fails()) {
            
                        echo "<div style=\"display: flex; justify-content: center; align-items: center; height: 100vh;\">";
                            echo "<div style=\"font-family: 'Arial', sans-serif; padding: 50px; border: solid 1px gainsboro;\">";
                            echo "<p>Your payment could not continue.</p>";
            
                            foreach($validator->errors()->all() as $error) {
                            echo $error;
                            }
            
                            echo "<p style='margin-top: 50px'><a href='".env('CAMAYA_PAYMENT_PORTAL')."'>Go back to payment portal</a></p>";
                            echo "</div>";
                        echo "</div>";
            
                        exit;
                    }
    
                    $payer = new Payer();
                    $payer->setPaymentMethod("paypal");
            
                    $createTransactionId = substr(strtoupper(md5(rand() * time())), 0, 10);
            
                    $filtered_items = collect($this->items)->only($request->items);
    
                    $paypal_items = [];
                    $item_transactions = [];
                    
                    foreach ($filtered_items as $key => $amount) {
    
                        $createItemTransactionId = substr(strtoupper(md5(rand() * time())), 0, 20);
                        $price = (($key == 'non_hoa_3yr_golf_membership_fee' || $key == 'non_hoa_3yr_golf_membership_fee_promo') ? $this->items[$key] * ($request->payment_option == 'FULL' ? 0.9:0.3) : $this->items[$key]);
                        $newItem = new Item();
                        $newItem->setName('Payment: '. $key .' - '. $createTransactionId .' - '. $createItemTransactionId .' - '. $request->user()->first_name .' '. $request->user()->last_name)
                        ->setDescription('Item: '. $key)
                        ->setCurrency('PHP')
                        ->setQuantity(1)
                        ->setSku($key." (".$price.")") // Similar to `item_number` in Classic API
                        ->setPrice($price);
    
                        $item_transactions[] = [
                            'transaction_id' => $createTransactionId,
                            'item_transaction_id' => $createItemTransactionId,
                            'item' => $key,
                            'status' => 'created',
                            'payment_channel' => 'paypal',
                            'remarks' => '',
                            'amount' => $price
                        ];
    
                        $paypal_items[] = $newItem;
                    }
            
                    $itemList = new ItemList();
                    $itemList->setItems($paypal_items);
            
                    $details = new Details();
                    $details->setShipping(0)
                        ->setTax(0)
                        ->setSubtotal($total_amount);
            
                    $amount = new Amount();
                    $amount->setCurrency("PHP")
                        ->setTotal($total_amount)
                        ->setDetails($details);
            
                    $transaction = new Transaction();
                    $transaction->setAmount($amount)
                        ->setItemList($itemList)
                        ->setDescription("Golf Payment")
                        ->setCustom($request->email)
                        ->setNoteToPayee($createTransactionId)
                        ->setInvoiceNumber(uniqid());
            
                    // $baseUrl = getBaseUrl();
                    $redirectUrls = new RedirectUrls();
                    $redirectUrls->setReturnUrl(route('payment.status', ['provider' => 'golf-paypal', 'transaction_id' => $createTransactionId]))
                        ->setCancelUrl(route('payment.cancel', ['provider' => 'golf-paypal', 'transaction_id' => $createTransactionId, 'cancel_transaction' => 1]));
            
                    $payment2 = new Payment();
                    $payment2->setIntent("sale")
                        ->setPayer($payer)
                        ->setRedirectUrls($redirectUrls)
                        ->setTransactions(array($transaction));
            
                    $req = clone $payment2;
                    
                    try {
    
                        $user = User::find($request->user()->id);
    
                        $payment_history = [];
    
                        foreach ($item_transactions as $i) {
                            $payment_history[] = new PaymentTransaction([
                                'transaction_id' => $i['transaction_id'],
                                'item_transaction_id' => $i['item_transaction_id'],
                                'item' => $i['item'],
                                'payment_type' => $request->payment_option,
                                'status' => $i['status'],
                                'payment_channel' => $i['payment_channel'],
                                'remarks' => $i['remarks'],
                                'amount' => $i['amount']
                            ]);
                        }
    
                        $user->paymentHistory()->saveMany($payment_history);
                        
                        $payment2->create($this->_api_context);
                    } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                        // echo ("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", null, $request, $ex);
                        echo 'Exception: ' .  $ex->getMessage() . PHP_EOL;
                        echo "<br><br>";
                        echo "Error: ". $ex->getData();
                        echo "<br><br>";
                        // echo $request;
                        exit(1);
                    }
            
                    $approvalUrl = $payment2->getApprovalLink();
                    // ResultPrinter::printResult("Created Payment Using PayPal. Please visit the URL to Approve.", "Payment", "<a href='$approvalUrl' >$approvalUrl</a>", $request, $payment);
                    $redirect_url = $approvalUrl ? $approvalUrl : '';
                    
                    return $redirect_url;
            
                    // return $payment;
            break;
        }
    }

    public function getPayPalPaymentStatus(Request $request)
    {
        // return $request->all();
        $url = $request->provider == 'golf-paypal' ? env('CAMAYA_GOLF_PORTAL') : env('CAMAYA_PAYMENT_PORTAL');
      if (env('CAMAYA_PAYMENT_PORTAL_ENABLE') == false) {
        return "Payment portal closed.";
      }

      $payment = Payment::get($request->paymentId, $this->_api_context);

      // CHECK IF PAYMENT HAS BEEN APPROVED OR PAID ALREADY; REDIRECT WHEN PAID
      if ($payment->getState() == 'approved') {
        // return redirect('/');
        echo "Approved already.";
        exit;
      } 
    
      // PREPARE FOR PAYMENT EXECUTION
      $executePayment = new PaymentExecution;
      $executePayment->setPayerId($request->PayerID);
      
      // EXECUTE PAYMENT
      try {
        $result = $payment->execute($executePayment, $this->_api_context);
      } catch(\Paypal\Exception\PPConnectionException $ex) {
          die('Exception:' .  $ex->getMessage() . PHP_EOL);
      }
      
      // IF PAYMENT HAS BEEN MADE, CHECK IF APPROVED
      if ($result->getState() == 'approved') {
        // Make the updates

        $transactions = PaymentTransaction::setTransactionStatusToPaid($request->transaction_id, $request->paymentId);

        $user = User::where('id', $transactions[0]->user_id)->first();
        $payment_transactions = PaymentTransaction::where('transaction_id', $request->transaction_id)
                                ->select('transaction_id', 'item', 'payment_channel','amount')
                                ->get();
        
        $total_amount = collect($payment_transactions)->sum('amount');
        $items = collect($payment_transactions)->pluck('item');

        // if ($request->provider == 'golf-paypal') {
            $newGolfMember = 0;

            if ($user->clientProfile->golf_membership == NULL) {
                $newGolfMember = 1;
            }

            // golf_membership_fee
            // golf_membership_fee_promo

            if ($items->contains('non_hoa_3yr_golf_membership_fee')
                || $items->contains('non_hoa_3yr_golf_membership_fee_promo')
                || $items->contains('golf_membership_fee')
                || $items->contains('golf_membership_fee_promo')) {
                ClientProfile::where('user_id', $user->id)->update([
                    'golf_membership' => Carbon::now()->addYears(3),
                ]);

                $newGolfMember = $newGolfMember + 1;
            }else if ($items->contains('non_hoa_golf_membership_fee_2021')) {
                ClientProfile::where('user_id', $user->id)->update([
                    'golf_membership' => Carbon::now()->addYears(1),
                ]);

                $newGolfMember = $newGolfMember + 1;
            }
        // }

        // Redirect to payments page for confirmation
        echo "Confirming payment. DO NOT CLOSE.";

        // Notify via email
        if ($request->provider == 'golf-paypal') {
            Mail::to($user->email)->send(new GolfPaymentTransactionMail($user, $transactions));
        } else {
            Mail::to($user->email)->send(new PaymentTransactionMail($user, $transactions));
        }

        $message['text'] = $user->first_name." ".$user->last_name." has paid P".number_format($total_amount,0)." for [". implode(' ,', $items->all())."]";

        if (env('VIBERBOT_NOTIFICATION') == true) {
            ViberSubscriber::broadcastMessage($message,'text');
        }

        return redirect($url.'/?status=confirmed&t='.$request->transaction_id.($request->provider == 'golf-paypal' || $request->provider == 'paypal' && $newGolfMember == 2 ? '&new_golf_member=1':''));
      }

    }

    public function cancelPayment(Request $request)
    {
        $url = $request->provider == 'golf-paypal' ? env('CAMAYA_GOLF_PORTAL') : env('CAMAYA_PAYMENT_PORTAL');

        if (empty($request->token) || empty($request->transaction_id) || empty($request->cancel_transaction)) {
            return redirect($url);
        }

        $transaction = PaymentTransaction::setTransactionStatusToCancelled($request->transaction_id);
        return redirect($url.'/?status=cancelled&t='.$request->transaction_id);
    
    }

    public function bankPayment(Request $request)
    {
        // return $request->all();

        if (env('CAMAYA_PAYMENT_PORTAL_ENABLE') == false) {
            return "Payment portal closed.";
        }

        $url = $request->provider == 'golf-paypal' ? env('CAMAYA_GOLF_PORTAL') : env('CAMAYA_PAYMENT_PORTAL');

        // get the area from users property details
        $area = count($request->user()->clientProperties) ? $request->user()->clientProperties[0]['area'] : '';

        // initialize amount
        $total_amount = 0;

        // Sum it up
        foreach ($request->items as $item) {
            if ($item == 'non_hoa_3yr_golf_membership_fee' || $item == 'non_hoa_3yr_golf_membership_fee_promo') {
                $total_amount = $total_amount + ($this->items[$item] * ($request->payment_option == 'FULL' ? 0.9 : 0.3));
            } else {
                $total_amount = $total_amount + $this->items[$item];
            }
        }

        // Check if the total_amount ordered is equal to the item database
        if ($total_amount != $request->amount) {
            return 'An error occured please make another transaction.';
        }

        if ($total_amount < 750 || $total_amount > 500000) {
            return 'Amount out of range.';
        }

        $createTransactionId = substr(strtoupper(md5(rand() * time())), 0, 10);
        
        $filtered_items = collect($this->items)->only($request->items);

        $item_transactions = [];

        foreach ($filtered_items as $key => $amount) {

            $createItemTransactionId = substr(strtoupper(md5(rand() * time())), 0, 20);
            $price = (($key == 'non_hoa_3yr_golf_membership_fee' || $key == 'non_hoa_3yr_golf_membership_fee_promo') ? $this->items[$key] * ($request->payment_option == 'FULL' ? 0.9:0.3) : $this->items[$key]);

            $item_transactions[] = new PaymentTransaction([
                'transaction_id' => $createTransactionId,
                'item_transaction_id' => $createItemTransactionId,
                'item' => $key,
                'payment_type' => $request->payment_option,
                'status' => 'created',
                'payment_channel' => 'bank_deposit',
                'remarks' => '',
                'expires_at' => Carbon::now()->addHours(24),
                'amount' => $price
            ]);

        }

        $user = User::find($request->user()->id);

        $user->paymentHistory()->saveMany($item_transactions);

        if (!$user) {
            return response()->json(['message'=>'Bank payment mode failed.'], 400);
        }

        $transactions = PaymentTransaction::where('transaction_id', $createTransactionId)->get();

        Mail::to($user->email)->send(new BankPaymentMail($user, $transactions));

        return response()->json(['message'=>'Bank payment mode created.', 'status' => 'OK', 'transaction_id' => $createTransactionId], 200);
    }


    public function golfPayments(Request $request)
    {
        // return $request->all();
        if (!$request->user()->user_type == 'admin') {
            return 'Access Denied';
        }

        return PaymentTransaction::with('payer')->orderBy('created_at', 'desc')->get();
    }

    public function golfPaymentsMarkAsPaid(Request $request)
    {
        // return $request->all();

        if (!$request->user()->user_type == 'admin') {
            return 'Access Denied';
        }

        PaymentTransaction::where('id', $request->id)
        ->update([
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s', time()),
        ]);

        $transaction = PaymentTransaction::where('id', $request->id)->first();

        $memberships = [
            'non_hoa_3yr_golf_membership_fee',
            'non_hoa_3yr_golf_membership_fee_promo',
            'golf_membership_fee',
            'golf_membership_fee_promo',

            // new
            'non_hoa_golf_membership_fee_2021',
        ];

        if (in_array($transaction->item, $memberships)) {
            ClientProfile::where('user_id', $transaction->user_id)->update([
                'golf_membership' => Carbon::now()->addYears(3),
            ]);
        }

        // Mail

        return response()->json(['message' => 'Success! Transaction marked as paid.'], 200);

    }
}
