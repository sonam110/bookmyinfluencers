
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

  .table th, .table td {
    text-align: left;
    padding: 8px;
    border-top: 1px solid #fff;
    color: #000000!important;
  }
.main-table{
    border: 2px solid #9f2109;
  }
 
</style>
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="white">
  <tr>
    <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#25d2db" style="padding-bottom: 30px;padding: 15px;">
          <tr>
            <td>
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="white" style="padding: 15px 15px 0px 15px;">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#32a6c4" style="padding: 15px;background-color: white;">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #000000; font-size: 18px;text-decoration: underline;"> Brand Whstapp Request </center>
                            </div>
                          </td>
                        </tr>
                      </table>

                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px;;background-color: white;">
                        <tr>
                           <td >
                           <div >
                              <center ><img src="{{ env('AWS_ENDPOINT') }}/{{ env('AWS_BUCKET') }}/assets/bmiheader.png" width="115"></center>
                            </div>
                          </td>
                          
                        </tr>

                        <tr>
                          <td colspan="2" style="color: #000000;">
                            Dear Account Manager,<br> 
                            New Request from brand {{ $content['user']->fullname }} for influencer whats app contact detail
                            <br>
                           
                          </td>
                        </tr>
                      </table>
                      </table>
                      <h4> Brand Detail</h4>
                      <table class="table main-table" cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;;background-color: white;">
                        <thead>
                        <tr>
                          <th></th>
                          <th></th>
                          <th></th>
                        </tr>
                      </thead>
                       
                       <tbody>
                          <tr>
                            <td class="desc" colspan="4">Brand Name</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['user']->fullname }} </td>
                          </tr>
                          <tr>
                            <td class="desc" colspan="4">Email</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['user']->email  }} </td>
                          </tr>
                          <tr>
                            <td class="desc" colspan="4">Phone</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['user']->phone  }} </td>
                          </tr>
                         
                           
                        </tbody>
                         <tfoot style="float:left;">
                       
                      </tfoot>
                      </table>
                      <br>
                      <h4> Channel Detail</h4>
                      <table class="table main-table" cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;;background-color: white;">
                        <thead>
                        <tr>
                          <th></th>
                          <th></th>
                          <th></th>
                        </tr>
                      </thead>
                       
                       <tbody>
                          <tr>
                            <td class="desc" colspan="4">Profile Name</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['channel_name'] }} </td>
                          </tr>
                          <tr>
                            <td class="desc" colspan="4">Link</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['channel_link']  }} </td>
                          </tr>
                         
                         
                           
                        </tbody>
                         <tfoot style="float:left;">
                       
                      </tfoot>
                      </table>
                    
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;">
                        <tr>
                          <td><center>Thank you for providing business.</center></td>
                        </tr>
                      </table>

                    </td>
                  </tr>

                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 5px; color: #000000; text-align: center;border: 2px solid #9f2109;background-color: white;">
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
