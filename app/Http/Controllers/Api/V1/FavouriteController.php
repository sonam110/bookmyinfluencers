<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Favourite;
use Validator;
use Exception;
use DB;

class FavouriteController extends Controller {

    public function like(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                if($request->plateform_type =='2'){
                    $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:instagram_data,id'],
                            ['channel_id.required' => 'Channel ID is required',]);
                } else{
                    $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:channels,id'],
                                ['channel_id.required' => 'Channel ID is required',]);
                }

               
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }

                $favourites = Favourite::where('channel_id', $request->channel_id)->where('user_id', $user->id)->where('plateform_type', $request->plateform_type)->first();
                if (empty($favourites)) {
                    $favourites = new Favourite();
                    $favourites->user_id = $user->id;
                    $favourites->channel_id = $request->channel_id;
                    $favourites->plateform_type = $request->plateform_type;
                }
                $favourites->status = 1;
                $favourites->save();
                return prepareResult(true, 'This channel has been marked as a favorite', $favourites, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function unlike(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                if($request->plateform_type =='2'){
                    $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:instagram_data,id'],
                            ['channel_id.required' => 'Channel ID is required',]);
                } else{
                    $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:channels,id'],
                                ['channel_id.required' => 'Channel ID is required',]);
                }
                
                if ($validator->fails()) {
                    return prepareResult(false, $validator->errors()->first(), [], $this->unprocessableEntity);
                }
                $favourites = \DB::table('favourites')->where('channel_id', $request->channel_id)->where('user_id', $user->id)->where('plateform_type', $request->plateform_type)->first();
                if (!empty($favourites)) {
                    $deleteFav = Favourite::where('id',$favourites->id)->delete();
                    
                } else {
                     return prepareResult(false, "Channel not found in your favorite list", [], $this->not_found);
                }
                return prepareResult(true, 'This channel was unfavorited.',[], $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

    public function liked(Request $request) {
        try {
            $user = getUser();
            if ($user->roles[0]->name == 'brand') {
                $channels = DB::table('favourites')
                        ->rightJoin('channels', 'favourites.channel_id', '=', 'channels.id')
                        ->where('favourites.status', '=', 1)
                        ->where('favourites.user_id', $user->id)
                        ->orderBy('favourites.created_at', 'desc')
                        ->get();
                return prepareResult(true, 'Liked Channels', $channels, $this->success);
            } else {
                return prepareResult(false, "Unauthorized User", [], $this->unauthorized);
            }
        } catch (Exception $exception) {

            return prepareResult(false, $exception->getMessage(), [], $this->internal_server_error);
        }
    }

}
