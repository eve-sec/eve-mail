<?php
require_once('loadclasses.php');
$page = new Page('Spacemail disclaimer');
$html = '<p><h4>If you are courious what I do with the API access...</h4><br/>
Long story short: <i>Nothing</i>. I will not read your mails, send mails, read notifications or calendar events or anything else using '.URL::url_path().'.<br/>
<br/>
A little more precise:<br/>
<br/>
Everyone registering for an application (such as this webpage), which requires access to ESI (that\'s the new API) is bound to the <a href="https://developers.eveonline.com/resource/license-agreement">developer license agreement</a>. It\'s a pretty lengthy document but in order to know that I\'m not allowed to access your information without your knowledge and consent, just read section 2.3.<br/>
<br/>
So long,<br/>
o7
</p>
';
$page->addBody($html);
$page->display();
exit;
?>
