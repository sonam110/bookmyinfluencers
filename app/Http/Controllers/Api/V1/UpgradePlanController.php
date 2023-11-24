<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Subscriptions;
use Validator;
use Auth;
use Exception;
use DB;

class UpgradePlanController extends Controller {

    public function upgradePlan(Request $request) {
        try {
            $user = getUser();
            $validator = Validator::make($request->all(), ['plan_id' => 'required',],
                            ['plan_id.required' => 'Plan is required',]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $plan = DB::table('subscription_plan')
                    ->where('id', $request->plan_id)
                    ->where('status', '1')
                    ->first();
            if (!is_object($plan)) {
                return prepareResult(false, "Plan not found", [], $this->not_found);
            }
            $subscriptions = DB::table('subscriptions')
                    ->where('user_id', $user->id)
                    ->first();
            if (!is_object($subscriptions)) {
                return prepareResult(false, "User Subscriptions not found", [], $this->not_found);
            }
            $currentDate = now();
            $dateOneYearAdded = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($currentDate)));

            $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));
            $currency = (!empty($request->currency)) ? $request->currency : 'INR';

            $RzOrder = $api->order->create(
                    array(
                        'receipt' => 'SubsRcpt-' . $subscriptions->id,
                        'amount' => round($plan->price) . '00',
                        'currency' => $currency,
                        'notes' => array(
                            'old_plan_id' => $subscriptions->subscription_plan_id,
                            'plan_id' => $plan->id,
                            'plan_name' => $plan->name,
                            'plan_credits' => $plan->credits,
                            'subscription_id' => $subscriptions->id,
                            'user_id' => $user->id
                        )
                    )
            );

            $upgradeSubscriptions = Subscriptions::find($subscriptions->id);
            $upgradeSubscriptions->pg_order_id = $RzOrder['id'];
            $upgradeSubscriptions->save();

            $order_detail = [
                "order_id" => $RzOrder['id'],
                "amount" => $RzOrder['amount'],
                "currency" => $RzOrder['currency'],
                "receipt" => $RzOrder['receipt'],
                "status" => $RzOrder['status'],
            ];

            $subResponse = [];
            $subResponse['is_paid'] = true;
            $subResponse['plan'] = $plan;
            $subResponse['order_detail'] = $order_detail;
            return prepareResult(true, 'Subscribed and pending please redirect at payment gateway', $subResponse, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function upgradeResponse(Request $request) {
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

            if ($payemnt["status"] == 'captured') {
                $currentDate = now();
                $dateOneYearAdded = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($currentDate)));

                $plan_id = $payemnt['notes']['plan_id'];
                $old_plan_id = $payemnt['notes']['old_plan_id'];
                $old_plan_detail = DB::table('subscription_plan')
                        ->where('id', $old_plan_id)
                        ->first();
                $Subs = Subscriptions::where('pg_order_id', $request->order_id)->first();
                $plans = DB::table('subscription_plan')
                        ->where('id', $plan_id)
                        ->first();

                $plan_credits = ($old_plan_detail->credit_rollover == '1') ? $user->credit_balance + $plans->credits : $plans->credits;

                $upgradeSubscriptions = Subscriptions::find($Subs->id);
                $upgradeSubscriptions->subscription_plan_id = $plans->id;
                $upgradeSubscriptions->user_id = $user->id;
                $upgradeSubscriptions->amount = $plans->price;
                $upgradeSubscriptions->subscribed_at = $currentDate;
                $upgradeSubscriptions->expire_at = $dateOneYearAdded;
                $upgradeSubscriptions->status = '1';
                $upgradeSubscriptions->save();

                /*----------Credit Transaction-------*/

                transactionHistory($payemnt["id"], $user->id, '2', 'Plan Upgrade', '1', 0, $plan_credits, $plan_credits, '1', 'Plan Upgrade Successfully', $user->id, 'Subscriptions', $Subs->id,$payemnt["currency"],NULL);
                /*--------Transaction History----------*/
                transactionHistory($payemnt["id"], $user->id, '1', 'Plan Upgrade', '2', 0, $plans->price, 0, '1', 'Plan Upgrade Successfully', $user->id, 'Subscriptions', $Subs->id,$payemnt["currency"],'1');

                $update = DB::table('users')
                        ->where('id', $user->id)
                        ->update(['credit_balance' => $plan_credits]);
                /*-----Business Plan 9000 credits cashback*/
                if($plan_id == '3'){
                    $cashback_crerdits = $user->credit_balance + env('CREDITS_CASHBACK','9000');
                    $cashback = DB::table('users')
                        ->where('id', $user->id)
                        ->update(['credit_balance' => $cashback_crerdits]);

                    transactionHistory($payemnt["id"], $user->id, '2', 'Credit Cashback', '1', 0, $cashback_crerdits, $cashback_crerdits, '1', 'CashBack Credits', $user->id, 'Subscriptions', $Subs->id,$payemnt["currency"],NULL);

                }

                return prepareResult(true, 'Pyament Successfull', $plans, $this->success);
            } else {

                return prepareResult(false, "Opps!Something went wrong", [], $this->internal_server_error);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
