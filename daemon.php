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

# 'signals.php' generated from script by generate_signal_php.sh, using '/usr/include/asm-generic/signal.h'
require_once('signals.php');

# basic logging functionality
require_once('logging.php');

/**
 * PID of process
 * 
 * @var    integer
 */
$G_PID = -1;


/**
 * signals to be ignored when in critical section
 * 
 * @var    array
 */
$G_signals = array(SIGTERM,SIGHUP,SIGUSR1,SIGUSR2);


/**
 * true when in critical section
 * 
 * @var    boolean
 */
$G_Crit_Sect = false;


/**
 * set to true when a signal was recieved during a critical section
 * 
 * @var    boolean
 */
$G_MsgQStop = false;


/**
 * Signal handler function, for calling when signals are received.
 * 
 * If in a critical section, sets a flag when a masked signal is recieved.
 * Otherwise, exits the process.
 * The flag is expected to be checked, so that the process can exit when it has been set, after exiting a critical seection.
 * 'man 7 signal'
 * 
 * @return void  
 * @access public
 * @see    set_signal_handlers, exit
 */
function sig_handler($signo) 
	{
	global $G_MsgQStop, $G_Crit_Sect, $G_signals;
	if(in_array($signo,$G_signals))
		{
		if($G_Crit_Sect==false)
			{
			logmsg(WARN,'SIGNAL '.$signo.', No Critical Section : terminating..',__FILE__,__LINE__);
			exit();
			}
   		logmsg(WARN,'SIGNAL '.$signo.', In Critical Section : Ending soon ..',__FILE__,__LINE__);
		$G_MsgQStop = true;
		}
	else logmsg(WARN,'SIGNAL:unhandled signal',__FILE__,__LINE__);
	}

/**
 * Sets up signal handler function for each of the masked signals
 * 
 * @return void  
 * @access public
 * @see    sig_handler, pcntl_signal
 */
function set_signal_handlers($hfn='sig_handler',$restart=true)
	{
	global $G_signals;
	foreach($G_signals as $sig)
		if(!pcntl_signal($sig, $hfn, $restart))
			app_err('SIGNAL:couldnt set handler for signal '.$sig,__FILE__,__LINE__);
	}

/**
 * Function to set or unset flag and signal-handlers on entering or leaving a critical section.
 * 
 * call 'critical_section(true);' before that start of a criticla section;
 * call 'critical_section(false);' after the end of a criticla section.
 * Make sure to wrap the intervening code in a try-catch block to prevent errors causing the
 * 'critical_section(false)' call to be skipped
 * 
 * @return void  
 * @access public
 */
function critical_section($is=true)
	{
	global $G_Crit_Sect,$G_signals;
	if($is)
		{
		$G_Crit_Sect=true;
		logmsg(DEBUG,'SIGNAL:Enter Crit Sect',__FILE__,__LINE__);
		if(function_exists('pcntl_sigprocmask'))
			pcntl_sigprocmask(SIG_BLOCK, $G_signals);
		}
	else
		{
		$G_Crit_Sect=false;
		logmsg(DEBUG,'SIGNAL:Leave Crit Sect',__FILE__,__LINE__);
		if(function_exists('pcntl_sigprocmask'))
			pcntl_sigprocmask(SIG_UNBLOCK, $G_signals);
		}
	}

/**
 * Writes the process's PID to a pid file; exits process if pid-file not writeable
 * 
 * @return void  
 * @access public
 * @see    fopen, fwrite, fclose, exit
 */
function write_pid($pidfilename,$pid)
	{
	$h = fopen($pidfilename,'w+');
	if($h==false)
		{
		logmsg(FATAL,"can't open file $pidfilename for writing",__FILE__,__LINE__);
		}
	else
		{
		fwrite($h, $pid);
		fclose($h);
		}
	}

/**
 * Writes the process's PID to a pid file; exits process if pid-file not writeable
 * 
 * @return void  
 * @access public
 * @see    write_pid, set_signal_handlers, pcntl_fork, posix_setsid, posix_getpid, chdir, umask, posix_setgid, posix_setuid, declare
 */
function become_daemon($pidfile,$userid=-1,$groupid=-1)
	{
	global $G_PID;

	// need to be run as root to change user
	if($userid!=-1 or $groupid!=-1)
		{
		$uid = posix_getuid();
		if($uid != 0)
			logmsg(FATAL,'need to be run as root, to change userId',__FILE__,__LINE__);
		}

	// make pid file writeable, before dropping privs in child process
	exec("/usr/bin/touch '$pidfile' && /bin/chown $userid:$groupid '$pidfile' && /bin/chmod g+w '$pidfile'");

	// fork process ; after forking, both parent and child process continue, but only the child has $child==true
	$child = pcntl_fork();

	if($child == -1)
		{
		logmsg(FATAL,'could not fork',__FILE__,__LINE__);
		}
	if($child)
		{
		logmsg(DEBUG,'parent (pid='.$G_PID.') exiting',__FILE__,__LINE__);
		exit; // kill parent
		}

	// Only the Child process continues from here

	// daemon safety : close input, cd to root, unset umask, set user & group ID.
	fclose(STDIN);
	chdir("/");
	umask(0); // clear umask
	if($userid!=-1 or $groupid!=-1)
		{
		posix_setgid($groupid);
		posix_setuid($userid);
		}

	// get child process-ID
	$G_PID = posix_getpid();

	// write pid to file; exits on write failure
	write_pid($pidfile,$G_PID);

	// become session leader
	posix_setsid();

	// php daemons shouldn't be time-limited
	ini_set("max_execution_time", "0");
	ini_set("max_input_time", "0");
	set_time_limit(0);

	// set up php signal handling
	declare(ticks=1);
	set_signal_handlers();

	logmsg(WARN,'Daemon PID '.$G_PID,__FILE__,__LINE__);
	}

