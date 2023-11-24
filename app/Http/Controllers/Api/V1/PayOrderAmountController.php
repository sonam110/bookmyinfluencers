<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use Exception;
use DB;
use Str;
use Razorpay\Api\Api;
use App\Models\User;
use App\Models\PaymentHistory;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use App\Models\websiteSetting;
use App\Models\Order;
use App\Models\OrderProcess;
use App\Models\TransactionHistory;
use App\Models\Message;
use App\Models\Revealed;
use App\Models\GstInvoiceRequest;
use Notification;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Storage;
use Mail;
use App\Mail\HireInfluencerMail;
use App\Mail\CancelOrderMail;
use PDF;
class PayOrderAmountController extends Controller
{

      /* Pay Remaining Amount Using Paymnet gateway */
    public function payOrderAmount(Request $request) {

        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'amount' => 'required',
                            'internal_order_id' => 'required|exists:orders,id',
                                ],
                                [
                                    'amount.required' => 'Amount  is required',
                                    'internal_order_id.required' => 'Amount  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                /* Create Customer--------------- */
                $currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
                if ($user->customer_id == '') {
                    $createCustomer = $api->customer->create(array('name' => $user->fullname, 'email' => $user->email, 'contact' => $user->phone, 'notes' => array('company_name' => $user->company_name, 'address' => $user->address)));
                    $updateCustid = User::find($user->id);
                    $updateCustid->customer_id = $createCustomer['id'];
                    $updateCustid->save();
                }
                /* Create Order---------------- */
                $receipt = 'recipt-' . Str::random(4);
                $order = $api->order->create(array('receipt' => $receipt, 'amount' => round($request->amount) . '00', 'currency' => $currency, 'notes' => array('address' => $request->address, 'internal_order_id' => $request->internal_order_id)));
                $orderData = [];
                $orderData['profile'] = [
                    "customer_id" => $user->customer_id,
                    "name" => $user->fullname,
                    "email" => $user->email,
                    "contact" => $user->phone,
                ];
                $orderData['order'] = [];
                if (isset($order) && $order['status'] == 'created') {
                    $orderData['order'] = [
                        "order_id" => $order['id'],
                        "amount" => $order['amount'],
                        "currency" => $order['currency'],
                        "receipt" => $order['receipt'],
                        "status" => $order['status'],
                    ];
                    /* Save data to databse */
                    $saveData = new PaymentHistory;
                    $saveData->user_id = Auth::id();
                    $saveData->type = 'payment';
                    $saveData->bal_type = 'credit';
                    $saveData->customer_id = $user->customer_id;
                    $saveData->order_id = $order['id'];
                    $saveData->receipt = $order['receipt'];
                    $saveData->email = $user->email;
                    $saveData->contact = $user->phone;
                    $saveData->currency = $order['currency'];
                    $saveData->amount = $request->amount;
                    $saveData->save();
                }
                return prepareResult(true,'Payment order create Successfully', $orderData, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function saveOrderTransaction(Request $request) {

        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'payment_id' => 'required',
                            'order_id' => 'required',
                            'signature' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                /* -------Verify Signatutre --------------- */
                $attributes = array(
                    'razorpay_order_id' => $request->order_id,
                    'razorpay_payment_id' => $request->payment_id,
                    'razorpay_signature' => $request->signature
                );
                $order = $api->utility->verifyPaymentSignature($attributes);
                //print_r($order['signature']);
                //die;
                //if($order['signature']=='verified') {
                $data = [];
                $payemnt = $api->payment->fetch($request->payment_id);
                //print_r($payemnt);
                //die;
                $payment_order = PaymentHistory::where('order_id', $request->order_id)->first();
                if (is_object($payment_order)) {
                    $internal_order_id = $payemnt["notes"]['internal_order_id'];
                    $data = [
                        "payment_id" => $payemnt["id"],
                        "entity" => $payemnt["entity"],
                        "currency" => $payemnt["currency"],
                        "old_amount" => $payemnt["amount"] / 100,
                        "amount" => $payemnt["amount"] / 100,
                        "new_amount" => $payemnt["amount"] / 100,
                        "invoice_id" => $payemnt["invoice_id"],
                        "method" => $payemnt["method"],
                        "description" => $payemnt["description"],
                        "amount_refunded" => $payemnt["amount_refunded"],
                        "refund_status" => $payemnt["refund_status"],
                        "captured" => $payemnt["captured"],
                        "email" => $payemnt["email"],
                        "contact" => $payemnt["contact"],
                        "fee" => $payemnt["fee"],
                        "tax" => $payemnt["tax"],
                        "tax_amount" => ($payemnt["tax"] == 1 ) ? $payemnt["tax_amount"] : 0,
                        "error_code" => $payemnt["error_code"],
                        "error_description" => $payemnt["error_description"],
                        "error_reason" => $payemnt["error_reason"],
                        "card_id" => $payemnt["card_id"],
                        "card_info" => null,
                        "bank" => $payemnt["bank"],
                        "wallet" => $payemnt["wallet"],
                        "vpa" => $payemnt["vpa"],
                        "acquirer_data" => null,
                        "status" => $payemnt["status"],
                        "payemnt_status" => ($payemnt["status"] == 'captured') ? 'success' : 'failed',
                    ];
                    $saveData = PaymentHistory::where('id', $payment_order->id)->update($data);
                    $dataInfo = PaymentHistory::where('id', $payment_order->id)->first();
                    if ($payemnt["status"] == 'captured') {
                        $checkOrder = Order::where('id', $internal_order_id)->where('brand_id', $user->id)->first();
                        

                        $todayOrderCount = DB::table('orders')->select('id')->whereDate('created_at',date('Y-m-d'))->where('status','!=','3')->count();
                        $orderRandId = date('dmy').($todayOrderCount+1);
                        $File_Name = 'BMI-'.$orderRandId;
                        $File_Path = 'brand/script/' . $File_Name;
                        if($checkOrder->plateform_type =='2'){
                            $channel_name = DB::table('instagram_data')->select('id','username')->where('id',$checkOrder->channel_id)->first();
                            $byName = $channel_name->username;
                        } else{
                            $channel_name =  DB::table('channels')->select('id','channel_name')->where('id',$checkOrder->channel_id)->first();
                            $byName = $channel_name->channel_name;
                        }
                        $invoice_data =[
                            "payment_term"      => '0',
                            "camp_price"        => $checkOrder->camp_price,
                            "tax_per"           => $checkOrder->tax,
                            "tax_amount"        => $checkOrder->tax_amount,
                            "total_pay"         => $checkOrder->total_pay,
                            "pay_amount"        => $dataInfo->amount,
                            "remainingAmount"   => $checkOrder->total_pay - $dataInfo->amount,
                            "currency"          => ($payemnt["currency"] =='INR') ?'Rs.' :'$',
                            "checkApp"          => DB::table('campaigns')->select('id','brand_name','promot_product')->where('id',$checkOrder->camp_id)->first(),
                            "File_Name"         => $File_Name,
                            "userInfo"          => $user,
                            "channel_name"      => $byName,


                        ];

                        $pdf = PDF::loadView('invoice', $invoice_data);

                        $storageInvoicePath = Storage::disk('s3')->put($File_Path, $pdf->output(), 'public');
                        $invoice_url = Storage::disk('s3')->url($File_Path);



                        $order = Order::find($internal_order_id);
                        $order->pay_amount = $dataInfo->amount+$dataInfo->amount;
                        $order->payment_term = '0';
                        $order->invoice = $invoice_url;
                        $order->save();

                        transactionHistory($payemnt["id"], $user->id, '1', 'Remaining amount pay  successfully ', '2', '0', $dataInfo->amount,'0', $dataInfo->payemnt_status, '', $user->id, 'orders', $internal_order_id,$payemnt["currency"],'1');

                         message($checkOrder->id, $user->id, $checkOrder->influ_id, 'Congratulation! Remaining amount pay  successfully.', '0', '1',$checkOrder->channel_id,$checkOrder->plateform_type);

                    
                    }
                    //$this->savePayamentData($data);
                    return prepareResult(true, 'Pyament Successfull', $dataInfo, $this->success);
                } else {
                    return prepareResult(false, "Order Id Not Found", [], $this->not_found);
                }

                //}
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function savePayamentData($data) {

        $saveData = new PaymentHistory;
        $saveData->user_id = Auth::id();
        $saveData->type = $date->type;
        $saveData->bal_type = $date->bal_type;
        $saveData->plan_id = $date->plan_id;
        $saveData->order_id = $date->order_id;
        $saveData->payment_id = $date->payment_id;
        $saveData->entity = $date->entity;
        $saveData->currency = $date->currency;
        $saveData->old_amount = $date->old_amount;
        $saveData->amount = $date->amount;
        $saveData->new_amount = $date->new_amount;
        $saveData->invoice_id = $date->invoice_id;
        $saveData->method = $date->method;
        $saveData->description = $date->description;
        $saveData->amount_refunded = $date->amount_refunded;
        $saveData->refund_status = $date->refund_status;
        $saveData->captured = $date->captured;
        $saveData->email = $date->email;
        $saveData->contact = $date->contact;
        $saveData->fee = $date->fee;
        $saveData->tax = $date->tax;
        $saveData->tax_amount = $date->tax_amount;
        $saveData->error_code = $date->error_code;
        $saveData->error_description = $date->error_description;
        $saveData->error_reason = $date->error_reason;
        $saveData->card_id = $date->card_id;
        $saveData->card_info = $date->card_info;
        $saveData->bank = $date->bank;
        $saveData->wallet = $date->wallet;
        $saveData->vpa = $date->vpa;
        $saveData->acquirer_data = $date->acquirer_data;
        $saveData->status = $date->status;
        $saveData->payemnt_status = $date->payemnt_status;
        $saveData->save();
        if ($saveData) {
            return $saveData->id;
        }
        return false;
    }

    /*------------------------Pay full order amount--------*/
    public function orderPayment(Request $request) {
        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'application_id' => 'required',
                            //'job_description' => 'required_without:template_script',
                            //'template_script' => 'required_without:job_description',
                            'payment_term' => 'required|in:0,1',
                            'plateform_type' => 'required|in:1,2',
                                ],
                                [
                                    'application_id.required' => 'Application id field is required',
                                    //'job_description.required' => 'Job description is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $checkApp = AppliedCampaign::select('id','channel_id','influ_id','price','delivery_days','camp_id','brand_id','plateform_type','currency')->where('brand_id',$user->id)->where('id', $request->application_id)->where('plateform_type',$request->plateform_type)->with('campInfo')->first();
                
              
                if (empty($checkApp)) {
                    return prepareResult(false, "Application Not Found", [], $this->not_found);
                }
                $checkRevealedChannel = \DB::table('revealeds')->select('id')->where('user_id', $user->id)->where('channel_id', $checkApp->channel_id)->where('plateform_type',$request->plateform_type)->count();
                if ($checkRevealedChannel < 1) {
                    return prepareResult(false, "Please reveal the channel to hire this influencer.", [], $this->not_found);
                }

                $checkAppAlready = \DB::table('orders')->select('id')->where('brand_id', $user->id)->where('application_id',$request->application_id)->where('influ_id', $checkApp->influ_id)->where('channel_id',$checkApp->channel_id)->where('plateform_type',$request->plateform_type)->where('status','1')->count();
                // print_r($checkAppAlready);
                //die;
                if ($checkAppAlready > 0) {
                    return  prepareResult(false, "Application already submitted", [], $this->not_found);
                }
                $currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
                if($currency =='INR'){
                    $websiteSetting = websiteSetting::first();
                    $tax_per = $websiteSetting->tax_per;
                    $camp_price = $checkApp->price;
                    $tax_amount = ($camp_price * $tax_per) / 100;
                    $total_pay = $camp_price + $tax_amount;
                } else{
                    $camp_price = $checkApp->price;
                    $tax_per = 0;
                    $tax_amount = 0;
                    $total_pay = $camp_price;

                }
                
                $checkUserWallet = false;
                $remainUserwallet = $user->wallet_balance - $user->reserved_balance;
                $pay_amount = 0;
                if ($request->payment_term == '0') {
                    if ($remainUserwallet >= $total_pay) {
                        $checkUserWallet = true;
                    }
                    $pay_amount = $total_pay;
                }
                if ($request->payment_term == '1') {
                    $payfiftyPer = ($total_pay * 50) / 100;
                    if ($remainUserwallet >= $payfiftyPer) {
                        $checkUserWallet = true;
                    }
                    $pay_amount = $payfiftyPer;
                }
                
                $delivery_days =  ($checkApp->delivery_days != '') ? $checkApp->delivery_days : $checkApp->other_delivery_days;

                $todayOrderCount = DB::table('orders')->select('id')->whereDate('created_at',date('Y-m-d'))->where('status','!=','3')->count();
                $orderRandId = date('dmy').($todayOrderCount+1);
                $File_Name = 'BMI-'.$orderRandId;
                $File_Path = 'brand/script/' . $File_Name;
                if($request->plateform_type =='2'){
                    $channel_name = DB::table('instagram_data')->select('id','username')->where('id',$checkApp->channel_id)->first();
                    $byName = $channel_name->username;
                } else{
                    $channel_name =  DB::table('channels')->select('id','channel_name')->where('id',$checkApp->channel_id)->first();
                    $byName = $channel_name->channel_name;
                }
                $invoice_data =[
                    "payment_term"      => $request->payment_term,
                    "camp_price"        => $camp_price,
                    "tax_per"           => $tax_per,
                    "tax_amount"        => $tax_amount,
                    "total_pay"         => $total_pay,
                    "pay_amount"        => $pay_amount,
                    "remainingAmount"   => $total_pay - $pay_amount,
                    "currency"          => ($currency =='INR') ?'Rs.' :'$',
                    "checkApp"          => DB::table('campaigns')->select('id','brand_name','promot_product')->where('id',$checkApp->camp_id)->first(),
                    "File_Name"         => $File_Name,
                    "userInfo"          => $user,
                    "channel_name"      => $byName,


                ];

                $pdf = PDF::loadView('invoice', $invoice_data);

                $storageInvoicePath = Storage::disk('s3')->put($File_Path, $pdf->output(), 'public');
                $invoice_url = Storage::disk('s3')->url($File_Path);


                $orderAdd = new Order;
                $orderAdd->application_id = $request->application_id;
                $orderAdd->plateform_type = $request->plateform_type;
                $orderAdd->brand_id = $user->id;
                $orderAdd->influ_id = $checkApp->influ_id;
                $orderAdd->camp_id = $checkApp->camp_id;
                $orderAdd->channel_id = $checkApp->channel_id;
                $orderAdd->payment_term = $request->payment_term;
                $orderAdd->currency = $currency;
                $orderAdd->camp_price = $camp_price;
                $orderAdd->pay_amount = $pay_amount;
                $orderAdd->tax = $tax_per;
                $orderAdd->tax_amount = $tax_amount;
                $orderAdd->total_pay = $total_pay;
                $orderAdd->invoice = $invoice_url;
                $orderAdd->gst_invoice = ($request->gst_invoice ==1 )? 1:0;
                $orderAdd->deadlineDate = date('Y-m-d', strtotime("+".$delivery_days." days"));
                $orderAdd->payment_status = '0';
                $orderAdd->status = '3';
                $orderAdd->save();
                /*--------Gst invoice-*/
                if($request->gst_invoice==true){
                    $gstInvoice = new GstInvoiceRequest;
                    $gstInvoice->order_id = $orderAdd->id;
                    $gstInvoice->brand_id = $user->id;
                    $gstInvoice->influ_id = $orderAdd->influ_id;
                    $gstInvoice->camp_id = $orderAdd->camp_id;
                    $gstInvoice->channel_id = $orderAdd->channel_id;
                    $gstInvoice->save();

                }

                /* Process Order------------- */
                if (!empty($orderAdd)) {
                
                    $processOrder = new OrderProcess;
                    $processOrder->order_id = $orderAdd->id;
                    $processOrder->orderRandId = $orderRandId;
                    $processOrder->application_id = $request->application_id;
                    $processOrder->plateform_type = $request->plateform_type;
                    $processOrder->brand_id = $user->id;
                    $processOrder->influ_id = $orderAdd->influ_id;
                    $processOrder->camp_id = $orderAdd->camp_id;
                    $processOrder->channel_id = $orderAdd->channel_id;
                    $processOrder->action_taken = 'Place';
                    $processOrder->save();
                }

                /* Create Customer--------------- */

                /*if ($user->customer_id == '') {
                    $createCustomer = $api->customer->create(array('name' => $user->fullname, 'email' => $user->email, 'contact' => $user->phone, 'notes' => array('company_name' => $user->company_name, 'address' => $user->address)));
                    $updateCustid = User::find($user->id);
                    $updateCustid->customer_id = $createCustomer['id'];
                    $updateCustid->save();
                }*/
                /* Create Order---------------- */
                $receipt = 'recipt-' . Str::random(4);
                $order = $api->order->create(array('receipt' => $receipt, 'amount' => round($pay_amount) . '00', 'currency' => $currency, 'notes' => array('address' => $user->address,'orderRandId' => 
                    $orderRandId,'internal_order_id' => $orderAdd->id)));
                $orderData = [];
                $orderData['profile'] = [
                    "customer_id" => $user->customer_id,
                    "name" => $user->fullname,
                    "email" => $user->email,
                    "contact" => $user->phone,
                ];
                $orderData['order'] = [];
                if (isset($order) && $order['status'] == 'created') {
                    $orderData['order'] = [
                        "order_id" => $order['id'],
                        "amount" => $order['amount'],
                        "currency" => $order['currency'],
                        "receipt" => $order['receipt'],
                        "status" => $order['status'],
                    ];
                    /* Save data to databse */
                    $saveData = new PaymentHistory;
                    $saveData->user_id = Auth::id();
                    $saveData->type = 'payment';
                    $saveData->bal_type = 'credit';
                    $saveData->customer_id = $user->customer_id;
                    $saveData->order_id = $order['id'];
                    $saveData->receipt = $order['receipt'];
                    $saveData->email = $user->email;
                    $saveData->contact = $user->phone;
                    $saveData->currency = $order['currency'];
                    $saveData->amount = $pay_amount;
                    $saveData->save();
                }
                return prepareResult(true,'Payment order create Successfully', $orderData, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function saveOrderPayment(Request $request) {

        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'payment_id' => 'required',
                            'order_id' => 'required',
                            'signature' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                /* -------Verify Signatutre --------------- */
                $attributes = array(
                    'razorpay_order_id' => $request->order_id,
                    'razorpay_payment_id' => $request->payment_id,
                    'razorpay_signature' => $request->signature
                );
                $order = $api->utility->verifyPaymentSignature($attributes);
                //print_r($order['signature']);
                //die;
                //if($order['signature']=='verified') {
                $data = [];
                $payemnt = $api->payment->fetch($request->payment_id);
                //print_r($payemnt);
                //die;
                $payment_order = PaymentHistory::where('order_id', $request->order_id)->first();
                if (is_object($payment_order)) {
                    $internal_order_id = $payemnt["notes"]['internal_order_id'];
                    $orderRandId = $payemnt["notes"]['orderRandId'];
                    $data = [
                        "payment_id" => $payemnt["id"],
                        "entity" => $payemnt["entity"],
                        "currency" => $payemnt["currency"],
                        "old_amount" => $payemnt["amount"] / 100,
                        "amount" => $payemnt["amount"] / 100,
                        "new_amount" => $payemnt["amount"] / 100,
                        "invoice_id" => $payemnt["invoice_id"],
                        "method" => $payemnt["method"],
                        "description" => $payemnt["description"],
                        "amount_refunded" => $payemnt["amount_refunded"],
                        "refund_status" => $payemnt["refund_status"],
                        "captured" => $payemnt["captured"],
                        "email" => $payemnt["email"],
                        "contact" => $payemnt["contact"],
                        "fee" => $payemnt["fee"],
                        "tax" => $payemnt["tax"],
                        "tax_amount" => ($payemnt["tax"] == 1 ) ? $payemnt["tax_amount"] : 0,
                        "error_code" => $payemnt["error_code"],
                        "error_description" => $payemnt["error_description"],
                        "error_reason" => $payemnt["error_reason"],
                        "card_id" => $payemnt["card_id"],
                        "card_info" => null,
                        "bank" => $payemnt["bank"],
                        "wallet" => $payemnt["wallet"],
                        "vpa" => $payemnt["vpa"],
                        "acquirer_data" => null,
                        "status" => $payemnt["status"],
                        "payemnt_status" => ($payemnt["status"] == 'captured') ? 'success' : 'failed',
                    ];
                    $saveData = PaymentHistory::where('id', $payment_order->id)->update($data);
                    $dataInfo = PaymentHistory::where('id', $payment_order->id)->first();
                    if ($payemnt["status"] == 'captured') {
                
                        $updeOrder = Order::find($internal_order_id);
                        $updeOrder->status = '0';
                        $updeOrder->payment_status = '1';
                        $updeOrder->save();

                        $application = AppliedCampaign::find($updeOrder->application_id);
                        $application->status = '1';
                        $application->save();

                        $transaction_id = Str::random(10);
                        /* Transaction Histrory */
                        /* -----------For Brand/Infleuncer---------------------- */
                        transactionHistory($transaction_id, $updeOrder->influ_id, '7', 'Proposal accepted', '3','0', $updeOrder->pay_amount, '0', '1', '', $user->id, 'orders', $updeOrder->id,$updeOrder->currency,NULL);
                        /* for order process */
                        $payment_term = ($updeOrder->payment_term =='1') ? "(Balance 50% payment)" : "(Balance 100% payment)";
                        $orderMessage = "Order ID ".$orderRandId." ".$payment_term." ";
                        transactionHistory($transaction_id, $user->id, '1',$orderMessage, '2', '0', $updeOrder->pay_amount, '0', '1', '', $user->id, 'orders', $updeOrder->id,$updeOrder->currency,'1');
                        /* first entry in message */
                        message($updeOrder->id, $user->id, $updeOrder->influ_id, 'Congratulation! Your order started successfully..', '0', '1',$updeOrder->channel_id,$updeOrder->plateform_type);
                        /*$orderList = Order::where('id', $order->id)->with('infInfo', 'campInfo')->first();

                        $userInfo = User::find($order->influ_id);
                        Notification::send($userInfo, new ActivityNotification($orderList));
                       
                        $content = [
                            "user" => User::where('id', $order->brand_id)->first(),
                            "order" => Order::where('id', $order->id)->first()
                        ];
                        if (env('IS_MAIL_ENABLE', true) == true) {
                           // $creator = Mail::to(mailSendTo($order->brand_id))->send(new HireInfluencerMail($content));
                        }*/

                        if($updeOrder->plateform_type =='2'){
                            $channel_name = DB::table('instagram_data')->select('id','username')->where('id',$updeOrder->channel_id)->first();
                            $byName = $channel_name->username;
                        } else{
                            $channel_name =  DB::table('channels')->select('id','channel_name')->where('id',$updeOrder->channel_id)->first();
                            $byName = $channel_name->channel_name;
                        }

                        $content1 = [
                        "user" => DB::table('users')->select('id','fullname')->where('id', $updeOrder->influ_id)->first(),
                        "checkApp"          => DB::table('campaigns')->select('id','brand_name','promot_product')->where('id',$updeOrder->camp_id)->first(),
                        "channel_name"      => $byName,
                        ];
                        if (env('IS_MAIL_ENABLE', true) == true) {
                            $recevier = Mail::to(mailSendTo($updeOrder->influ_id))->send(new HireInfluencerMail($content1));
                        }

                        
                    }
                    //$this->savePayamentData($data);
                    return prepareResult(true, 'Pyament Successfull', $updeOrder, $this->success);
                } else {
                    return prepareResult(false, "Order Id Not Found", [], $this->not_found);
                }

                //}
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }
}
