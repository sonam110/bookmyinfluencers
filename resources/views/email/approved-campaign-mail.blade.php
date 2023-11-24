
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
    border-top: 1px solid #428b9f;
    color: #000000!important;
  }

 /* .table tr:nth-child(even) {
    background-color: #2d4046;
    border-top: 1px solid #428b9f;
  }*/
</style>
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#000000">
  <tr>
    <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#25d2db" style="padding-bottom: 30px;padding: 15px;">
          <tr>
            <td>
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff" style="padding: 15px 15px 0px 15px;">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#32a6c4" style="padding: 15px;background-color: #9f2109;">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #fff; font-size: 18px;text-decoration: underline;">REQUEST FOR CAMPAIGN APPROVAL </center>
                            </div>
                          </td>
                        </tr>
                      </table>

                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px;background-color: white;">
                        <tr>
                          <td >
                            <div >
                              <center ><img src="{{ env('AWS_ENDPOINT') }}/{{ env('AWS_BUCKET') }}/assets/bmiheader.png" width="115"></center>
                            </div>
                          </td>
                          
                        </tr>
                        <tr>
                          <td colspan="2" style="color: #000000;">
                            Dear Admin,<br>
                            You have received request  from brand {{ @$content['brand_name'] }} for campaign approved .
                            <br>
                            <br>
                            Please click on the link for the approved campaign.
                          </td>
                        </tr>
                      </table>
                              <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px; background-color: white;">
                                <tr>

                                  <td style="text-align: center;">
                                    <a href="{{URL('/')}}/campaign-approved/{{ $content['uuid'] }}" target="_blank"
                                    style=" 
                                    color: blue;
                                    text-decoration: none;
                                    text-align: center;
                                    font-weight: 700;
                                    padding: 13px 42px;
                                    font-size: 11px;
                                    line-height: 6.5em;
                                    border-radius: 2px;
                                    color: #fff;
                                    background-color: #9f2109;"
                                    >
                                    Click here
                                  </a>
                                </td>
                              </tr>
                              <br></br>
                              <tr>
                                <td><p style="font-size: 16px; line-height: 22px; font-family: Georgia, Times New Roman, Times, serif; color: #fff; margin: 0px; text-align: center;">
                                  <a href="{{URL('/')}}" target="_blank" style=" 
                                  color: blue;
                                  text-decoration: none;
                                  text-align: center;
                                  font-weight: 700;
                                  padding: 13px 42px;
                                  font-size: 11px;
                                  line-height: 1.5em;
                                  border-radius: 2px;
                                  color: #fff;
                                  background-color: #9f2109;">
                                  Go To Website
                                </a></p></td>
                              </tr>
                            </table>
                            <br>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;">
                        <tr>
                          <td><center >Thank you for providing business.</center></td>
                        </tr>
                      </table>

                    </td>
                  </tr>

                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 5px; color: #000000;  text-align: center;border: 2px solid #9f2109;background-color: white;">
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
