<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use App\Models\websiteSetting;
use App\Models\Order;
use App\Models\OrderProcess;
use App\Models\TransactionHistory;
use App\Models\Message;
use App\Models\User;
use App\Models\Revealed;
use App\Models\GstInvoiceRequest;
use App\Models\OrderInvoiceManualPay;
use Validator;
use Auth;
use Exception;
use DB;
use Str;
use Notification;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Storage;
use Mail;
use App\Mail\HireInfluencerMail;
use App\Mail\CancelOrderMail;
use PDF;
class OrderController extends Controller {

    public function hireInfluencer(Request $request) {

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

                $checkApp = DB::table('applied_campaigns')->select('id','channel_id','influ_id','price','delivery_days','camp_id','brand_id','plateform_type')->where('brand_id',$user->id)->where('id', $request->application_id)->where('plateform_type',$request->plateform_type)->first();

                if ($checkApp =='') {
                    return prepareResult(false, "Application Not Found", [], $this->not_found);
                }
               
                $checkRevealedChannel = \DB::table('revealeds')->select('id')->where('user_id', $user->id)->where('channel_id', $checkApp->channel_id)->where('plateform_type',$request->plateform_type)->count();
                if ($checkRevealedChannel < 1) {
                    return prepareResult(false, "Please reveal the channel to hire this influencer.", [], $this->not_found);
                }

                $checkAppAlready = \DB::table('orders')->select('id')->where('brand_id', $user->id)->where('application_id',$request->application_id)->where('influ_id', $checkApp->influ_id)->where('channel_id',$checkApp->channel_id)->where('plateform_type',$request->plateform_type)->where('status','!=','3')->count();
               
                if ($checkAppAlready > 0) {
                    return  false;//prepareResult(false, "Application already submitted", [], $this->not_found); // return  false; 
                }

                if ($request->hasFile('template_script')) {
                    $validator = Validator::make($request->all(), [
                                'template_script' => 'mimes:doc,pdf,docx,zip',
                                    ],
                                    [
                                        'template_script.mimes' => 'Please upload file in doc, docx, pdf format',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }


                $template_script = '';
                if ($request->hasFile('template_script')) {
                    $file = $request->file('template_script');
                    $FileName = time() . '-' . $file->getClientOriginalName();
                    $FilePath = 'brand/script/' . $FileName;
                    $storagePath = Storage::disk('s3')->put($FilePath, file_get_contents($file), 'public');
                    $template_script = Storage::disk('s3')->url($FilePath);
                }

                $currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
                
                $tax_per =0;
                $camp_price = $checkApp->price;
                $tax_amount =0;
                $total_pay = $camp_price ;

                $checkUserWallet = false;
                $remainUserwallet = $user->wallet_balance;
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
                $insuffData = [
                    'userWallet' => $remainUserwallet,
                    'pay_amount' => $pay_amount,
                    'remainingAmount' => $pay_amount - $remainUserwallet,
                ];



                if ($checkUserWallet == false) {
                    return prepareResult(false, "Insufficient wallet balance, please recharge.", $insuffData, '411');
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
                    "currency"          => ($currency =='INR') ?'Rs' :'$',
                    "checkApp"          => DB::table('campaigns')->select('id','brand_name','promot_product')->where('id',$checkApp->camp_id)->first(),
                    "File_Name"         => $File_Name,
                    "userInfo"          => $user,
                    "channel_name"      => $byName,


                ];

                $pdf = PDF::loadView('invoice', $invoice_data);

                $storageInvoicePath = Storage::disk('s3')->put($File_Path, $pdf->output(), 'public');
                $invoice_url = Storage::disk('s3')->url($File_Path);
                
                $order = new Order;
                $order->application_id = $request->application_id;
                $order->plateform_type = $request->plateform_type;
                $order->brand_id = $user->id;
                $order->influ_id = $checkApp->influ_id;
                $order->camp_id = $checkApp->camp_id;
                $order->channel_id = $checkApp->channel_id;
                $order->job_description = $request->job_description;
                $order->template_script = $template_script;
                $order->payment_term = $request->payment_term;
                $order->currency = $currency;
                $order->camp_price = $camp_price;
                $order->pay_amount = $pay_amount;
                $order->tax = $tax_per;
                $order->tax_amount = $tax_amount;
                $order->total_pay = $total_pay;
                $order->invoice = $invoice_url;
                $order->gst_invoice = ($request->gst_invoice ==1 )? 1:0;
                $order->deadlineDate = date('Y-m-d', strtotime("+".$delivery_days." days"));
                $order->payment_status = '1';
                $order->save();
                /*--------Gst invoice-*/
                if($request->gst_invoice==true){
                    $gstInvoice = new GstInvoiceRequest;
                    $gstInvoice->order_id = $order->id;
                    $gstInvoice->brand_id = $user->id;
                    $gstInvoice->influ_id = $order->influ_id;
                    $gstInvoice->camp_id = $order->camp_id;
                    $gstInvoice->channel_id = $order->channel_id;
                    $gstInvoice->save();

                }

                /* Process Order------------- */
                if (!empty($order)) {
                    
                    $processOrder = new OrderProcess;
                    $processOrder->order_id = $order->id;
                    $processOrder->orderRandId = $orderRandId;
                    $processOrder->application_id = $request->application_id;
                    $processOrder->plateform_type = $request->plateform_type;
                    $processOrder->brand_id = $user->id;
                    $processOrder->influ_id = $checkApp->influ_id;
                    $processOrder->camp_id = $checkApp->camp_id;
                    $processOrder->channel_id = $checkApp->channel_id;
                    $processOrder->job_description = $request->job_description;
                    $processOrder->template_script = $template_script;
                    $processOrder->action_taken = 'Place';
                    $processOrder->save();
                }
                //print_r($processOrder);
                /* Application status */
                $application = AppliedCampaign:: find($request->application_id);
                $application->status = '1';
                $application->save();

                $transaction_id = Str::random(10);
                /* Transaction Histrory */
                $new_amount = $remainUserwallet - $pay_amount;
                /* -----------For Brand/Infleuncer---------------------- */
                transactionHistory($transaction_id, $checkApp->influ_id, '7', 'Proposal accepted', '3', $remainUserwallet, $pay_amount, $new_amount, '1', '', $user->id, 'orders', $order->id,@$currency,NULL);
                /* for order process */

                $payment_term = ($order->payment_term =='1') ? "(Balance 50% payment)" : "(Balance 100% payment)";
                $orderMessage = "Order ID ".$orderRandId." ".$payment_term." ";

                transactionHistory($transaction_id, $user->id, '1',$orderMessage, '2', $remainUserwallet, $pay_amount, $new_amount, '1', '', $user->id, 'orders', $order->id,@$currency,'0');
                /* update balance */
                balanceUpdate('1', '2', $user->id, $pay_amount);
                /* first entry in message */
                message($order->id, $user->id, $checkApp->influ_id, 'Congratulation! Your order started successfully..', '0', '1',$order->channel_id,$order->plateform_type);
               /* $orderList = Order::where('id', $order->id)->with('infInfo', 'campInfo')->first();

                $userInfo = User::find($checkApp->influ_id);
                Notification::send($userInfo, new ActivityNotification($orderList));*/
                /* Send Mail 
                $content = [
                    "user" => User::where('id', $order->brand_id)->first(),
                    "order" => Order::where('id', $order->id)->first()
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                   // $creator = Mail::to(mailSendTo($order->brand_id))->send(new HireInfluencerMail($content));
                }
                */
                $content1 = [
                    "user" => DB::table('users')->select('id','fullname')->where('id', $order->influ_id)->first(),
                    "checkApp"          => DB::table('campaigns')->select('id','brand_name','promot_product')->where('id',$checkApp->camp_id)->first(),
                    "channel_name"      => $byName,
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    $recevier = Mail::to(mailSendTo($order->influ_id))->send(new HireInfluencerMail($content1));
                }

                return prepareResult(true, 'Congratulation! Your order started successfully.', $order, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }
    public function increaseDeadline(Request $request) {
        try {

            $user = getUser();
            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                    'order_id' => 'required|exists:orders,id',
                    'delivery_days' => 'required'
    
                ],
                [
                    'order_id.required' => 'Order id field is required',
                    'delivery_days.required' => 'Please choose delivery days',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                if ($request->delivery_days == 'other') {
                    $validator = Validator::make($request->all(), [
                                'other_delivery_days' => 'required',
                                    ],
                                    [
                                        'other_delivery_days.required' => 'Please enter delivery days',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }

                $delivery_days =  ($request->delivery_days == 'other') ? $request->other_delivery_days : $request->delivery_days;

                $order = Order::find($request->order_id);
                $date = (!empty($order->new_deadlineDate)) ? $order->new_deadlineDate : $order->deadlineDate;
                $newDate = date('Y-m-d', strtotime("+".$delivery_days." days", strtotime($date)));

                $order->new_deadlineDate = $newDate;
                $order->save();
             
                return prepareResult(true, "Deadline changed successfully", $order, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function orderManualPayInvoice(Request $request) {

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

                $checkApp = DB::table('applied_campaigns')->select('id','channel_id','influ_id','price','delivery_days','camp_id','brand_id','plateform_type')->where('brand_id',$user->id)->where('id', $request->application_id)->where('plateform_type',$request->plateform_type)->first();

                if ($checkApp =='') {
                    return prepareResult(false, "Application Not Found", [], $this->not_found);
                }
               
                $checkRevealedChannel = \DB::table('revealeds')->select('id')->where('user_id', $user->id)->where('channel_id', $checkApp->channel_id)->where('plateform_type',$request->plateform_type)->count();
                if ($checkRevealedChannel < 1) {
                    return prepareResult(false, "Please reveal the channel to hire this influencer.", [], $this->not_found);
                }

                
                $currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
                if($currency=='INR'){
                    $websiteSetting = websiteSetting::first();
                    $tax_per = $websiteSetting->tax_per;
                    $camp_price = $checkApp->price;
                    $tax_amount = ($camp_price * $tax_per) / 100;
                    $total_pay = $camp_price + $tax_amount;

                } else{
                    $tax_per =0;
                    $camp_price = $checkApp->price;
                    $tax_amount =0;
                    $total_pay = $camp_price ;

                }
                if ($request->payment_term == '0') {
                   
                    $pay_amount = $total_pay;
                }
                if ($request->payment_term == '1') {
                    $payfiftyPer = ($total_pay * 50) / 100;
                    $pay_amount = $payfiftyPer;
                }
                
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
                    "currency"          => ($currency =='INR') ?'Rs' :'$',
                    "checkApp"          => DB::table('campaigns')->select('id','brand_name','promot_product')->where('id',$checkApp->camp_id)->first(),
                    "File_Name"         => $File_Name,
                    "userInfo"          => $user,
                    "channel_name"      => $byName,


                ];

                $pdf = PDF::loadView('order-manual-pay-invoice', $invoice_data);

                $storageInvoicePath = Storage::disk('s3')->put($File_Path, $pdf->output(), 'public');
                $invoice_url = Storage::disk('s3')->url($File_Path);
                
                $orderInvoice = new OrderInvoiceManualPay;
                $orderInvoice->application_id = $request->application_id;
                $orderInvoice->plateform_type = $request->plateform_type;
                $orderInvoice->brand_id = $user->id;
                $orderInvoice->influ_id = $checkApp->influ_id;
                $orderInvoice->camp_id = $checkApp->camp_id;
                $orderInvoice->channel_id = $checkApp->channel_id;
                $orderInvoice->payment_term = $request->payment_term;
                $orderInvoice->currency = $currency;
                $orderInvoice->camp_price = $camp_price;
                $orderInvoice->pay_amount = $pay_amount;
                $orderInvoice->tax = $tax_per;
                $orderInvoice->tax_amount = $tax_amount;
                $orderInvoice->total_pay = $total_pay;
                $orderInvoice->invoice = $invoice_url;
                $orderInvoice->payment_status = '0';
                $orderInvoice->save();
                
                return prepareResult(true, 'Invoice! download successfully.', $orderInvoice, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

   public function liveOrders(Request $request) {

        try {
            $user = getUser();
            $orderList = [];
            $plateform_type = (!empty($request->plateform_type)) ? $request->plateform_type:'1';
            if ($user->roles[0]->name == 'brand') {
                if($plateform_type =='2'){
                    $query = Order::select(array('orders.*','order_processes.*','campaigns.*','applied_campaigns.*', 'instagram_data.*','order_processes.id as order_processes_id','campaigns.id as camp_id','orders.id as id','orders.created_at as created_at','orders.currency as currency','instagram_data.id as channelid','applied_campaigns.id as application_id','order_processes.status as order_processes_status','order_processes.comment as comment','campaigns.status as camp_status','orders.status as status','instagram_data.status as channel_sttaus','applied_campaigns.status as application_status',DB::raw("(SELECT count(id) from messages WHERE messages.receiver_id = " . $user->id . "  and  is_read = '0' and orders.channel_id = messages.channel_id ) messageCount")))->where('orders.brand_id', $user->id)->where('orders.status','!=','3')
                
                    ->leftJoin('order_processes','orders.id','=','order_processes.order_id')
                    ->leftJoin('campaigns','campaigns.id','=','orders.camp_id')
                    ->leftJoin('applied_campaigns','applied_campaigns.id','=','orders.application_id')
                    ->leftJoin('instagram_data','instagram_data.id','=','orders.channel_id')
                    ->orderby('orders.id','DESC');

                } else{
                    $query = Order::select(array('orders.*','order_processes.*','campaigns.*','applied_campaigns.*', 'channels.*','order_processes.id as order_processes_id','campaigns.id as camp_id','orders.id as id','orders.created_at as created_at','orders.currency as currency','channels.id as channelid','applied_campaigns.id as application_id','order_processes.status as order_processes_status','order_processes.comment as comment','campaigns.status as camp_status','orders.status as status','channels.status as channel_sttaus','applied_campaigns.status as application_status',DB::raw("(SELECT count(id) from messages WHERE messages.receiver_id = " . $user->id . "  and  is_read = '0' and orders.channel_id = messages.channel_id ) messageCount")))->where('orders.brand_id', $user->id)
                    //->with('campInfo:id,brand_name,camp_title,budget,currency,deal_type,status,camp_desc,promot_product', 'appInfo:id,influ_id,brand_id,influ_id,camp_id,old_duration,new_duration,promotion_slot,new_promotion_slot,currency,price,view_commitment,min_views,minor_changes,delivery_days,other_delivery_days,status', 'orderProcess')
                    ->where('orders.status','!=','3')
                    ->leftJoin('order_processes','orders.id','=','order_processes.order_id')
                    ->leftJoin('campaigns','campaigns.id','=','orders.camp_id')
                    ->leftJoin('applied_campaigns','applied_campaigns.id','=','orders.application_id')
                    ->leftJoin('channels','channels.id','=','orders.channel_id')
                    ->orderby('orders.id','DESC');

                }

                $query->where('orders.plateform_type',$plateform_type);
                if ($request->status!='') {
                        $query = $query->where('orders.status',''.$request->status.'');
                
                }
                if(!empty($request->perPage))
                {
                    $perPage = $request->perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $orders = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                    
                   
                   
                }
                else
                {
                    $orders = $query->get();
                    $total = $query->count();
                    $page = '1';
                    $perPage = '10';
                    $last_page = ceil($total / 10);
                }
                $orderCount = DB::table('orders')->select(DB::raw('COUNT(id) as totalCount'),
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as liveCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as cancelledCount'),
                    DB::raw('COUNT(IF(status = "1", 0, NULL)) as completedCount'),
                 )->where('brand_id', $user->id)->where('orders.plateform_type',$plateform_type)->where('orders.status','!=','3')->first();
                
                $orderList['orders'] = $orders;
                $orderList['total'] = $total;
                $orderList['current_page'] = $page;
                $orderList['last_page'] = $last_page;
                $orderList['per_page'] = $perPage;
                $orderList['totalCount'] = @$orderCount->totalCount;
                $orderList['liveCount'] = @$orderCount->liveCount;
                $orderList['cancelledCount'] = @$orderCount->cancelledCount;
                $orderList['completedCount'] = @$orderCount->completedCount;
            }
            if ($user->roles[0]->name == 'influencer') {
                if($plateform_type =='2'){
                    $query = Order::select(array('orders.*','order_processes.*','campaigns.*','applied_campaigns.*', 'instagram_data.*','order_processes.id as order_processes_id','campaigns.id as camp_id','orders.id as id','orders.currency as currency','instagram_data.id as channelid','applied_campaigns.id as application_id','order_processes.status as order_processes_status','order_processes.comment as comment','campaigns.status as camp_status','orders.status as status','instagram_data.status as channel_sttaus','applied_campaigns.status as application_status',DB::raw("(SELECT count(id) from messages WHERE messages.receiver_id = " . $user->id . " and   is_read = '0' and orders.channel_id = messages.channel_id ) messageCount")))->where('orders.influ_id', $user->id)
                    //->with('campInfo:id,brand_name,camp_title,budget,currency,deal_type,status,camp_desc,promot_product', 'appInfo:id,influ_id,brand_id,influ_id,camp_id,old_duration,new_duration,promotion_slot,new_promotion_slot,currency,price,view_commitment,min_views,minor_changes,delivery_days,other_delivery_days,status', 'orderProcess')
                    ->leftJoin('order_processes','orders.id','=','order_processes.order_id')
                    ->leftJoin('campaigns','campaigns.id','=','orders.camp_id')
                    ->leftJoin('applied_campaigns','applied_campaigns.id','=','orders.application_id')
                    ->leftJoin('instagram_data','instagram_data.id','=','orders.channel_id')
                    ->orderby('orders.id','DESC');

                } else{
                    $query = Order::select(array('orders.*','order_processes.*','campaigns.*','applied_campaigns.*', 'channels.*','order_processes.id as order_processes_id','campaigns.id as camp_id','orders.id as id','orders.currency as currency','channels.id as channelid','applied_campaigns.id as application_id','order_processes.status as order_processes_status','order_processes.comment as comment','campaigns.status as camp_status','orders.status as status','channels.status as channel_sttaus','applied_campaigns.status as application_status',DB::raw("(SELECT count(id) from messages WHERE messages.receiver_id = " . $user->id . "  and  is_read = '0' and orders.channel_id = messages.channel_id ) messageCount")))->where('orders.influ_id', $user->id)
                    //->with('campInfo:id,brand_name,camp_title,budget,currency,deal_type,status,camp_desc,promot_product', 'appInfo:id,influ_id,brand_id,influ_id,camp_id,old_duration,new_duration,promotion_slot,new_promotion_slot,currency,price,view_commitment,min_views,minor_changes,delivery_days,other_delivery_days,status', 'orderProcess')
                    ->leftJoin('order_processes','orders.id','=','order_processes.order_id')
                    ->leftJoin('campaigns','campaigns.id','=','orders.camp_id')
                    ->leftJoin('applied_campaigns','applied_campaigns.id','=','orders.application_id')
                    ->leftJoin('channels','channels.id','=','orders.channel_id')
                    ->orderby('orders.id','DESC');

                }
                $query->where('orders.plateform_type',$plateform_type);
                if ($request->status!='') {
                        $query = $query->where('orders.status',''.$request->status.'');
                
                }
                if(!empty($request->perPage))
                {
                    $perPage = $request->perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $orders = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $orders = $query->get();
                    $total = $query->count();
                    $page = '1';
                    $perPage = '10';
                    $last_page = ceil($total / 10);
                }

                $orderCount = DB::table('orders')->select(DB::raw('COUNT(id) as totalCount'),
                    DB::raw('COUNT(IF(status = "0", 0, NULL)) as liveCount'),
                    DB::raw('COUNT(IF(status = "2", 0, NULL)) as cancelledCount'),
                    DB::raw('COUNT(IF(status = "1", 0, NULL)) as completedCount'),
                 )->where('influ_id', $user->id)->where('orders.plateform_type',$plateform_type)->where('orders.status','!=','3')->first();
                
                $orderList['orders'] = $orders;
                $orderList['total'] = $total;
                $orderList['current_page'] = $page;
                $orderList['last_page'] = $last_page;
                $orderList['per_page'] = $perPage;
                $orderList['totalCount'] = @$orderCount->totalCount;
                $orderList['liveCount'] = @$orderCount->liveCount;
                $orderList['cancelledCount'] = @$orderCount->cancelledCount;
                $orderList['completedCount'] = @$orderCount->completedCount;
                
            }


            return prepareResult(true, "Live order list", $orderList, $this->success);
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }


    public function viewOrder(Request $request) {

        try {
            $user = getUser();
            $validator = Validator::make($request->all(), [
                        'order_id' => 'required',
                            ],
                            [
                                'order_id.required' => 'Order id is required',
            ]);

            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $checkOrder = DB::table('orders')->where('id',$request->order_id)->first();
            if(empty($checkOrder)){
                return prepareResult(false, "Order Not Found", [], $this->not_found);
            }
            

            if ($user->roles[0]->name == 'brand') {
                if($checkOrder->plateform_type =='1'){
                    $orderView = Order::where('id', $request->order_id)->where('brand_id', $user->id)->with('campInfo', 'appInfo:id,influ_id,brand_id,influ_id,camp_id,old_duration,new_duration,promotion_slot,new_promotion_slot,currency,price,view_commitment,min_views,minor_changes,delivery_days,other_delivery_days,status', 'orderProcess','channelInfo:id,channel_name')->first();
                } else{
                    $orderView = Order::where('id', $request->order_id)->where('brand_id', $user->id)->with('campInfo', 'appInfo:id,influ_id,brand_id,influ_id,camp_id,old_duration,new_duration,promotion_slot,new_promotion_slot,currency,price,view_commitment,min_views,minor_changes,delivery_days,other_delivery_days,status', 'orderProcess','InstagramData:id,username')->first();
                }
                
            }
            if ($user->roles[0]->name == 'influencer') {
                if($checkOrder->plateform_type =='1'){
                    $orderView = Order::where('id', $request->order_id)->where('influ_id', $user->id)->with('campInfo', 'appInfo', 'orderProcess','channelInfo:id,channel_name')->first();
                } else{
                     $orderView = Order::where('id', $request->order_id)->where('influ_id', $user->id)->with('campInfo', 'appInfo', 'orderProcess','InstagramData:id,username')->first();
                }
            }

            return prepareResult(true, "Order View", $orderView, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function transactionHistory(Request $request) {

        try {
            $user = getUser();
            $transactionHistory = [];
            if ($request->type == '1') {
                $from = (auth()->user()->currency =='INR') ? 'USD': 'INR';
                $wallect_amount = currencyConvert($from,auth()->user()->currency,$user->wallet_balance);
                $totalBalance = $user->wallet_balance ;
                $totalUsed = \DB::table('transaction_histories')->where('user_id', $user->id)->where('type', '1')->where('bal_type', '2')->sum('amount');
            } else {

                $totalBalance = $user->credit_balance;
                $totalUsed = \DB::table('transaction_histories')->where('user_id', $user->id)->where('type', '2')->where('bal_type', '2')->sum('amount');
            }
            
            
            $query = \DB::table('transaction_histories')->where('user_id', $user->id)
                    ->whereIn('type', ['1', '2'])
                    ->orderBy('id', 'DESC');
            if ($request->status!='') {
                $query = $query->where('status',''.$request->status.'');
            
            }
            if ($request->type!='') {
                $query = $query->where('type',''.$request->type.'');
            
            }

            if (!empty($request->perPage)) {
                $perPage = $request->perPage;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination = [
                    'data' => $result,
                    'total' => $total,
                    'totalBalance' => $totalBalance,
                    'totalUsed' => $totalUsed,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($total / $perPage)
                ];
                return prepareResult(true, "Transaction list", $pagination, $this->success);
            } else {
                $query = $query->get();
            }


            return prepareResult(true, "Transaction list", $query, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function orderHistory(Request $request) {

        try {
            $user = getUser();
            $totalUsed = DB::table('orders')->where('influ_id', $user->id)->where('status', '1')->where('payment_status', '1')->sum('total_pay');
            $query = Order::select(array('orders.*','order_processes.*','campaigns.*','order_processes.id as order_processes_id','campaigns.id as camp_id','orders.id as id','orders.currency as currency','order_processes.status as order_processes_status','campaigns.status as camp_status','orders.status as status'))->where('orders.influ_id', $user->id)
                ->leftJoin('order_processes','orders.id','=','order_processes.order_id')
                ->leftJoin('campaigns','campaigns.id','=','orders.camp_id')
                ->orderby('orders.id','DESC');
            $totalCount = $query->count();

            if (!empty($request->perPage)) {
                $perPage = $request->perPage;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination = [
                    'data' => $result,
                    'total' => $total,
                    'totalCount' => $totalCount,
                    'totalUsed' => $totalUsed,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($total / $perPage)
                ];
                return prepareResult(true, "Influencer's order list", $pagination, $this->success);
            } else {
                $query = $query->get();
            }
            return prepareResult(true, "Influencer's order list", $query, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function cancelOrder(Request $request) {


        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {

                $validator = Validator::make($request->all(), [
                            'order_id' => 'required',
                                ],
                                [
                                    'order_id.required' => 'Order id is required',
                ]);

                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $checkOrder = Order::where('brand_id', $user->id)->where('id', $request->order_id)->where('status', '!=', '1')->first();
                if (!is_object($checkOrder)) {
                    return prepareResult(false, "Order Not Found", [], $this->not_found);
                }
                /* update order status */

                $updateOrder = Order::where('id', $request->order_id)->update(['status' => '2']);
                $order = Order::where('brand_id', $user->id)->where('id', $request->order_id)->first();

                $updateOrderProStatus = OrderProcess::where('order_id', $request->order_id)->update(['status' => '5']);
                $messagStatus = Message::where('order_id', $request->order_id)->update(['status' => '5']);
                /* Message send to user */
                message($request->order_id, $user->id, $checkOrder->influ_id, 'Your order is cancelled', '0', '5',$checkOrder->channel_id,$checkOrder->plateform_type);
                /* balance update */
                balanceUpdate('1', '1', $user->id, $checkOrder->total_pay);

                $transaction_id = Str::random(10);
                /* Transaction Histrory */
                $old_amount = $user->wallet_balance - $user->reserved_balance;
                $new_amount = $old_amount - $checkOrder->total_pay;
                transactionHistory($transaction_id, $checkOrder->influ_id, '1', 'Your Order Cancelled', '1', $old_amount, $checkOrder->total_pay, $new_amount, '0', '', $user->id, 'orders', $checkOrder->id,$checkOrder->currency,NULL);

                $userInfo = User::find($checkOrder->influ_id);

                /* send mail */
                $content = [
                    "order" => Order::where('id', $checkOrder->id)->first()
                ];
                if (env('IS_MAIL_ENABLE', true) == true) {
                    $influencer = Mail::to(mailSendTo($order->influ_id))->send(new CancelOrderMail($content));
                    $brand = Mail::to(mailSendTo($order->brand_id))->send(new CancelOrderMail($content));
                }
                Notification::send($userInfo, new ActivityNotification($order));

                return prepareResult(true, "Order cancelled successfully", $order, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    private function getWhereRawFromRequest(Request $request) {
        $w = '';
        if (is_null($request->input('status')) == false) {
            if ($w != '') {
                $w = $w . " AND ";
            }
            $w = $w . "(" . "status = " . "'" . $request->input('status') . "'" . ")";
        }
        return($w);
    }

    private function getWhereRawFromRequest1(Request $request) {
        $w = '';
        if (is_null($request->input('type')) == false) {
            if ($w != '') {
                $w = $w . " AND ";
            }
            $w = $w . "(" . "type = " . "'" . $request->input('type') . "'" . ")";
        }
        return($w);
    }

}
