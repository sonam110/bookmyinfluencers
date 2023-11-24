<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\websiteSetting;

class websiteSettingSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        \DB::table('website_settings')->delete();
        $setting = new websiteSetting();
        $setting->company_name = 'Bookmyinfluencers';
        $setting->email = 'partners@bookmyinfluencers.com';
        $setting->phone = '123554658';
        $setting->company_logo = env('APP_URL') . '/' . 'logo.png';
        $setting->address = 'Accunite Solutions Pvt Ltd., 115, Tower 1, Assotech Business Cresterra,Sector 135';
        $setting->city = 'Noida';
        $setting->state = 'Uttar Pradesh';
        $setting->pincode = '201301';
        $setting->country = 'India';
        $setting->tax_per = '18';
        $setting->save();
    }

}
