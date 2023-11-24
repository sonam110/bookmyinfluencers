<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\TransactionHistory;
use App\Models\Message;
use Auth;
use Validator;
use Exception;

class NotificationController extends Controller {

    public function notifications() {
        try {
            $user = getUser();
            $transactionHistory = TransactionHistory::select('id', 'message', 'created_at', 'type','user_id')->WhereIn('type', ['5', '6', '7', '8'])->where('user_id', $user->id)->groupBy('id')->orderBy('created_at','DESC')->get();
            //$readAll = TransactionHistory::where('user_id',$user->id)->update(['status'=>'0']);
            return prepareResult(true, "Notifications", $transactionHistory, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }

    public function readAll() {
        try {
            $user = getUser();

            $readAll = TransactionHistory::where('user_id', $user->id)->update(['status' => '0']);
            return prepareResult(true, "Read All", [], $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", [], $this->internal_server_error);
        }
    }
    public function readUserMessage(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                ],
                [
                'user_id.required' => 'User id field is required',
            ]);
            $user = getUser();
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $checkUser = User::where('id', $request->user_id)->where('status', '1')->first();
            if (!is_object($checkUser)) {
                return prepareResult(false, "User Not Found", [], $this->not_found);
            }
            $query = Message::where('sender_id', $user->id)
                    ->where('receiver_id', $request->user_id)
                    ->update(['is_read' => '1']);

            return prepareResult(true, "Read All", [], $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
