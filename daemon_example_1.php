<?php
/*
	Copyright 2011 John Mullee

	This file is part of PhpUnixDaemon.

	PhpUnixDaemon is free software: you can redistribute it and/or modify it under the terms of the
	GNU General Public License as published by the Free Software Foundation, either version 3 of
	the License, or (at your option) any later version.

	PhpUnixDaemon is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
	without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the GNU General Public License for more details.

	You should have received a copy of the GNU General Public License along with PhpUnixDaemon.
	If not, see http://www.gnu.org/licenses/.
*/

/*
first allow writes to logfile ater privilige-drop
	sudo touch /tmp/logfile.log
	sudo chmod a+rw /tmp/logfile.log
then start it from command-line like this:
	sudo php -f daemon_example_1.php
*/
require_once('daemon.php');

function run()
	{
	$h = fopen('/tmp/daemon_example_1.log','w+');
	if($h != null)
		{
		while(true)
			{
			$msg = 'daemon says '.date('Y-m-d H:i:s');

			// enter critical section
			critical_section(true);

			// perform critical operation
			try
				{
				fwrite($h, $msg."\n");
				logmsg(DEBUG,$msg,__FILE__,__LINE__);
				}
			catch(Exception $e) { }

			// leave critical section
			critical_section(false);

			sleep(3);
			}
		fclose($h);
		}
	else
		logmsg(FATAL,'couldnt open output file',__FILE__,__LINE__);
	}

$pidfile = '/tmp/daemon_example_1.pid';
$userid  = 33; // 'www-user' in debian-derived systems
$groupid = 1;  // 'daemon'   in debian-derived systems
become_daemon($pidfile,$userid,$groupid);

run();

