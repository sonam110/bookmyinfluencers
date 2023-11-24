<html>
<head>
  <title></title>
</head>
<body>
<p>Hi {{ @$content['influ_name'] }}</p>

<p>{{  @$content['order']->campInfo->brand_name  }} has requested changes in the {{ @$content['title'] }} on the order for [<strong>{{  @$content['order']->campInfo->camp_title  }}</strong>]. Please login to the influencer &nbsp;account to check the issue and take action respectively.</p>

<p>{{  @$content['order']->orderProcess->comment  }}</p>

<p>This is an automated notification. Please do not reply directly to this message.</p>

<p><strong>Thank You</strong></p>

<p><strong>Team BookMyInfluencers</strong><br />
&nbsp;</p>
</body>
</html>
