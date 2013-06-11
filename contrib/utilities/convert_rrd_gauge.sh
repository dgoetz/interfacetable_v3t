#!/bin/bash
#set -x

# Example: [icinga@server]$ /var/tmp/convert_bps.sh /usr/local/pnp4nagios/perfdata/rrd

for i in `ls -d $1/*`; do

    cd $i
    echo "######################## Directory $i ##########################"

    echo "=== Processing rrd files ==="
    for x in `ls If_*Pkts*.rrd If_*Bits*.rrd 2>/dev/null`; do
        
        # get mtime of the file
        xmtime=`stat -c %Y $x`
        
        echo "- Backuping rrd file $x as ${x}.bak -"
        cp -p "$x" "${x}.bak"

        echo "- Converting $x to DST GAUGE -"
        rrdtool tune $x -d 1:GAUGE

        y=`echo $x | sed 's/Pkts/Pps/'`
        y=`echo $y | sed 's/Bits/Bps/'`
        echo "- Renaming ${x} to ${y} -"
        mv -f ${x} ${y}
        
        # reapply kept mtime
        touch -t $xmtime $x
        
    done

    echo "=== Processing xml files ==="
    for x in `ls If_*.xml 2>/dev/null`; do
        
        # get mtime of the file
        xmtime=`stat -c %Y $x`
        
        echo "- Backuping xml file $x as ${x}.bak -"
        cp -p "$x" "${x}.bak"

        echo "- Adapting $x -"
        sed -i 's/Bits/Bps/g' $x
        sed -i 's/Pkts/Pps/g' $x
        sed -i 's#<UNIT>c</UNIT>#<UNIT></UNIT>#g' $x
        
        # reapply kept mtime
        touch -t $xmtime $x
        
    done

done
