<?php
require_once('loadclasses.php');
$page = new Page('About Spacemail');
$html = '<p>Spacemail.tk &copy;'.date('Y').' Snitch Ashor of MBLOC.<br/><br/>
Version 1.4<br/><br/>
This app is built using php and the <a href="http://getbootstrap.com/">bootstrap</a> framework.<br/>
All interactions with EVE Online are done using the <a href=" https://esi.tech.ccp.is/">EVE Swagger Interface</a><br/>
<br/>
Sources are available <a href="https://bitbucket.org/snitchashor/spacemail">here</a>.<br/>
<br/>
Additional Software used:<br/>
<ul>
<li>ESI php client generated with <a href="http://swagger.io/swagger-codegen/">swagger-codegen</a></li>
<li>Auth was adopted from Fuzzy Steve\'s <a href="https://github.com/fuzzysteve/eve-sso-auth">EVE SSO Auth</a></li>
<li><a href="https://jquery.com/">jQuery</a></li>
<li>jQuery <a href="https://datatables.net/">datatables</a></li>
<li>Twitter <a href="https://twitter.github.io/typeahead.js/">typeahead.js</a></li>
<li>Nakupanda\'s <a href="https://nakupanda.github.io/bootstrap3-dialog/">Bootstrap Dialog</a></li>
<li><a href="http://searchturbine.com/php/phpwee">PHPWee</a> Minifier</li>
<li>Sydcanem\'s <a href="https://github.com/sydcanem/bootstrap-contextmenu">Bootstrap Contextmenu</a></li>
<li><a href="https://github.com/bootstrap-wysiwyg/bootstrap3-wysiwyg">bootstrap3-wysihtml5</a></li>
<li>The <a href="http://docs.guzzlephp.org/en/stable/">Guzzle</a> PHP HTTP client</li>
<li>Kevinrob\'s <a href="https://github.com/Kevinrob/guzzle-cache-middleware">Guzzle Cache Middleware</a></li>
<li>Local AccessToken verification using Spomky Lab\'s <a href="https://web-token.spomky-labs.com/">JWT Framework</a></li>
</ul>
<br/>
Special Thanks to a lot of very helpful people in #devfleet and #esi on the tweetfleet slack.<br/>
<br/>
So long,<br/>
o7, Snitch.
</p>
';
$page->addBody($html);
$page->display();
exit;
?>
