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
use Mail;
use App\Mail\MoneyAddeToWalletMail;
use App\Models\websiteSetting;
class PaymentController extends Controller {

    public function addMoneyTowalet(Request $request) {

        $api = new Api(config('app.razorpay_api_key'), config('app.razorpay_api_secret'));

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'amount' => 'required',
                                ],
                                [
                                    'amount.required' => 'Please enter amount',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                /* Create Customer--------------- */
                $currency = (!empty($request->currency)) ? $request->currency : 'INR';
                if($currency =='INR'){
                    $websiteSetting = websiteSetting::first();
                    $tax_per = $websiteSetting->tax_per;
                    $tax_amount = ($request->amount * $tax_per) / 100;
                    $total_pay = round($request->amount + $tax_amount);
                    $org_amount =  $request->amount;

                } else{
                    $tax_per = 0;
                    $tax_amount = 0;
                    $total_pay = round($request->amount);
                    $org_amount =  $request->amount;

                }
                

               /* if ($user->customer_id == '') {
                    $createCustomer = $api->customer->create(array('name' => $user->fullname, 'email' => $user->email, 'contact' => $user->phone, 'notes' => array('company_name' => $user->company_name, 'address' => $user->address)));
                    $updateCustid = User::find($user->id);
                    $updateCustid->customer_id = $createCustomer['id'];
                    $updateCustid->save();
                }*/
                /* Create Order---------------- */
                $receipt = 'recipt-' . Str::random(4);
                $order = $api->order->create(array('receipt' => $receipt, 'amount' => $total_pay . '00', 'currency' => $currency, 'notes' => array('tax_per' => $tax_per,'tax_amount' => $tax_amount, 'user_amount' =>$request->amount)));
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
                    $saveData->type = 'wallet';
                    $saveData->bal_type = 'credit';
                    $saveData->tax = $tax_per;
                    $saveData->tax_amount = $tax_amount;
                    $saveData->customer_id = $user->customer_id;
                    $saveData->order_id = $order['id'];
                    $saveData->receipt = $order['receipt'];
                    $saveData->email = $user->email;
                    $saveData->contact = $user->phone;
                    $saveData->currency = $order['currency'];
                    $saveData->amount = $org_amount;
                    $saveData->total = $total_pay;
                    $saveData->save();
                }
                return prepareResult(true, 'Amount added to wallet', $orderData, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function saveTransaction(Request $request) {

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
                    $data = [
                        "payment_id" => $payemnt["id"],
                        "entity" => $payemnt["entity"],
                        "currency" => $payemnt["currency"],
                        "old_amount" => $user->wallet_balance,
                        "amount" => $payment_order->amount,
                        "new_amount" => $user->wallet_balance + $payment_order->amount,
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
                    $content = [
                        "name" => @$user->fullname,
                        "pay_amount" => $payemnt["amount"] / 100,
                        "currency" => $payemnt["currency"],
                        "tax" => $dataInfo->tax,
                        "tax_amount" => $dataInfo->tax_amount,
                        "amount" => $dataInfo->amount,
                    ];
                    if (env('IS_MAIL_ENABLE', true) == true) {
                        Mail::to(@$user->email)->send(new MoneyAddeToWalletMail($content));
                    }
                    transactionHistory($payemnt["id"], $user->id, '1', 'Added Money to wallet', '1', $dataInfo->old_amount, $dataInfo->amount, $dataInfo->new_amount,'1', '', $user->id, 'payment_histories', $dataInfo->id,$payemnt["currency"],'1');
                    if ($payemnt["status"] == 'captured') {
                        balanceUpdate('1', '1', $user->id, $dataInfo->amount);
                    }
                    //$this->savePayamentData($data);
                    return prepareResult(true, 'Payment Successful, Amount added successfully.', $dataInfo, $this->success);
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

}
