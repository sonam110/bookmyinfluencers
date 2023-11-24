
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Invoice</title>    
    <link href='https://fonts.googleapis.com/css?family=Source Sans Pro' rel='stylesheet'>
    <style type="text/css">


.clearfix:after {
  content: "";
  display: table;
  clear: both;
}
a {
  color: #0087C3;
  text-decoration: none;
}

body {
  position: relative;
  width: 21cm;  
  margin: 0 auto; 
  color: #555555;
  background: #FFFFFF; 
  font-family: Arial, sans-serif; 
  font-size: 14px; 
  font-family: Source Sans Pro;
}

header {
  padding: 10px 0;
  margin-bottom: 20px;
  border-bottom: 1px solid #AAAAAA;
}

#logo {
  float: left;
  margin-top: 8px;
}

#logo img {
  height: 70px;
}

#company {
  float: right;
  text-align: right;
}


#details {
  margin-bottom: 50px;
}

#client {
  padding-left: 6px;
  border-left: 6px solid #0087C3;
  float: left;
}

#client .to {
  color: #777777;
}

h2.name {
  font-size: 1.4em;
  font-weight: normal;
  margin: 0;
}

#invoice {
  float: right;
  text-align: right;
}

#invoice h1 {
  color: #0087C3;
  font-size: 2.4em;
  line-height: 1em;
  font-weight: normal;
  margin: 0  0 10px 0;
}

#invoice .date {
  font-size: 1.1em;
  color: #777777;
}

table {
  width: 100%;
  border-collapse: collapse;
  border-spacing: 0;
  margin-bottom: 20px;
}

table th,
table td {
  padding: 20px;
  background: #EEEEEE;
  text-align: center;
  border-bottom: 1px solid #FFFFFF;
}

table th {
  white-space: nowrap;        
  font-weight: normal;
}

table td {
  text-align: right;
}

table td h3{
  color: #dc2626;
  font-size: 1.2em;
  font-weight: normal;
  margin: 0 0 0.2em 0;
}

table .no {
  color: #FFFFFF;
  font-size: 1.6em;
  background: #dc2626;
}

table .desc {
  text-align: left;
}

table .unit {
  background: #DDDDDD;
}

table .qty {
}

table .total {
  background: #dc2626;
  color: #FFFFFF;
}

table td.unit,
table td.qty,
table td.total {
  font-size: 1.2em;
}

table tbody tr:last-child td {
  border: none;
}

table tfoot td {
  padding: 10px 20px;
  background: #FFFFFF;
  border-bottom: none;
  font-size: 1.2em;
  white-space: nowrap; 
  border-top: 1px solid #AAAAAA; 
}

table tfoot tr:first-child td {
  border-top: none; 
}

table tfoot tr:last-child td {
  color: #dc2626;
  font-size: 1.4em;
  border-top: 1px solid #dc2626; 

}

table tfoot tr td:first-child {
  border: none;
}

#thanks{
  font-size: 2em;
  margin-bottom: 50px;
}

#notices{
  padding-left: 6px;
  border-left: 6px solid #0087C3;  
}

#notices .notice {
  font-size: 1.2em;
}

footer {
  color: #777777;
  width: 100%;
  height: 30px;
  position: absolute;
  bottom: 0;
  border-top: 1px solid #AAAAAA;
  padding: 8px 0;
  text-align: center;
}


    </style>
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        <img src="{{asset('images/reachomation_logo_black.png')}}">
      </div>
      <div id="company">
        <h2 class="name">{!! $websiteSetting->company_name !!}</h2>
        <div>{!! $websiteSetting->address !!},<br>{!! $websiteSetting->city !!}, {!! $websiteSetting->pincode !!} India</div>
        <div>M : {!! $websiteSetting->  phone !!} | E : <a href="mailto:{!! $websiteSetting->email !!}">{!! $websiteSetting->email !!}</a></div>  
      </div>
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
          <div class="to">INVOICE TO:</div>
          <h2 class="name">{!! @$checkApp->brand_name !!}</h2>
          <div class="address"> {!! $userInfo ->company_address !!}<br>
          {!! $userInfo ->phone !!}</div>
          <div class="email"><a href="mailto:{!! $userInfo ->email !!}">{!! $userInfo ->email !!}</a></div>
        </div>
        <div id="invoice">
          <h3>INVOICE {{ $File_Name }}</h3>
          <div class="date">Date of Invoice:  {{date('d M Y')}}</div>
        </div>
      </div>
      <table border="0" cellspacing="0" cellpadding="0">
        <thead>
         <tr>
              <th class="no">S.NO</th>
              <th class="desc"><div>Description of Services</div></th>
              <th class="desc"><div>Quantity</div></th>
              <th class="unit"><div>Price per Video</div></th>
              <th class="total"><div>Total Price</div></th>
  
            </tr>
        </thead>
        <tbody>
         <tr>
            <td class="no">1</td>
            <td class="desc">{{ @$checkApp->promot_product }} by {{ $channel_name }} </td>
            <td class="qty">1</td>
            <td class="unit">{{ $currency }} {{ $camp_price }}</td>
            <td class="total"> {{ $currency }} {{ $camp_price }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">Total</td>
            <td>{{ $currency }} {{$camp_price}}</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">GST@18%</td>
            <td>{{ $currency }} {{ $tax_amount }}</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">Grand Total </td>
            <td>{{ $currency }} {{ $total_pay }}</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">Paying Amount </td>
            <td>{{ $currency }} {{ $pay_amount }}</td>
          </tr>
          <tr>
            <td colspan="2"></td>
            <td colspan="2">Remaining Amount </td>
            <td>{{ $currency }} {{ $remainingAmount }}</td>
          </tr>
        </tfoot>
      </table>
      <div id="thanks">Thank you!</div>
      <div id="notices">
         <div class="notice">Bank Details:<br>
          Account Name: Accunite Solutions Private Limited<br>
          Account No: 7213021815<br>
          IFSC: KKBK0005040<br>
          Bank branch: Kotak Mahindra Bank Ltd<br>
          GST No: 09AAMCA0390C1ZV<br>
          PAN No: AAMCA0390C
          </div> 
      </div>
    </main>
    <footer>
      <h3>Accunite Solutions Private Limited</h1>
     <p> Address: 115, Tower 1, Assotech Business Cresterra, Sector-135, Noida, U.P.- 201301
      Ph. +91 120 7195328, Web: <a href=">www.accunite.com">www.accunite.com</a>, Email: <a href="mailto:contact@accunite.com">contact@accunite.com</a></p>
    </footer>
  </body>
</html>