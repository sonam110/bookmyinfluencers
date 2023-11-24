<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscriptions;
use App\Models\SubscriptionTopup;
use App\Models\SubscriptionPlan;
use App\Models\websiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Validator;
use Auth;
use Exception;
use DB;
use AmrShawky\LaravelCurrency\Facade\Currency;

class SubscriptionsController extends Controller {

    public function getSubscription(Request $request) {
        $plan = SubscriptionPlan::orderby('id','ASC')->get();

        return prepareResult(true, "Plans", $plan, $this->success);
    }

    public function SubscribePay(Request $request) {
        try {
            $user = getUser();
            $validator = Validator::make($request->all(), ['plan_id' => 'required',],
                            ['plan_id.required' => 'Plan is required',]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $plan = SubscriptionPlan::where('id', $request->plan_id)
                    ->first();

            if ($plan->price <= 0) {
                $subscriptions = DB::table('subscriptions')
                        ->where('user_id', $user->id)
                        ->where('status', 1)
                        ->first();

                if (!empty($subscriptions) && $subscriptions->subscription_plan_id == $plan->id) {
                    return prepareResult(false, 'User already subscribed the same plan', [], $this->bad_request);
                } else {
                    $currentDate = now();
                    $dateOneYearAdded = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($currentDate)));
                    $subscriptions = Subscriptions::create([
                                'subscription_plan_id' => $plan->id,
                                'user_id' => $user->id,
                                'amount' => 0,
                                'subscribed_at' => $currentDate,
                                'expire_at' => $dateOneYearAdded,
                                'status' => 1,
                    ]);
                    $subResponse = [];
                    $subResponse['is_paid'] = false;
                    $subResponse['subscription'] = $subscriptions;
                    $subResponse['order_detail'] = false;

                    $update = DB::table('users')
                            ->where('id', $user->id)
                            ->update(['credit_balance' => $plan->credits]);
                    return prepareResult(true, 'Plan successfully activated', $subResponse, $this->success);
                }
            } else {
                $subscriptions = DB::table('subscriptions')
                        ->where('user_id', $user->id)
                        ->where('status', 1)
                        ->first();

                if (!empty($subscriptions) && $subscriptions->subscription_plan_id == $plan->id) {
                    return prepareResult(false, 'User already subscribed the same plan', [], $this->bad_request);
                } else {
                    /*-----if user purchased starter plan and then user want to upgrade in o business then plan price 49k in inr----*/
                    $currency = (!empty(auth()->user()->currency)) ? auth()->user()->currency:'INR';
                    $currentDate = now();
                    $planId = (!empty($subscriptions))  ? $subscriptions->subscription_plan_id :NULL;
                    if($request->plan_id =='3' && $planId == '2' ){
                        $plan_price =  changePrice('49000','INR') ;
                    } else{
                        $plan_price = $plan->price;
                    }
                    
                    if($currency=='INR'){
                        $websiteSetting = websiteSetting::first();
                        $tax_per = $websiteSetting->tax_per;
                        $tax_amount = ($plan_price * $tax_per) / 100;
                        $total_pay = round($plan_price + $tax_amount);

                    } else{
                        $tax_per = 0;
                        $tax_amount = 0;
                        $total_pay = round($plan_price);
                    }
                    $dateOneYearAdded = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($currentDate)));
                    $subscriptions = Subscriptions::create([
                                'subscription_plan_id' => $plan->id,
                                'user_id' => $user->id,
                                'amount' => $plan_price,
                                'tax' => $tax_per,
                                'tax_amount' => $tax_amount,
                                'total' => $total_pay,
                                'subscribed_at' => $currentDate,
                                'expire_at' => $dateOneYearAdded,
                                'status' => 2,
                    ]);

                    $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));
                    
                    $RzOrder = $api->order->create(
                            array(
                                'receipt' => 'SubsRcpt-' . $subscriptions->id,
                                'amount' => round($subscriptions->total) . '00',
                                'currency' => $currency,
                                'notes' => array(
                                    'plan_id' => $plan->id,
                                    'plan_name' => $plan->name,
                                    'plan_credits' => $plan->credits,
                                    'subscription_id' => $subscriptions->id,
                                    'user_id' => $user->id
                                )
                            )
                    );
                    $subscriptions->pg_order_id = $RzOrder['id'];
                    $subscriptions->save();

                    $order_detail = [
                        "order_id" => $RzOrder['id'],
                        "amount" => $RzOrder['amount'],
                        "currency" => $RzOrder['currency'],
                        "receipt" => $RzOrder['receipt'],
                        "status" => $RzOrder['status'],
                    ];
                    $subResponse = [];
                    $subResponse['is_paid'] = true;
                    $subResponse['subscription'] = $subscriptions;
                    $subResponse['order_detail'] = $order_detail;
                    return prepareResult(true, 'Subscribed and pending please redirect at pay gateway', $subResponse, $this->success);
                }
            }
        } catch (Exception $exception) {
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function SubscribePayResponse(Request $request) {
        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));
        try {
            $user = getUser();
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
                'razorpay_signature' => $request->signature,
            );
            $order = $api->utility->verifyPaymentSignature($attributes);
            $payemnt = $api->payment->fetch($request->payment_id);

            $subscriptions = Subscriptions::where('user_id', $user->id)
                    ->where('status', 1)
                    ->first();
            if (!empty($subscriptions)) {
                $subscriptions->status = 3;
                $subscriptions->save();
            }

            $Subs = Subscriptions::where('pg_order_id', $request->order_id)->first();
            $Subs->status = 1;
            $Subs->save();

            $plans = DB::table('subscription_plan')
                    ->where('id', $Subs->subscription_plan_id)
                    ->first();
            $plan_name = "(".$plans->name.")";

            transactionHistory($payemnt["id"], $user->id, '1', 'Plan subscription '.$plan_name.' ', '1', 0, $Subs->amount,'0', '1', '', $user->id, 'subscriptions', $Subs->id,$payemnt["currency"],'1');
            if ($payemnt["status"] == 'captured') {
                $yearlyCredit = $plans->credits*12;
                $updateCredit = User::find($user->id);
                $updateCredit->credit_balance =  $user->credit_balance + $yearlyCredit;
               // $updateCredit->last_topup_date = now()->addDays(30);
                $updateCredit->save();

                updateRelationshipMgr($Subs->subscription_plan_id,$user->id);

                transactionHistory($payemnt["id"] ,$user->id, '2', 'Credits '.$yearlyCredit.' updated ', '1', $user->credit_balance, $yearlyCredit, $updateCredit->credit_balance, '1', '', $user->id, 'users', $user->id,$user->currency,NULL);

            }
            return prepareResult(true, 'Your account has been upgraded successfully', $plans, $this->success);
        } catch (Exception $exception) {
            //dd($exception);
            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function TopupPay(Request $request) {
        try {
            $user = getUser();
            $validator = Validator::make($request->all(), ['currency' => 'required',],
                            ['amount.required' => 'Amount is required','credits.required' => 'credits is required',]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            
            $currency = (!empty($request->currency)) ? $request->currency : 'INR';
            if($currency =='INR'){
                $websiteSetting = websiteSetting::first();
                $tax_per = $websiteSetting->tax_per;
                $tax_amount = ($request->amount * $tax_per) / 100;
                $total_pay = $request->amount + $tax_amount;


            } else{
                $tax_per = 0;
                $tax_amount = 0;
                $total_pay = $request->amount;

            }

           
            $topup = SubscriptionTopup::create([
                        'subscription_plan_current_id' => '1',
                        'user_id' => $user->id,
                        'amount' => $request->amount,
                        'tax' => $tax_per,
                        'tax_amount' => $tax_amount,
                        'total' => $total_pay,
                        'additional_credits' => $request->credits,
                        'status' => 2,
            ]);
        
            $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));
            
            $RzOrder = $api->order->create(
                    array(
                        'receipt' => 'TopupRcpt-' . $user->id,
                        'amount' => $total_pay.'00',
                        'currency' => $currency,
                        'notes' => array(
                            'credits' => $request->credits,
                            'user_id' => $user->id,
                            'tax_per' => $tax_per,
                            'tax_amount' => $tax_amount,
                            'total_pay' => $total_pay,
                            'amount' => $request->amount,
                        )
                    )
            );

            $topup->pg_order_id = $RzOrder['id'];
            $topup->save();

            $order_detail = [
                "order_id" => $RzOrder['id'],
                "amount" => $RzOrder['amount'],
                "currency" => $RzOrder['currency'],
                "receipt" => $RzOrder['receipt'],
                "status" => $RzOrder['status'],
            ];
            $topupResponse = [];
            $topupResponse['is_paid'] = true;
            $topupResponse['topup'] = $topup;
            $topupResponse['order_detail'] = $order_detail;
            return prepareResult(true, 'Topup and pending please redirect at pay gateway', $topupResponse, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function TopupPayResponse(Request $request) {
        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));
        try {
            $user = getUser();
            $validator = Validator::make($request->all(), [
                        'order_id' => 'required',
                        'payment_id' => 'required',
                        'signature' => 'required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            /* -------Verify Signatutre --------------- */
            $attributes = array(
                'razorpay_order_id' => $request->order_id,
                'razorpay_payment_id' => $request->payment_id,
                'razorpay_signature' => $request->signature,
            );
            $order = $api->utility->verifyPaymentSignature($attributes);
            $payemnt = $api->payment->fetch($request->payment_id);

            $Subs = SubscriptionTopup::where('pg_order_id', $request->order_id)->first();
            $Subs->status = 1;
            $Subs->save();
            $transaction_id = \Str::random(8);
            transactionHistory($transaction_id, $user->id, '2', 'Topup credits', '1', $user->credit_balance, $Subs->additional_credits, $user->credit_balance + $Subs->additional_credits, $Subs->status, '', $user->id, 'subscription_topups', $Subs->id,$payemnt["currency"],NULL);
            transactionHistory($transaction_id, $user->id, '1', 'Paid '.$payemnt["currency"].' '.$Subs->total.' for credit purchase', '2','0', $Subs->amount,'0', '1', '', $user->id, 'subscription_topups', $Subs->id,$payemnt["currency"],'1');

            if ($payemnt["status"] == 'captured') {
                $update = DB::table('users')
                        ->where('id', $user->id)
                        ->update(['credit_balance' => $user->credit_balance + $Subs->additional_credits]);
            }
            return prepareResult(true, 'Pyament Successfull', $Subs, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
