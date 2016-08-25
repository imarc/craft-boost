#!/bin/bash
##
# This is a wrapper script for boost to make it eaier to use. Link to this file
# by running the following command file in your site's root directory; this
# is the same directory you'd find dev/, stage/, and prod/.
#
#     ln -s dev/craft/plugins/boost/deploy ./deploy
#
# After that, you can call this script using ./deploy <command> instead of
# prod/craft/app/etc/console/yiic boost <command>.
#
# @author Kevin Hamer [kh] <kevin@imarc.com>
#

YIIC="$(dirname $0)/prod/craft/app/etc/console/yiic"

# Check that yiic exists; try to use prod, otherwise use dev.
if [ ! -f "$YIIC" ]; then
    DEV_YIIC="$(dirname $0)/dev/craft/app/etc/console/yiic"

    if [ ! -f "$DEV_YIIC" ]; then
        echo Unable to find yiic command: $YIIC and $DEV_YIIC do not exist.
        exit 1
    else
        YIIC="$DEV_YIIC"
    fi
fi

# Check if yiic is executable; if we can, let's make it executable.
if [ ! -x $YIIC ]; then
    chmod +x $YIIC

    if [ ! -x $YIIC ]; then
        echo $YIIC is not executable. Please make $YIIC executable.
        exit 1
    fi
fi

# If a single argument is passed, use it as the target environment. This is a
# legacy syntax for convienence.
if [ "$#" -eq 1 ]; then
    if [[ "$1" == "dev" ]] || [[ "$1" == "stage" ]] || [[ "$1" == "prod" ]]; then
        $YIIC boost deploy --env=$1;
        exit 0
    else
        $YIIC boost
        exit 1
    fi
else
    $YIIC boost "$@"
fi
