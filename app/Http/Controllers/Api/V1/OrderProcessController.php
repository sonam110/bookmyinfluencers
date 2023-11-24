<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\AppliedCampaign;
use App\Models\websiteSetting;
use App\Models\Order;
use App\Models\OrderProcess;
use App\Models\User;
use Mail;
use App\Mail\OrderCompleteMail;
use App\Mail\CampaignResourceApprovedMail;
use App\Mail\VideoScriptSubmitMail;
use App\Mail\VideoScriptApprovedMail;
use App\Mail\VideoPreviewSubmitMail;
use App\Mail\VideoPreviewApprovedMail;
use App\Mail\VideoUrlSubmitdMail;
use App\Mail\VideoUrlApprovedMail;
use App\Mail\RequestChangeByInfuMail;
use App\Mail\RequestChangeByBrandMail;
use Validator;
use Auth;
use Exception;
use Str;
use DB;
use Notification;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Storage;

class OrderProcessController extends Controller {

    public function orderProcess(Request $request) {

        try {
            $user = getUser();
            $userInfo = User::find($user->id);
            $validator = Validator::make($request->all(), [
                        'order_id' => 'required',
                        'action_taken' => 'required|in:Review,RequestChange,Submit,Approved',
                        'stage' => 'required|numeric',
                            ],
                            [
                                'order_id.required' => 'Order id field is required',
                                'action_taken.required' => 'Action name is required',
                                'stage.required' => 'Stage is required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }

            $checkOrder = Order::where('id', $request->order_id)->with('infInfo','brandInfo','orderProcess','channelInfo','campInfo');
            if ($user->roles[0]->name == 'brand') {
                $checkOrder = $checkOrder->where('brand_id', $user->id)->with('campInfo')->first();
                if (!is_object($checkOrder)) {
                    return prepareResult(false, "Order id Not Found", [], $this->not_found);
                }
                $sender_id = $user->id;
                $receiver_id = $checkOrder->influ_id;

                /* ----Pay 50% Payment-------------------- */
                if ($request->stage == '2' && $request->action_taken != 'RequestChange') {
                    if ($checkOrder->payment_term == '1') {
                        $reMainingData = [
                            'userWallet' => $user->wallet_balance,
                            'pay_amount' => $checkOrder->pay_amount,
                            'remainingAmount' => $checkOrder->total_pay - $checkOrder->pay_amount,
                        ];
                        return prepareResult(false, "Please pay remaining 50 % payment", $reMainingData, '411');
                    }
                }
            }
            if ($user->roles[0]->name == 'influencer') {
                $checkOrder = $checkOrder->where('influ_id', $user->id)->with('campInfo')->first();
                if (!is_object($checkOrder)) {
                    return prepareResult(false, "Order id Not Found", [], $this->not_found);
                }
                $sender_id = $user->id;
                $receiver_id = $checkOrder->brand_id;
            }

            if($checkOrder->plateform_type =='2'){
                $channel = DB::table('instagram_data')->select('id','username')->where('id',$checkOrder->channel_id)->first();
                $channel_name = @$channel->username;
            } else{
                $channel =  DB::table('channels')->select('id','channel_name')->where('id',$checkOrder->channel_id)->first();
                $channel_name = @$channel->channel_name;
            }
            $order_process = DB::table('order_processes')->where('order_id', $request->order_id)->first();
           /* if (empty($order_process)) {
                $todayOrderCount = DB::table('orders')->select('id')->whereDate('created_at',date('Y-m-d'))->where('status','0')->count();
                $orderRandId = date('dmy').($todayOrderCount+1);
                
                $order_process = new OrderProcess;
                $order_process->order_id = $request->order_id;
                $order_process->orderRandId = $orderRandId;
                $order_process->application_id = $checkOrder->application_id;
                $order_process->plateform_type = $checkOrder->plateform_type;
                $order_process->brand_id = $user->id;
                $order_process->influ_id = $checkOrder->influ_id;
                $order_process->camp_id = $checkOrder->camp_id;
                $order_process->channel_id = $checkOrder->channel_id;
                $order_process->action_taken = 'Place';
                $order_process->save();
            }*/
            $is_stage_complete = true;
            if ($order_process->status != '4' && $order_process->stage != $request->stage) {
                $is_stage_complete = false;
            }
            if ($is_stage_complete == false) {
                return prepareResult(false, 'Please complete your current stage-' . $order_process->stage . '', [], $this->unprocessableEntity);
            }

            if ($request->action_taken == 'Review') {
                $orderProcess = OrderProcess::find($order_process->id);
                $orderProcess->action_taken = $request->action_taken;
                $orderProcess->status = '1';
                $orderProcess->stage = $request->stage;
                $orderProcess->comment = $request->comment;
                $orderProcess->save();
                return prepareResult(true, "Review", $order_process, $this->success);
            }
            if ($request->action_taken == 'RequestChange') {
                $validator = Validator::make($request->all(), [
                            'comment' => ['required'],
                                ],
                                [
                    'comment.required' => 'Please describe changes in comment section',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $sMessage = 'Request Change';
                if ($request->stage == '1') {
                    $sMessage = 'Requested change in campaign brief for Order ID ' . @$order_process->orderRandId . '';
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$checkOrder->brandInfo->email)->send(new RequestChangeByInfuMail($content));
                    }
                }
                if ($request->stage == '2') {
                    $sMessage = 'Requested change in video script for Order ID ' . @$order_process->orderRandId . '';
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "title" => 'video script',
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(mailSendTo($checkOrder->influ_id))->send(new RequestChangeByBrandMail($content));
                    }
                }
                if ($request->stage == '3') {
                    $sMessage = 'Requested change in video preview for Order ID ' . @$order_process->orderRandId . '';
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "title" => 'video preview',
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(mailSendTo($checkOrder->influ_id))->send(new RequestChangeByBrandMail($content));
                    }
                }
                if ($request->stage == '4') {
                    $sMessage = 'Requested change in live video for Order ID ' . @$order_process->orderRandId . '';
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "title" => 'Live video',
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(mailSendTo($checkOrder->influ_id))->send(new RequestChangeByBrandMail($content));
                    }
                }

                $orderProcess = OrderProcess::find($order_process->id);
                $orderProcess->action_taken = $request->action_taken;
                $orderProcess->status = '3';
                $orderProcess->stage = $request->stage;
                $orderProcess->promo_text_link = $request->promo_text_link;
                $orderProcess->comment = $request->comment;
                $orderProcess->save();
                //Notification::send($userInfo, new ActivityNotification($orderProcess));

                transactionHistory($orderProcess->id, $receiver_id, '8', ''.$sMessage.'', '3', '0', '0', '0', '1', '', $sender_id, 'order_processes', $orderProcess->id,$checkOrder->currency,NULL);

                /* first entry in message */
                message($request->order_id, $sender_id, $receiver_id, ''.$sMessage.'', '0', '3',$checkOrder->channel_id,$checkOrder->plateform_type);
                return prepareResult(true, $sMessage, $orderProcess, $this->success);
            }
            if ($request->action_taken == 'Submit') {
                $submitMsg = "Campaign brief Submitted for Order ID " . @$order_process->orderRandId . "";
                if ($request->stage == '1') {
                    $file_name = @$checkOrder->template_script;
                }
                if ($request->stage == '2') {
                    $file_name = @$order_process->video_script;
                    $submitMsg = "Video Script Submitted for Order ID " . @$order_process->orderRandId . "";
                }
                if ($request->stage == '3') {
                    $file_name = @$request->file_name;
                    $submitMsg = "Video Preview Submitted for Order ID " . @$order_process->orderRandId . "";
                }
                if ($request->stage == '4') {
                    $file_name = @$request->file_name;
                    $submitMsg = "Live Video Submitted for Order ID " . @$order_process->orderRandId . "";
                }
                if ($request->stage == '1') {
                    $validator = Validator::make($request->all(), [
                                'job_description' => 'required_without:file_name',
                                'file_name' => 'required_without:job_description',
                                    ],
                                    [
                        'job_description.required' => 'Campaign brief document is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                if ($request->stage == '2') {
                    $validator = Validator::make($request->all(), [
                                'video_script_desc' => 'required_without:file_name',
                                'file_name' => 'required_without:video_script_desc',
                                    ],
                                    [
                                        'video_script_desc.required' => 'Video script description is required',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                if ($request->stage == '3') {
                    $validator = Validator::make($request->all(), [
                        'file_name' => ['required'],
                        ],
                        [
                        'file_name.required' => 'Please put video  preview url',
                                       
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                } if ($request->stage == '4') {
                    $validator = Validator::make($request->all(), [
                        'file_name' => ['required'],
                        'is_text_link_provided' => ['required'],
                        ],
                        [
                        'file_name.required' => 'Please put live video url',
                        'is_text_link_provided.required' => 'Please click on checkbox',
                                       
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                if ($request->stage == '1' || $request->stage == '2') {
                    if ($request->hasFile('file_name')) {
                        $file = $request->file('file_name');
                        $FileName = time() . '-' . $file->getClientOriginalName();
                        $FilePath = 'brand/script/' . $FileName;
                        $storagePath = Storage::disk('s3')->put($FilePath, file_get_contents($file), 'public');
                        $file_name = Storage::disk('s3')->url($FilePath);
                    }
                }
                $orderProcess = OrderProcess::find($order_process->id);
                $orderProcess->comment = $request->comment;
                if ($request->stage == '1') {
                    $orderProcess->template_script = (!empty($request->file_name)) ? $file_name : NULL;
                    $orderProcess->job_description = (!empty($request->job_description)) ? $request->job_description: NULL;

                    $orderUpdate = Order::find($request->order_id);
                    $orderUpdate->template_script =  ( !empty($request->file_name)) ? $file_name : NULL;
                    $orderUpdate->job_description = (!empty($request->job_description)) ? $request->job_description: NULL;
                    $orderUpdate->save();
                }
                if ($request->stage == '2') {
                    $orderProcess->video_script = ( !empty($request->file_name)) ? $file_name : NULL;
                    $orderProcess->video_script_desc = (!empty($request->video_script_desc)) ? $request->video_script_desc : NULL;
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$checkOrder->brandInfo->email)->send(new VideoScriptSubmitMail($content));
                    }
                }
                if ($request->stage == '3') {
                    $orderProcess->video_preview = ( !empty($request->file_name)) ? $request->file_name : NULL;
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$checkOrder->brandInfo->email)->send(new VideoPreviewSubmitMail($content));
                    }
                }
                if ($request->stage == '4') {
                    $orderProcess->live_video = ( !empty($request->file_name)) ? $request->file_name : NULL;
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$checkOrder->brandInfo->email)->send(new VideoUrlSubmitdMail($content));
                    }
                }
                $orderProcess->status = '2';
                $orderProcess->action_taken = $request->action_taken;
                $orderProcess->stage = $request->stage;
                $orderProcess->promo_text_link = $request->promo_text_link;
                $orderProcess->is_text_link_provided = ($request->is_text_link_provided == '1') ? 1:0;
                $orderProcess->comment = $request->comment;
                $orderProcess->save();
                //Notification::send($userInfo, new ActivityNotification($orderProcess));
                transactionHistory($orderProcess->id, $receiver_id, '8', ''.$submitMsg.'', '3', '0', '0', '0', '1', '', $sender_id, 'order_processes', $orderProcess->id,$checkOrder->currency,NULL);

                /* first entry in message */
                message($request->order_id, $sender_id, $receiver_id, ''.$submitMsg.'', '0', '2',$checkOrder->channel_id,$checkOrder->plateform_type);
                return prepareResult(true, $submitMsg, $orderProcess, $this->success);
            }
            if ($request->action_taken == 'Approved') {
                $orderProcess = OrderProcess::find($order_process->id);
                $sMessage = "Order Completed";
                if ($request->stage == '1') {
                    $orderProcess->camp_script_approval_date = date('Y-m-d H:i:s');
                    $sMessage = "Campaign brief is approved successfully for  Order ID " . @$order_process->orderRandId . "";
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$checkOrder->brandInfo->email)->send(new CampaignResourceApprovedMail($content));
                    }

                }
                if ($request->stage == '2') {
                    $orderProcess->video_script_approval_date = date('Y-m-d H:i:s');
                    $sMessage = "Video script is approved successfully for Order ID " . @$order_process->orderRandId . "";
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(mailSendTo($checkOrder->influ_id))->send(new VideoScriptApprovedMail($content));
                    }
                }
                if ($request->stage == '3') {
                    $orderProcess->video_prev_approval_date = date('Y-m-d H:i:s');
                    $sMessage = "Video preview is approved successfully for Order ID " . @$order_process->orderRandId . "";
                    $content = [
                        "brand_name" => @$checkOrder->brandInfo->fullname,
                        "influ_name" => @$checkOrder->infInfo->fullname,
                        "order" => $checkOrder,
                        "plateform_type" => $checkOrder->plateform_type,
                        "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(mailSendTo($checkOrder->influ_id))->send(new VideoPreviewSubmitMail($content));
                    }
                }
                if ($request->stage == '4') {
                    $orderProcess->live_video_approval_date = date('Y-m-d H:i:s');
                    $sMessage = "Live Video is approved successfully for Order ID " . @$order_process->orderRandId . "";
                    
                }
                $orderProcess->action_taken = $request->action_taken;
                $orderProcess->status = '4';
                $orderProcess->stage = $request->stage;
                $orderProcess->comment = $request->comment;
                $orderProcess->promo_text_link = $request->promo_text_link;
                $orderProcess->save();
                if ($request->stage == '4') {
                    $updateOrder = Order::where('id', $request->order_id)->update(['status' => '1']);
                    $updateCamp = Campaign::where('id', $request->order_id)->update(['status' => '1']);
                    $updateApp = AppliedCampaign::where('id', $checkOrder->application_id)->update(['status' => '5']);
                    /*--------order Complete mail to brand--------------*/
                    $content = [
                    "brand_name" => @$checkOrder->brandInfo->fullname,
                    "influ_name" => @$checkOrder->infInfo->fullname,
                    "order" => $checkOrder,
                    "type" => 'brand',
                    "plateform_type" => $checkOrder->plateform_type,
                    "channel_name" => $channel_name,
                    ];
                    $content1 = [
                    "brand_name" => @$checkOrder->brandInfo->fullname,
                    "influ_name" => @$checkOrder->infInfo->fullname,
                    "order" => $checkOrder,
                    "type" => 'influencer',
                    "plateform_type" => $checkOrder->plateform_type,
                    "channel_name" => $channel_name,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$checkOrder->brandInfo->email)->send(new OrderCompleteMail($content));
                    }
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(mailSendTo($checkOrder->influ_id))->send(new OrderCompleteMail($content1));
                    }

                }

                /* entry in message */

                message($request->order_id, $sender_id, $receiver_id, ''.$sMessage.'', '0', '4',$checkOrder->channel_id,$checkOrder->plateform_type);
               // Notification::send($userInfo, new ActivityNotification($orderProcess));
                transactionHistory($orderProcess->id, $receiver_id, '8', ''.$sMessage.'', '3', '0', '0', '0', '1', '', $sender_id, 'order_processes', $orderProcess->id,$checkOrder->currency,NULL);
                return prepareResult(true, $sMessage, $orderProcess, $this->success);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function payRemainingOrderAmount(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'order_id' => 'required',
                                ],
                                [
                                    'order_id.required' => 'Order id field is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $checkOrder = Order::where('id', $request->order_id)->where('brand_id', $user->id)->first();
                if (!is_object($checkOrder)) {
                    return prepareResult(false, "Order id Not Found", [], $this->not_found);
                }
                
                $tax_per = 0;
                $camp_price = $checkOrder->camp_price;
                $tax_amount = 0;
                $total_pay = $camp_price ;

                $checkUserWallet = false;
                $remainUserwallet = $user->wallet_balance;
                $pay_amount = 0;
                $payfiftyPer = ($total_pay * 50) / 100;
                if ($remainUserwallet >= $payfiftyPer) {
                    $checkUserWallet = true;
                }
                $pay_amount = $payfiftyPer;

                $reMainingData = [
                    'userWallet' => $user->wallet_balance,
                    'pay_amount' => $pay_amount,
                    'remainingAmount' => $pay_amount - $user->wallet_balance,
                ];
                if ($checkUserWallet == false) {
                    return prepareResult(false, "Insufficient wallet balance, please recharge.", $reMainingData, '411');
                }

                $order = Order::find($request->order_id);
                $order->pay_amount = $pay_amount+$pay_amount;
                $order->payment_term = '0';
                $order->save();
                $orderRandId = \DB::table('order_processes')->select('order_id','orderRandId')->where('order_id',$request->order_id)->first();

                $transaction_id = Str::random(10);

                /* Transaction Histrory */
                $new_amount = $remainUserwallet - $pay_amount;
                transactionHistory($transaction_id, $user->id, '1', 'Order ID '.$orderRandId->orderRandId.' 100% payment', '2', $remainUserwallet, $pay_amount, $new_amount, '1', '', $order->influ_id, 'orders', $order->id,$order->currency,'0');

                /* update balance */
                balanceUpdate('1', '2', $user->id, $pay_amount);
                /* entry in message */
                message($order->id, $user->id, $checkOrder->influ_id, 'Congratulation! The remaining amount is paid successfully..', '0', '1',$checkOrder->channel_id,$checkOrder->plateform_type);

                $orderList = Order::where('id', $order->id)->first();

                return prepareResult(true, 'Amount Pay successfully', $orderList, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
