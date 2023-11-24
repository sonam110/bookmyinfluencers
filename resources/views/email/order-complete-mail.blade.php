@if(@$content['type']=='brand')
<html>
<head>
  <title></title>
</head>
<body>
<p>Hi {{ @$content['brand_name'] }},</p>

<p>You&rsquo;ve approved the video and the order for {{  @$content['order']->campInfo->camp_title  }} has been marked as completed. You can find the video link here.</p>

<p><strong>{{  @$content['order']->orderProcess->live_video  }}</strong></p>

<p>This is an automated notification. Please do not reply directly to this message.</p>

<p><strong>Thank You</strong></p>

<p><strong>Team BookMyInfluencers</strong><br />
&nbsp;</p>

<p><campaign title=""> </campaign></p>
</body>
</html>
@else
<html>
<head>
  <title></title>
</head>
<body>
<p>Hi {{ @$content['influ_name'] }},</p>

<p>[<strong>{{  @$content['order']->campInfo->brand_name  }}</strong>]&nbsp;has approved the video for {{  @$content['order']->campInfo->camp_title  }} and order has been marked as completed. The payment will be processed within 24-48 hours.</p>

<p>This is an automated notification. Please do not reply directly to this message.</p>

<p><strong>Thank You</strong></p>

<p><strong>Team BookMyInfluencers</strong><br />
&nbsp;</p>
</body>
</html>

@endif
