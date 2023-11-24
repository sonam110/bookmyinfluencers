<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Validator;
use Auth;
use Exception;
use DB;

class ServiceController extends Controller {

    public function serviceManagment(Request $request) {

        try {
            $user = getUser();
            $payload = [];
            $checkService = Service::where('user_id', $user->id)->first();
            if (is_object($checkService)) {

                $payload = [
                    'user_id' => $checkService->user_id,
                    'is_affiliate_program' => ( $checkService->is_affiliate_program) ? $checkService->is_affiliate_program : 0,
                    'is_managment_service' => ( $checkService->is_managment_service) ? $checkService->is_managment_service : 0,
                ];
            } else {
                $payload = [
                    'user_id' => Null,
                    'is_affiliate_program' => 0,
                    'is_managment_service' => 0,
                ];
            }
            return prepareResult(true, 'Service Management', $payload, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function affiliateProgram(Request $request) {

        try {
            $validator = Validator::make($request->all(), [
                        'is_affiliate_program' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $user = getUser();
            $payload = [];
            $checkService = Service::where('user_id', $user->id)->first();
            if (is_object($checkService)) {

                $affiliateProgram = Service::find($checkService->id);
                $affiliateProgram->user_id = $user->id;
                $affiliateProgram->is_affiliate_program = $request->is_affiliate_program;
                $affiliateProgram->save();
            } else {
                $affiliateProgram = new Service;
                $affiliateProgram->user_id = $user->id;
                $affiliateProgram->is_affiliate_program = $request->is_affiliate_program;
                $affiliateProgram->save();
            }
            return prepareResult(true, 'Service Management', $affiliateProgram, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function managmentService(Request $request) {

        try {
            $validator = Validator::make($request->all(), [
                        'is_managment_service' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
            }
            $user = getUser();
            $payload = [];
            $checkService = Service::where('user_id', $user->id)->first();
            if (is_object($checkService)) {

                $managmentService = Service::find($checkService->id);
                $managmentService->user_id = $user->id;
                $managmentService->is_managment_service = $request->is_managment_service;
                $managmentService->save();
            } else {
                $managmentService = new Service;
                $managmentService->user_id = $user->id;
                $managmentService->is_managment_service = $request->is_managment_service;
                $managmentService->save();
            }
            return prepareResult(true, 'Service Management', $managmentService, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

}
