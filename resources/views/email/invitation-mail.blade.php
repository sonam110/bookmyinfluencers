
<html>
<head>
  <title></title>
</head>
<body>
<p>Hi {{ @$content['user']->fullname }},</p>
<p>&quot;<strong>{{ @$content['campaign']->brand_name }}</strong>&quot; has invited your channel {{ @$content['channel_name'] }}  for a campaign &quot;<strong>{{ @$content['campaign']->camp_title }}</strong>&quot;. Please login to your account for reviewing the campaign budget and submitting your proposal.&nbsp;</p>

<p><a href="https://bookmyinfluencers.com">https://bookmyinfluencers.com</a></p>

<p><br />
This is an automated notification. Please do not reply directly to this message.</p>

<p><strong>Thank You</strong></p>

<p><strong>Team BookMyInfluencers</strong><br />
&nbsp;</p>
</body>
</html>


<!-- 
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

 
</style>
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff">
  <tr>
    <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#25d2db" style="padding-bottom: 30px;padding: 15px;">
          <tr>
            <td>
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#fff" style="padding: 15px 15px 0px 15px;">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#32a6c4" style="padding: 15px;background-color: #9f2109">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #fff; font-size: 18px;text-decoration: underline;"> INVITATION </center>
                            </div>
                          </td>
                        </tr>
                      </table>

                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px;;background-color: #fff;">
                        <tr>
                          <td >
                           <div >
                              <center ><img src="{{ env('AWS_ENDPOINT') }}/{{ env('AWS_BUCKET') }}/assets/bmiheader.png" width="115"></center>
                            </div>
                          </td>
                         
                        </tr>

                        <tr>
                          <td colspan="2" style="color: #000000;">
                            Dear {{ @$content['user']->fullname }},<br>
                            You have received invitation from brand {{ @$content['campaign']->brand_name }} .
                            <br>
                            <br>
                            Invitation campaign link below.
                          </td>
                        </tr>
                      </table>
                              <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px 15px 0px 15px; ;background-color: #fff;">
                                <tr>

                                  <td style="text-align: center;">
                                    <a href="{{ @$content['link'] }}" target="_blank"
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

                              <tr>
                                <td><p style="font-size: 16px; line-height: 22px; font-family: Georgia, Times New Roman, Times, serif; color: #333; margin: 0px; text-align: center;">
                                  <a href="{{ env('APP_URL') }}" target="_blank" style=" 
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
 -->