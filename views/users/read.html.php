<h2>User Detail</h2>

<h3><?=$document->email; ?></h3>
<p>Member since <?=$this->minervaTime->to('meridiem', $document->created); ?></p>
<p>Last seen <?=$this->minervaTime->to('meridiem_short', $document->last_login_time->sec); ?> from <?=$document->last_login_ip; ?></p>
<p>Role: <?=$document->role; ?></p>