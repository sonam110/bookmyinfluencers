
<style type="text/css">
  @import url('https://fonts.googleapis.com/css?family=Roboto+Condensed&display=swap');
  body {
    font-family: 'Roboto Condensed', sans-serif;
  }
  .table table {
    border-collapse: collapse;
    width: 100%;
  }
  .main-table{
    border: 2px solid #9f2109;
  }
  .table th {
    font-weight: bold;
  }

  .table th, .table td {
    text-align: left;
    padding: 8px;
    color: #000000!important;
  }

  /*.table tr:nth-child(even) {
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
                <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#262626" style="padding: 15px 15px 0px 15px; background-color: white;">
                  <tr>
                    <td>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#32a6c4" style="padding: 15px;background-color: #9f2109;">
                        <tr>
                          <td>
                            <div>
                              <center style="color: #fff; font-size: 18px;text-decoration: underline;"> CANCEL ORDER </center>
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
                            Dear User,<br>
                            Your Order is cancelled .
                            <br>
                            <br>Order Detail Given below
                           
                          </td>
                        </tr>
                      </table>

                      <table class="table main-table" cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000; ;">
                        <thead>
                        <tr>
                          <th></th>
                          <th></th>
                          <th></th>
                        </tr>
                      </thead>
                       
                       <tbody>
                          <tr>
                            <td class="desc" colspan="4">Campaign Title</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['order']->campInfo->camp_title  }} </td>
                          </tr>
                          <tr>
                            <td class="desc" colspan="4">Campaign price</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['order']->camp_price  }} </td>
                          </tr>
                          <tr>
                            <td class="desc" colspan="4">Deadline</td>
                            <td class="no" colspan="4"></td>
                            <td class="desc" colspan="4">{{  $content['order']->deadlineDate  }} </td>
                          </tr>
                          <tr>
                            <td class="desc" colspan="4">Applying as</td>
                            <td class="no"colspan="4"></td>
                            <td class="desc"colspan="4">{{ ($content['order']->channel) ? $content['order']->channelInfo->channel_name :'' }} </td>
                          </tr>
                          <tr>
                            <td class="desc"colspan="4">Bid Amount</td>
                            <td class="no"colspan="4"></td>
                            <td class="desc"colspan="4">{{ $content['order']->currency }} {{ $content['order']->appInfo->price }} </td>
                          </tr>
                          <tr>
                            <td class="desc"colspan="4">Instruction</td>
                             <td class="no"colspan="4"></td>
                            <td class="desc"colspan="4">{!! $content['order']->instruction !!}</td>
                          </tr>
                           <tr>
                            <td class="no"colspan="4">Promotion part</td>
                             <td class="no"colspan="4"></td>
                            <td class="desc"colspan="4">{!! $content['order']->Promotion_part !!}</td>
                          </tr>
                           <tr>
                            <td class="no"colspan="4">Features </td>
                             <td class="no"colspan="4"></td>
                             <td class="no"colspan="4">{!! $content['order']->features !!}</td>
                            <tr>
                            <td class="desc"colspan="4">Template script </td>
                             <td class="no"colspan="4"></td>
                             <td class="no"colspan="4"><a href="{!! $content['order']->template_script !!}" download rel="noopener noreferrer" target="_blank" >{!! $content['order']->template_script !!}</a></td>
                          </tr>
                          </tr>
                           <tr>
                            <td class="no"colspan="4">Suggestions </td>
                             <td class="no"colspan="4"></td>
                            <td class="desc"colspan="4">{!! $content['order']->suggestions !!}</td>
                          </tr>
                           
                        </tbody>
                        
                      </table>
                      <table cellspacing="0" border="0" cellpadding="0" width="100%" style="padding: 15px; color: #000000;">
                         <tr>
                          <td colspan="2"></td>
                          <td colspan="2"></td>
                          <td>SUBTOTAL : &#8377; {!! $content['order']->camp_price !!}</td>
                        </tr>
                        <tr>
                          <td colspan="2"></td>
                          <td colspan="2"></td>
                          <td>TAX {!! $content['order']->tax !!}%  : &#8377; {!! $content['order']->tax_amount !!}</td>
                        </tr>
                        <tr>
                          <td colspan="2"></td>
                          <td colspan="2"></td>
                          <td>GRAND TOTAL : &#8377; {!! $content['order']->total_pay !!}</td>
                        </tr>
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
