<html>
<head>
  <title></title>
</head>
<body>
<p>Hi {{ @$content['brand_name'] }},</p>

<p>The Video URL for {{  @$content['order']->campInfo->camp_title  }} has been submitted by {{  @$content['channel_name']  }}. Please check the video and approve the order.</p>

<p><strong>{{  @$content['order']->orderProcess->live_video  }}</strong></p>

<p>The order will be auto-approved in 24 hours in case of inaction.&nbsp;</p>

<p>This is an automated notification. Please do not reply directly to this message.</p>

<p>We highly recommend whitelisting offer notification email so that you never miss out on any paid offer.</p>

<p><strong>Thanks &amp; Regards,<br />
<br />
Team BookMyInfluencers</strong></p>

<p><campaign title=""> </campaign></p>
</body>
</html>
