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

// simple logging function

define('DEBUG',4);
define('WARN', 3);
define('ERROR',2);
define('FATAL',1);

$logfile='/tmp/logfile.log';
$logfilehandle = null;
$max_loglevel = DEBUG;

function logmsg($level, $msg, $file='', $line=0)
	{
	global $logfile, $max_loglevel, $logfilehandle;

	$level_names = array(FATAL=>'FATAL', ERROR=>'ERROR',WARN=>'WARN',DEBUG=>'DEBUG');

	// prepend level, filename and line-number to message
	$lmsg = $level_names[$level].':('.$file.':'.$line.'):'.$msg."\n";

	// open log file
	if($logfilehandle==null)
		$logfilehandle=fopen($logfile,'w+');

	// exit if logfile still not open
	if($logfilehandle==null)
		die('couldnt open logfile, to write '.$lmsg);
	else
		// if level less-or-equal to max desired level
		if($level <= $max_loglevel)
			// log the message
			fwrite($logfilehandle, $lmsg);

	// exit on fatal errors
	if($level==FATAL)
		die($lmsg);
	}

