<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use Exception;
use App\Models\User;
use App\Models\Channel;
use App\Models\ChannelAssociation;

class ProfileSettingController extends Controller {

    public function ProfileSetting(Request $request) {
        try {
            $user = auth()->user();
            if ($request->email) {
                $validator = Validator::make($request->all(), [
                            'email' => 'email|unique:users,email,' . $user->id,
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
            }
            if ($request->phone) {
                $validator = Validator::make($request->all(), [
                            'phone' => 'required|min:10|max:11',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
            }
            if ($request->whats_app) {
                $validator = Validator::make($request->all(), [
                            'whats_app' => 'required|min:10|max:15',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
            }
            if($request->gst) {
                $validator = Validator::make($request->all(), [
                            'pan_no' => 'required',
                            'company_name' => 'required',
                            'company_address' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
            }
            $user = User::find($user->id);
            $user->fullname = ($request->fullname) ? $request->fullname : $user->fullname;
            $user->email = ($request->email) ? $request->email : $user->email;
            $user->phone = ($request->phone) ? $request->phone : $user->phone;
            $user->country_id = ($request->country_id) ? $request->country_id : $user->country_id;
            $user->bank_name = ($request->bank_name) ? $request->bank_name : $user->bank_name;
            $user->account_holder = ($request->account_holder) ? $request->account_holder : $user->account_holder;
            $user->account_number = ($request->account_number) ? $request->account_number : $user->account_number;
            $user->ifsc_code = ($request->ifsc_code) ? $request->ifsc_code : $user->ifsc_code;
            $user->upi_id = ($request->upi_id) ? $request->upi_id : $user->upi_id;
            $user->pan_no = ($request->pan_no) ? $request->pan_no : $user->pan_no;
            $user->skype = ($request->skype) ? $request->skype : $user->skype;
            $user->whats_app = ($request->whats_app) ? $request->whats_app : $user->whats_app;
            $user->whats_app_notification = ($request->whats_app_notification) ? $request->whats_app_notification : 0;
            $user->detail_in_exchange = ($request->detail_in_exchange) ? $request->detail_in_exchange : 0;
            $user->company_name = ($request->company_name) ? $request->company_name : $user->company_name;
            $user->company_address = ($request->company_address) ? $request->company_address : $user->company_address;
            $user->currency = ($request->currency) ? $request->currency : $user->currency;
            $user->gst = ($request->gst) ? $request->gst : $user->gst;
            $user->save();
            return prepareResult(true, "Profile updated successfully..", $user, $this->success);
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function updateProfile(Request $request) {
        try {
            $user = auth()->user();
            if ($user->roles[0]->name == 'influencer') {
                $validator = Validator::make($request->all(), [
                            'fullname' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                if ($request->email) {
                    $validator = Validator::make($request->all(), [
                                'email' => 'required|email|unique:users,email,' . $user->id,
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }
                if ($request->phone) {
                    $validator = Validator::make($request->all(), [
                                'phone' => 'required|min:10|max:11',
                    ]);
                    if ($validator->fails()) {
                        return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                    }
                }

                $updateuser = User::find($user->id);
                $updateuser->fullname = ($request->fullname) ? $request->fullname : $user->fullname;
                $updateuser->email = ($request->email) ? $request->email : $user->email;
                $updateuser->phone = ($request->phone) ? $request->phone : $user->phone;
                $updateuser->save();
                if ($updateuser) {
                    $is_login_firsttime = User::where('id', $user->id)->update(['is_login_first_time' => '0']);
                }
                if (!empty($request->channel_name)) {
                    $default_channel = Channel::join('channel_association', 'channels.id', '=', 'channel_association.internal_channel_id')->where('channel_association.influ_id', $user->id)->where('channel_association.is_default', '1')->first();
                    if (!empty($default_channel)) {
                        $channel = Channel::find($default_channel->internal_channel_id);
                        $channel->channel_name = $request->channel_name;
                        $channel->channel_lang = $request->channel_lang;
//                        $channel->promotion_price = $request->promotion_price;
                        $channel->save();

                        $ChannelAssociation = ChannelAssociation::find($default_channel->id);
                        $ChannelAssociation->promotion_price = $request->promotion_price;
                        $ChannelAssociation->save();
                    }
                }

                return prepareResult(true, "Updated successfully.", $user, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function updateCategoryPreference(Request $request) {
        try {
            $user = auth()->user();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'category_preferences' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $updateuser = User::find($user->id);
                $updateuser->category_preferences = (!empty($request->category_preferences)) ? $request->category_preferences : $user->category_preferences;
                $updateuser->save();
                if ($updateuser) {
                    $is_login_firsttime = User::where('id', $user->id)->update(['is_login_first_time' => '0']);
                }
                return prepareResult(true, "Preferences saved successfully!", $user, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", $exception->getMessage(), $this->internal_server_error);
        }
    }
    public function dontShowMeAgain(Request $request) {
        try {
            $user = auth()->user();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'dont_show_me_again' => 'required|in:0,1',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $updateuser = User::find($user->id);
                $updateuser->dont_show_me_again = ($request->dont_show_me_again=='1') ? '1' : '0';
                $updateuser->save();
                
                return prepareResult(true, "Updated successfully.", $updateuser, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, "Oops!!!, something went wrong, please try again.", $exception->getMessage(), $this->internal_server_error);
        }
    }

}
