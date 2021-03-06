#!/bin/sh

#  Copyright 2011 John Mullee
#
#  This file is part of PhpUnixDaemon.
#
#  PhpUnixDaemon is free software: you can redistribute it and/or modify it under the terms of the
#  GNU General Public License as published by the Free Software Foundation, either version 3 of
#  the License, or (at your option) any later version.
#
#  PhpUnixDaemon is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
#  without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#  See the GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License along with PhpUnixDaemon.
#  If not, see http://www.gnu.org/licenses/.

INCFILE=/usr/include/asm-generic/signal.h
SIGNALS_PHP="signals.php"

cat > ${SIGNALS_PHP}<<EOF
<?php
# generated by '$0' on `date`\n"
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
EOF

grep "^#define *SIG[A-Z0-9]*.*[0-9][0-9]*$" ${INCFILE} |\
	tr '\r\t' ' ' | tr -s ' ' |\
	sed "s|^[^ ]* \([A-Z0-9_]*\) \([0-9]*\).*|if(@defined('\1'))	define('\1',\2);|" | tr '@' '!' |\
	sort -u \
	>> ${SIGNALS_PHP}

