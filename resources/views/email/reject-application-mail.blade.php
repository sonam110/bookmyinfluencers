
<style type="text/css">
  @import url('https://fonts.googleapis.com/css?family=Roboto+Condensed&display=swap');
  body {
    font-family: 'Roboto Condensed', sans-serif;
  }
  .table table {
    border-collapse: collapse;
    width: 100%;
  }
  .table th {
    font-weight: bold;
  }
   .main-table{
    border: 2px solid #9f2109;
  }


  .table th, .table td {
    text-align: left;
    padding: 8px;
    border-top: 1px solid #fff;
    color: #000000!important;
  }

 
</style>
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff">
  <tr>
    <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#25d2db" style="padding-bottom: 30px;padding: 15px;">
          <tr>
            <td>
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 15px 15px 0px 15px;background-color: #fff;"">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#32a6c4" style="padding: 15px;background-color: #9f2109;">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #fff; font-size: 18px;text-decoration: underline;"> APPLICATION REJECTED </center>
                            </div>
                          </td>
                        </tr>
                      </table>

                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px;background-color: #fff;"">
                        <tr>
                          <td >
                           <div >
                              <center ><img src="{{ env('AWS_ENDPOINT') }}/{{ env('AWS_BUCKET') }}/assets/bmiheader.png" width="115"></center>
                            </div>
                          </td>
                          
                        </tr>

                        <tr>
                          <td colspan="2" style="color: #000000;">
                            Dear {{ $content['proposal']->infInfo->fullname }},<br>
                            Your application for campaign {{ $content['proposal']->campInfo->camp_title }} rejected by brand {{ $content['proposal']->campInfo->brand_name }} .
                            <br>
                            <br>
                            You can see your sent data below.
                          </td>
                        </tr>
                      </table>

                      <table class="table main-table" cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;">
                        <thead>
                        <tr>
                          <th></th>
                          <th></th>
                          <th></th>
                        </tr>
                      </thead>
                       
                       <tbody>
                          <tr>
                            <td class="no">Applying as</td>
                            <td class="no"></td>
                            <td class="desc">{{ ($content['proposal']->channel) ? $content['proposal']->channelInfo->channel_name :'' }} </td>
                          </tr>
                          <tr>
                            <td class="no">Bid Amount</td>
                            <td class="no"></td>
                            <td class="desc">{{ $content['proposal']->currency }} {{ $content['proposal']->price }} </td>
                          </tr>
                          <tr>
                            <td class="no">Brand would prefer the integration to be for 60-90 seconds</td>
                             <td class="no"></td>
                            <td class="desc">{{ ($content['proposal']->old_duration) ? 'AGREE' :'NO' }}</td>
                          </tr>
                           <tr>
                            <td class="no">Brand prefers the promotion to start 30 seconds reels campaign of the video</td>
                             <td class="no"></td>
                            <td class="desc">{{ ($content['proposal']->promotion_slot) ? 'AGREE' :'NO' }}</td>
                          </tr>
                           <tr>
                            <td class="no">Can you commit any minimum video views for this campaign? </td>
                             <td class="no"></td>
                             <td class="no">{{ ($content['proposal']->view_commitment) ? 'YES' :'NO' }}</td>
                            <tr>
                            <td class="desc">Are you flexible with minor changes after the video is submitted for review? </td>
                             <td class="no"></td>
                             <td class="no">{{ ($content['proposal']->minor_changes) ? 'YES' :'NO' }}</td>
                          </tr>
                          </tr>
                           <tr>
                            <td class="no">How soon can you deliver, after the order is confirmed by the brand?  </td>
                             <td class="no"></td>
                            <td class="desc">{{ ($content['proposal']->delivery_days == 'other') ? $content['proposal']->other_delivery_days : $content['proposal']->delivery_days }}</td>
                          </tr>
                           <tr>
                            <td class="no">Would you share the video on any of your other social media handles. </td>
                             <td class="no"></td>
                            <td class="desc">{{ ($content['proposal']->social_media_share) ? 'YES' :'NO' }} {{ ($content['proposal']->social_media) }} </td>
                          </tr>
                           <tr>
                            <td class="no">Additional comments for the Brand </td>
                             <td class="no"></td>
                            <td class="desc"> {!! $content['proposal']->comment !!}</td>
                          </tr>
                        </tbody>
                      </table>
                      <br>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;">
                        <tr>
                          <td><center>Thank you for providing business.</center></td>
                        </tr>
                      </table>

                    </td>
                  </tr>

                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 5px; color: #000000;  text-align: center;border: 2px solid #9f2109;background-color: #fff;">
                        <tr>
                          <td>
                            {!! $websiteSetting->company_name !!},
                            <br>
                            {!! $websiteSetting->address !!}, {!! $websiteSetting->city !!}, {!! $websiteSetting->pincode !!}
                            <br>
                            {!! $websiteSetting->email !!}
                            <br>
                            {!! $websiteSetting->phone !!}
                          </td>
                        </tr>
                      </table>
                      <br>
                    </td>
                  </tr>
                </table>
            </td>
          </tr>
        </table>
    </td>
  </tr>
</table>
