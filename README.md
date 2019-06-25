#Spacemail.xyz#
Out-of-game mail client for EVE Online.
Copyright 2019 Snitch Ashor of MBLOC.

#Requirements#
+ php 7.1+
+ php-curl
+ php-gmp
+ php-mbstring
+ MySQL 5.6+
+ php-mysqli
+ For certain features (cookies), site should be running via ssl

#Installation#

1. Create a Database for the app.
2. Import schema.sql from the SQL subfolder
3. Go to https://developers.eveonline.com/ and register an app with the following scopes:
	+ esi-calendar.respond_calendar_events.v1
	+ esi-calendar.read_calendar_events.v1
	+ esi-mail.organize_mail.v1
	+ esi-mail.read_mail.v1
	+ esi-mail.send_mail.v1
	+ esi-characters.read_contacts.v1
	+ esi-characters.read_notifications.v1

	The callback url should be http(s)://<domain>/<app path>/login.php

4. Grab the following tables from the SDE and insert them into your database: invTypes, invGroups, dgmTypeEffects (Required for the fitting viewer, get them here: https://www.fuzzwork.co.uk/dump/latest/)
5. Rename config.php.sample to config.php and edit it. Fill in the database and developer app credentials and put a random string for the salt. This one is used to add some security to authentication cookies. Add at least one admin by his or her characterID. If you want to keep track of what you added you can use associative arrays like array("Snitch" => 90976676,)

Done.

#Update#
When updating from verions <= 1.2 to version >= 1.3, drop all tables and re-import schema.sql (or delete the columns accessToken, refreshToken, expires, failcount and enabled from the table esisso and import the table accessToken from schema.sql)

#Version history#

+ 0.1b First public release
+ 1.0 First stable release
+ 1.1 Updated for php7.2
+ 1.2 Updated to SSO v2
+ 1.3 Refactored database, scopes on demand
+ 1.4 Bugfix release

