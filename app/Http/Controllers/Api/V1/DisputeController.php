<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Validator;
use Auth;
use Except;
use DB;
use Notification;
use App\Notifications\ActivityNotification;
use Mail;
use App\Mail\DisputeMail;
class DisputeController extends Controller {

    public function raiseDispute(Request $request) {

        try {
            $user = getUser();
            $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'reason' => 'required',
                ],
                [
                    'order_id.required' => 'Order id is required',
                    'reason.required' => 'Order id is required',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $checkOrder = Order::where('id', $request->order_id)->with('infInfo','brandInfo','orderProcess','channelInfo','campInfo');
            if ($user->roles[0]->name == 'influencer') {
                $checkOrder = $checkOrder->where('influ_id',$user->id)->first();
            } else{
                $checkOrder = $checkOrder->where('brand_id',$user->id)->first();
            }
            
            if (!is_object($checkOrder)) {
                return prepareResult(false, "Order not found", [], $this->unprocessableEntity);
            }
           
            $dispute = new Dispute;
            $dispute->user_id = $user->id;
            $dispute->order_id = $request->order_id;
            $dispute->reason = $request->reason;
            $dispute->reason1 = $request->reason1;
            $dispute->save();
            $disputeList = Dispute::where('id', $dispute->id)->with('order','orderProcess')->first();
            
            if ($user->roles[0]->name == 'influencer') {
                $name = $checkOrder->channelInfo->channel_email;
                $toemail = $checkOrder->brandInfo->email;
                $to_name = $checkOrder->brandInfo->fullname;
            } else{
                $name = @$checkOrder->campInfo->brand_name;
                $toemail = $checkOrder->infInfo->email;
                $to_name = $checkOrder->infInfo->fullname;
            }
            $content = [
                "name" => $name,
                "order_id" => $checkOrder->orderProcess->orderRandId,
                "to_name" => $to_name,
                "campaign" => @$checkOrder->campInfo->camp_title,
                "issue" => $request->reason,

            ];
            
            if (env('IS_MAIL_ENABLE', true) == true) {
                Mail::to($toemail)->send(new DisputeMail($content));
            }

            return prepareResult(true, 'Dispute raised', $disputeList, $this->success);
            
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function disputeList(Request $request) {

        try {
            $user = getUser();
            $query = Dispute::where('status', '!=', '');
            if ($request->status!='') {
                $query->where('status',''.$request->status.'');
            
            }
            if ($user->roles[0]->name == 'brand') {
                $query = $query->where('brand_id', $user->id)->with('infInfo:id,fullname','order','orderProcess');
               
            }
            if ($user->roles[0]->name == 'influencer') {
                $query = $query->where('influ_id', $user->id)->with('brandInfo:id,fullname','order','orderProcess');
                
            }
            if(!empty($request->perPage))
            {
                $perPage = $request->perPage;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination =  [
                    'dispute_list' => $result,
                    'totalCount' => $total,
                    'total' => $total,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($total / $perPage),
                ];
                $query = $pagination;
            }
            else
            {
                $query =  [
                    'dispute_list' => $query->get(),
                    'totalCount' => $query->count(),
                ];
            }

            return prepareResult(true, "Dispute list", $query, $this->success);
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

}
