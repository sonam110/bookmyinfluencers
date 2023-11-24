<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Campaign;
use App\Models\User;
use DB;

class WebsiteSettingController extends Controller {

    public function categoryList() {
        $categoryList = Category::where('status', '1')->whereNull('is_parent')->get();
        if ($categoryList) {
            return prepareResult(true, 'category List', $categoryList, $this->success);
        } else {
            return prepareResult(true, 'No Category Found', [], $this->not_found);
        }
    }

    public function getCountry() {
        $countryList = DB::table('country')->select('id', 'name')->get();
        if ($countryList) {
            return prepareResult(true, 'Country List', $countryList, $this->success);
        } else {
            return prepareResult(true, 'No Country Found', [], $this->not_found);
        }
    }

    public function campaginDetail($uuid, $infuluuid) {
        $data = [];
        $data['influencer'] = User::where('uuid', $infuluuid)->first();
        $data['campaign'] = Campaign::where('uuid', $uuid)->first();
        if ($data) {
            return prepareResult(true, 'Detail', $data, $this->success);
        } else {
            return prepareResult(true, 'No data Found', [], $this->not_found);
        }
    }

    public function campaginApproved($uuid) {
        $campaign = Campaign::where('uuid', $uuid)->first();
        if (!empty($campaign)) {
            $status = ($campaign->invite_only == '1') ? '2' : '1';
            $campaign = Campaign::find($campaign->id);
            $campaign->status = $status;
            $campaign->save();
            return prepareResult(true, 'campaign', $campaign, $this->success);
        } else {
            return prepareResult(true, 'No campaign Found', [], $this->not_found);
        }
    }

}
