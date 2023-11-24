
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
    border-top: 1px solid #428b9f;
    color: #000000!important;
  }

  .table tr:nth-child(even) {
    background-color: #2d4046;
    border-top: 1px solid #428b9f;
  }
</style>
<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#000000">
  <tr>
    <td>
        <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#25d2db" style="padding-bottom: 30px;padding: 15px;">
          <tr>
            <td>
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="white" style="padding: 15px 15px 0px 15px;">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#32a6c4" style="padding: 15px;background-color: #9f2109;">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #fff; font-size: 18px;text-decoration: underline;"> CONTACT US </center>
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
                            New User try to contact us
                            <br>
                            <br>User Detail is  Given below
                           
                          </td>
                        </tr>
                      </table>

                      <table class="table" cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;background-color: white;">
                        <thead>
                        <tr>
                          <th>Full Name</th>
                          <th>Email</th>
                          <th>Apply As</th>
                          <th>Skye/Whatsapp</th>
                          <th>Message</th>
                          <th>Ip Address</th>
                        </tr>
                      </thead>
                       
                       <tbody>
                          <tr>
                            <td >{{  $content['contact']->fullname  }} </td>
                          
                    
                            <td >{{  $content['contact']->email  }} </td>
                         
                            
                            <td >{{  $content['contact']->who_are_you  }} </td>
                          
                            <td >{{ $content['contact']->skype_whats_app }} </td>
                          
                           
                            <td >{{ $content['contact']->message }} </td>
                         
                            
                            <td >{{ $content['contact']->ip_address }} </td>
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
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 5px; color: #000000;text-align: center;border: 2px solid #9f2109;background-color: white;">
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
