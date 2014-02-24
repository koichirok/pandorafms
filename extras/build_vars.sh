#!/bin/bash
CODEHOME=/root/code/pandora/branches/pandora_5.0
CODEHOME_ENT=/root/code/artica/code
PANDHOME_ENT=$CODEHOME_ENT/pandora/branches/5.0
RPMHOME=/usr/src/packages
VERSION=$(grep 'my $pandora_version =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
BUILD=$(grep 'my $pandora_build =' $CODEHOME/pandora_server/lib/PandoraFMS/Config.pm | awk '{print substr($4, 2, length($4) - 3)}')
X86_64=`uname -m | grep x86_64`
CONSOLEHOME=$CODEHOME/pandora_console
CONSOLEHOME_ENT=$PANDHOME_ENT/pandora_console
