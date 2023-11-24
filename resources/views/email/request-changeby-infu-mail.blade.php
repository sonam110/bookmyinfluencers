<html>
<head>
  <title></title>
</head>
<body>
<p>Hi {{ @$content['brand_name'] }},</p>

<p>{{  @$content['channel_name']  }} has requested changes in the campaign brief on order for {{  @$content['order']->campInfo->camp_title  }} . Please login to the account to check the issue and take action respectively.</p>

<p>{{  @$content['order']->orderProcess->comment  }}</p>

<p>This is an automated notification. Please do not reply directly to this message.</p>

<p><strong>Thank You</strong></p>

<p><strong>Team BookMyInfluencers</strong><br />
&nbsp;</p>

<p><campaign title=""> </campaign></p>
</body>
</html>
