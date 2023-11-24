<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CreateList;
use App\Models\listing;
use Validator;
use Auth;
use Exception;
use DB;
use App\Models\Revealed;
class ListController extends Controller {

    public function createList(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'name' => 'required',
                            'plateform_type' => 'required:in:1,2',
                                ],
                                [
                                    'name.required' => 'name  is required',
                                    'plateform_type.required' => 'Platform  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $checkAlready = CreateList::where('brand_id', $user->id)->where('name', $request->name)->first();
                if (is_object($checkAlready)) {
                    return prepareResult(false, "Please select a different name.", [], $this->unprocessableEntity);
                }
                $list = new CreateList;
                $list->brand_id = $user->id;
                $list->name = $request->name;
                $list->plateform_type = $request->plateform_type;
                $list->save();
                $listed = CreateList::where('id', $list->id)->first();
                return prepareResult(true, 'Created '.$request->name.'  successfully', $listed, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function editList(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                            'name' => 'required',
                                ],
                                [
                                    'name.required' => 'Name  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->id;
                $checkList = CreateList::where('brand_id', $user->id)->where('id', $id)->first();

                if (!is_object($checkList)) {
                    return prepareResult(false, "Id Not Found", [], $this->not_found);
                }
                $checkAlready = CreateList::where('brand_id', $user->id)->where('name', $request->name)->where('id', '!=', $id)->first();
                if (is_object($checkAlready)) {
                    return prepareResult(false, "Please select a different name.", [], $this->unprocessableEntity);
                }


                $list = CreateList::find($id);
                $list->brand_id = $user->id;
                $list->name = $request->name;
                $list->save();
                $listed = CreateList::where('id', $list->id)->first();
                return prepareResult(true, 'list Updated successfully', $listed, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function deleteList(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->id;

                $checkList = CreateList::where('brand_id', $user->id)->where('id', $id)->first();
                if (!is_object($checkList)) {
                    return prepareResult(false, "Id Not Found", [], $this->not_found);
                }
               
                $list = CreateList::where('id', $id)->delete();
                $deleteListing = listing::where('brand_id', $user->id)->where('list_id',$id)->delete();
                return prepareResult(true, 'List deleted successfully', [], $this->success);
                
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }
    public function viewList(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $id = $request->id;

                $checkList = CreateList::where('brand_id', $user->id)->where('id', $id)->first();
                if (!is_object($checkList)) {
                    return prepareResult(false, "Id Not Found", [], $this->not_found);
                }
               
                $query = listing::where('list_id',$id)->where('brand_id',$user->id);
                $totalCount = $query->count();
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;  
                $listData = []; 
                if(!empty($perPage))
                {
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $listInfo = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $listInfo = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }
                $listData['channelList'] = $listInfo;
                $listData['totalCount'] = $totalCount;
                $listData['total'] = count($listInfo);
                $listData['totalCount'] = $totalCount;
                $listData['current_page'] = (!empty($request->page)) ? $request->page : 1;
                $listData['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
                $listData['last_page'] = $last_page;

                return prepareResult(true, 'List View',$listData, $this->success);
                
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), $exception->getMessage(), $this->internal_server_error);
        }
    }

    public function manageList(Request $request) {
        try {
            $user = getUser();
            
            $brand_list['list'] = [];
            if ($user->roles[0]->name == 'brand') {
                $query = CreateList::where('brand_id', $user->id)
                                ->where('status', '1')->orderby('id','DESC');
                if($request->plateform_type !=''){
                    $query ->where('plateform_type',$request->plateform_type);
                }

                $query = $query->orderby('id', 'DESC');
                $totalCount = $query->count();
                $perPage = (!empty($request->perPage)) ? $request->perPage:10 ;   
                if(!empty($perPage))
                {
                    $perPage = $perPage;
                    $page = $request->input('page', 1);
                    $total = $query->count();
                    $listing = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();
                    $last_page = ceil($total / $perPage);
                   
                   
                }
                else
                {
                    $listing = $query->get();
                    $total = $query->count();
                    $last_page = ceil($total / 10);
                }

                foreach ($listing as $key => $list) {
                    $listInfo = listing::where('list_id',$list->id)->where('brand_id',$user->id)->where('channel_id',@$request->channel_id)->where('status', '1')->where('plateform_type',$list->plateform_type)->first();
                    $listCount= listing::where('list_id',$list->id)->where('brand_id',$user->id)->where('plateform_type',$list->plateform_type)->count();
                    $brand_list['list'][] = [
                        "id" => $list->id,
                        "name" => $list->name,
                        "is_in_list" => (!empty($listInfo)) ? true:false,
                        "plateform_type" => $list->plateform_type,
                        "listCount" => $listCount,
                    ];
                }
                $brand_list['total'] = count($listing);
                $brand_list['totalCount'] = $totalCount;
                $brand_list['current_page'] = (!empty($request->page)) ? $request->page : 1;
                $brand_list['per_page'] = (!empty($request->perPage)) ? $request->perPage : 10;
                $brand_list['last_page'] = $last_page;

                return prepareResult(true, "Listing", $brand_list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

//    public function getListChannels(){
//        
//    }
    public function addTolist(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'list_id' => 'required',
                            'channel_id' => 'required',
                                ],
                                [
                                    'list_id.required' => 'List id  is required',
                                    'channel_id.required' => 'Channel id  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $ids = [];
                $checkList = CreateList::where('brand_id', $user->id)->where('id', $request->list_id)->first();
                if (!is_object($checkList)) {
                    return prepareResult(false, "List Not Found", [], $this->not_found);
                }
                
                $list = listing::where('brand_id', $user->id)->where('list_id',$request->list_id)->where('channel_id', $request->channel_id)->first();
                if (!$list) {
                    $list = new listing;
                }
                $list->brand_id = $user->id;
                $list->list_id = $request->list_id;
                $list->channel_id = $request->channel_id;
                $list->plateform_type = $request->plateform_type;
                $list->status = '1';
                $list->save();

                $transaction_id = \Str::random(10);
                $influInfo = \DB::table('channel_association')->where('internal_channel_id',$request->channel_id)->where('plateform_type',$request->plateform_type)->where('is_verified','1')->where('influ_id','!=','2')->first();

                $influ_id = (!empty($influInfo)) ? $influInfo->influ_id:'2';
                
                transactionHistory($transaction_id, $influ_id, '13', 'Added to '.$checkList->name.'', '3', '0',0,0, '1', '', $user->id, 'listings', $list->id,$user->currency,NULL);
               

                
                return prepareResult(true, ' Added to '.$checkList->name.'', $list, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function removeTolist(Request $request) {

        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $validator = Validator::make($request->all(), [
                            'id' => 'required',
                            'channel_id' => 'required',
                                ],
                                [
                                    'id.required' => 'Id  is required',
                                    'channel_id.required' => 'Channel id  is required',
                ]);
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $checkAlready = Listing::where('brand_id', $user->id)->where('id', $request->id)->first();
                if (!is_object($checkAlready)) {
                    return prepareResult(false, "Id not found", [], $this->unprocessableEntity);
                }
                $list = listing::find($request->id);
                $list->status = '0';
                $list->save();
                $listed = listing::where('id', $list->id)->first();
                return prepareResult(true, ' Remove successfully', $listed, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
