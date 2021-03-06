<?php

#
# Pnp template for check_interface_table, port perfdata part, output in bits
# Includes the possibility to choose what to display
# By Yannick Charton (tontonitch-pro@yahoo.fr)
# For pnp4nagios v0.6.11 and above
#

#
# Define some variables ..
#

$_WARNRULE  = '#FFFF00';
$_CRITRULE  = '#FF0000';
$_MAXRULE   = '#000000';
$_AREA_PRCT = '#256aef';
$_AREA_BYTE = '#8FBC8F';
$_LINE      = '#000000';
$colors     = array("#CC3300","#CC3333","#CC3366","#CC3399","#CC33CC",
"#CC33FF","#336600","#336633","#336666","#336699","#3366CC","#3366FF",
"#33CC33","#33CC66","#609978","#922A99","#997D6D","#174099","#1E9920",
"#E88854","#AFC5E8","#57FA44","#FA6FF6","#008080","#D77038","#272B26",
"#70E0D9","#0A19EB","#E5E29D","#930526","#26FF4A","#ABC2FF","#E2A3FF",
"#808000","#000000","#00FAFA","#E5FA79","#F8A6FF","#FF36CA","#B8FFE7",
"#CD36FF","#CC3300","#CC3333","#CC3366","#CC3399","#CC33CC","#CC33FF",
"#336600","#336633","#336666","#336699","#3366CC","#3366FF","#33CC33",
"#33CC66","#609978","#922A99","#997D6D","#174099","#1E9920","#E88854",
"#AFC5E8","#57FA44","#FA6FF6","#008080","#D77038","#272B26","#70E0D9",
"#0A19EB","#E5E29D","#930526","#26FF4A","#ABC2FF","#E2A3FF","#808000",
"#000000","#00FAFA","#E5FA79","#F8A6FF","#CC3300","#CC3333","#CC3366",
"#CC3399","#CC33CC","#CC33FF","#336600","#336633","#336666","#336699",
"#3366CC","#3366FF","#33CC33","#33CC66","#609978","#922A99","#997D6D",
"#174099","#1E9920","#E88854","#AFC5E8","#57FA44","#FA6FF6","#008080",
"#D77038","#272B26","#70E0D9","#0A19EB","#E5E29D","#930526","#26FF4A",
"#ABC2FF","#E2A3FF","#808000","#000000","#00FAFA","#E5FA79","#F8A6FF",
"#FF36CA","#B8FFE7","#CD36FF");

## Parameters
$display_traffic    = 1; # 0/1: disable/enable the traffic graph
$display_errors     = 1; # 0/1: disable/enable the error graph
$display_operstatus = 2; # 0: disable the interface status info in graphs
                         # 1: generate a new graph for ifstatus
                         # 2: add a red/orange/green line on the top of the traffic graph depending on the ifstatus
$display_pktload    = 1; # 0: disable the packet load graph
                         # 1: enable the packet load graph, one graphs, lined total in/out
                         # 2: enable the packet load graph, one graphs, lined in/out uni/multicast
                         # 3: enable the packet load graph, one graphs, stacked in/out uni/multicast
                         # 4: enable the packet load graph, two graphs (uni/multicast), bps traffic style
$display_thresholds = 1; # 0/1: disable/enable the thresholds display on graphs

#
# Initial Logic ...
#

$num_graph = 0;
$thresholds_fmt = '%.2lf';

###############################
# Interface status graph
###############################

if($display_operstatus == 1){
    $num_graph++;
    $ds_name[$num_graph] = 'Interface status';
    $opt[$num_graph] = " --vertical-label \"\"  --title 'Interface status' --y-grid none --units-length 8";
    $opt[$num_graph] .= " --watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
    $def[$num_graph] = "";
    $def[$num_graph] .= rrd::def     ("ifstatus", $RRDFILE[1], $DS[1], "AVERAGE");
    $def[$num_graph] .= rrd::ticker  ("ifstatus", 1.1, 2.1, 0.33,"ff","#00ff00","#ff0000","#ff8c00");
}

###############################
# Traffic graph
###############################

if($display_traffic == 1){
    $num_graph++;
    $ds_name[$num_graph] = 'Interface traffic';
    $opt[$num_graph] = " --vertical-label \"bits/s\" -l 0 -b 1000 --slope-mode  --title \"Interface Traffic for $hostname / $servicedesc\" ";
    $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
    $def[$num_graph] = "";
    $def[$num_graph] .= rrd::def     ("bits_in", $RRDFILE[2], $DS[2], "AVERAGE");
    $def[$num_graph] .= rrd::def     ("bits_out", $RRDFILE[3], $DS[3], "AVERAGE");
    if(($display_operstatus == 2)){
        $def[$num_graph] .= rrd::def     ("ifstatus", $RRDFILE[1], $DS[1], "AVERAGE");
        $def[$num_graph] .= rrd::ticker  ("ifstatus", 1.1, 2.1, -0.02,"ff","#00ff00","#ff0000","#ff8c00");
    }
    $def[$num_graph] .= rrd::cdef    ("bits_in_redef", "bits_in,UN,PREV,bits_in,IF");
    $def[$num_graph] .= rrd::cdef    ("bits_out_redef", "bits_out,UN,PREV,bits_out,IF");
    $def[$num_graph] .= rrd::area    ("bits_in_redef",  '#32CD32', 'in_bps        ');
    $def[$num_graph] .= rrd::gprint  ("bits_in_redef",  array("LAST","MAX","AVERAGE"), "%8.2lf%Sbps");
    $def[$num_graph] .= rrd::line1   ("bits_out_redef", '#0000CD', 'out_bps       ');
    $def[$num_graph] .= rrd::gprint  ("bits_out_redef", array("LAST","MAX","AVERAGE"), "%8.2lf%Sbps");
    # Thresholds
    if ($display_thresholds == 1) {
        if ($WARN[2] != "" && is_numeric($WARN[2])){
            $warn = pnp::adjust_unit( $WARN[2],1000,$thresholds_fmt );
            $def[$num_graph] .= rrd::hrule($WARN[2], $_WARNRULE, "Warning on ".$warn[0]."bps ");
        }
        if($CRIT[2] != "" && is_numeric($CRIT[2])){
            $crit = pnp::adjust_unit( $CRIT[2],1000,$thresholds_fmt );
            $def[$num_graph] .= rrd::hrule($CRIT[2], $_CRITRULE, "Critical on ".$crit[0]."bps ");
        }
        if($MAX[2] != "" && is_numeric($MAX[2])){
            $max = pnp::adjust_unit( $MAX[2],1000,$thresholds_fmt );
            $def[$num_graph] .= rrd::hrule($MAX[2], $_MAXRULE, "Maximum on ".$max[0]."bps\\n");
        }
    }
    # Total Values in
    $def[$num_graph] .= rrd::cdef    ("octets_in", "bits_in,8,/");
    $def[$num_graph] .= rrd::vdef    ("total_in", "octets_in,TOTAL");
    $def[$num_graph] .= "GPRINT:total_in:\"Total in  %3.2lf %sB total\\n\" ";
    # Total Values out
    $def[$num_graph] .= rrd::cdef    ("octets_out", "bits_out,8,/");
    $def[$num_graph] .= rrd::vdef    ("total_out", "octets_out,TOTAL");
    $def[$num_graph] .= "GPRINT:total_out:\"Total out %3.2lf %sB total\\n\" ";
}

###############################
# Error/drop packets graph
###############################

if(($display_errors == 1) && (isset($RRDFILE[7]))){
    $num_graph++;
    $ds_name[$num_graph] = 'Error/drop packets';
    $opt[$num_graph] = " --vertical-label \"pkts/s\" -l 0 -b 1000 --title \"Error/drop packets for $hostname / $servicedesc\" ";
    $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
    $def[$num_graph] = "";
    $def[$num_graph] .= rrd::def     ("pkt_in_err", $RRDFILE[4], $DS[4], "AVERAGE");
    $def[$num_graph] .= rrd::def     ("pkt_out_err", $RRDFILE[5], $DS[5], "AVERAGE");
    $def[$num_graph] .= rrd::def     ("pkt_in_drop", $RRDFILE[6], $DS[6], "AVERAGE");
    $def[$num_graph] .= rrd::def     ("pkt_out_drop", $RRDFILE[7], $DS[7], "AVERAGE");
    $def[$num_graph] .= rrd::area    ("pkt_in_err",      '#FFD700', 'in_err              ');
    $def[$num_graph] .= rrd::gprint  ("pkt_in_err", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    $def[$num_graph] .= rrd::area    ("pkt_out_err",     '#FF8C00', 'out_err             ', 'STACK');
    $def[$num_graph] .= rrd::gprint  ("pkt_out_err", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    $def[$num_graph] .= rrd::area    ("pkt_in_drop",  '#7B68EE', 'in_drop          ', 'STACK');
    $def[$num_graph] .= rrd::gprint  ("pkt_in_drop", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    $def[$num_graph] .= rrd::area    ("pkt_out_drop", '#BA55D3', 'out_drop         ', 'STACK');
    $def[$num_graph] .= rrd::gprint  ("pkt_out_drop", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
}

###############################
# Packets load graph
###############################

if(isset($RRDFILE[11])){
    if($display_pktload == 1){
        $num_graph++;
        $ds_name[$num_graph] = 'Packets load';
        $opt[$num_graph] = " --vertical-label \"pkts/s\" -l 0 -b 1000 --title \"Packets load for $hostname / $servicedesc\" ";
        $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
        $def[$num_graph] = "";
        $def[$num_graph] .= rrd::def     ("pkt_in_ucast", $RRDFILE[8], $DS[8], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_ucast", $RRDFILE[9], $DS[9], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_in_nucast", $RRDFILE[10], $DS[10], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_nucast", $RRDFILE[11], $DS[11], "AVERAGE");
        $def[$num_graph] .= rrd::cdef    ("pkt_in", "pkt_in_ucast,pkt_in_nucast,+");
        $def[$num_graph] .= rrd::cdef    ("pkt_out", "pkt_out_ucast,pkt_out_nucast,+");
        $def[$num_graph] .= rrd::line1   ("pkt_in",   '#F78181', 'in_pkts           ');
        $def[$num_graph] .= rrd::gprint  ("pkt_in", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::line1   ("pkt_out",  '#8A0808', 'out_pkts          ');
        $def[$num_graph] .= rrd::gprint  ("pkt_out", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    }
    if($display_pktload == 2){
        $num_graph++;
        $ds_name[$num_graph] = 'Packets load';
        $opt[$num_graph] = " --vertical-label \"pkts/s\" -l 0 -b 1000 --title \"Packets load for $hostname / $servicedesc\" ";
        $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
        $def[$num_graph] = "";
        $def[$num_graph] .= rrd::def     ("pkt_in_ucast", $RRDFILE[8], $DS[8], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_ucast", $RRDFILE[9], $DS[9], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_in_nucast", $RRDFILE[10], $DS[10], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_nucast", $RRDFILE[11], $DS[11], "AVERAGE");
        $def[$num_graph] .= rrd::line1   ("pkt_in_ucast",   '#01DFD7', 'in_ucast           ');
        $def[$num_graph] .= rrd::gprint  ("pkt_in_ucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::line1   ("pkt_out_ucast",  '#0B0B61', 'out_ucast          ');
        $def[$num_graph] .= rrd::gprint  ("pkt_out_ucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::line1   ("pkt_in_nucast",  '#00FF40', 'in_nucast          ');
        $def[$num_graph] .= rrd::gprint  ("pkt_in_nucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::line1   ("pkt_out_nucast", '#088A08', 'out_nucast         ');
        $def[$num_graph] .= rrd::gprint  ("pkt_out_nucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    }
    if($display_pktload == 3){
        $num_graph++;
        $ds_name[$num_graph] = 'Packets load';
        $opt[$num_graph] = " --vertical-label \"pkts/s\" -l 0 -b 1000 --title \"Packets load for $hostname / $servicedesc\" ";
        $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
        $def[$num_graph] = "";
        $def[$num_graph] .= rrd::def     ("pkt_in_ucast", $RRDFILE[8], $DS[8], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_ucast", $RRDFILE[9], $DS[9], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_in_nucast", $RRDFILE[10], $DS[10], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_nucast", $RRDFILE[11], $DS[11], "AVERAGE");
        $def[$num_graph] .= rrd::area    ("pkt_in_ucast",   '#01DFD7', 'in_ucast           ');
        $def[$num_graph] .= rrd::gprint  ("pkt_in_ucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::area    ("pkt_out_ucast",  '#0B0B61', 'out_ucast          ', 'STACK');
        $def[$num_graph] .= rrd::gprint  ("pkt_out_ucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::area    ("pkt_in_nucast",  '#00FF40', 'in_nucast          ', 'STACK');
        $def[$num_graph] .= rrd::gprint  ("pkt_in_nucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::area    ("pkt_out_nucast", '#088A08', 'out_nucast         ', 'STACK');
        $def[$num_graph] .= rrd::gprint  ("pkt_out_nucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    }
    if($display_pktload == 4){
        # Unicast packets load
        $num_graph++;
        $ds_name[$num_graph] = 'Packets load - Unicast';
        $opt[$num_graph] = " --vertical-label \"pkts/s\" -l 0 -b 1000 --title \"Unicast packets load for $hostname / $servicedesc\" ";
        $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
        $def[$num_graph] = "";
        $def[$num_graph] .= rrd::def     ("pkt_in_ucast", $RRDFILE[8], $DS[8], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_ucast", $RRDFILE[9], $DS[9], "AVERAGE");
        $def[$num_graph] .= rrd::area    ("pkt_in_ucast",   '#01DFD7', 'in_ucast           ');
        $def[$num_graph] .= rrd::gprint  ("pkt_in_ucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::line1   ("pkt_out_ucast",  '#0B0B61', 'out_ucast          ');
        $def[$num_graph] .= rrd::gprint  ("pkt_out_ucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        # Non-unicast packets load
        $num_graph++;
        $ds_name[$num_graph] = 'Packets load - Non-unicast';
        $opt[$num_graph] = " --vertical-label \"pkts/s\" -l 0 -b 1000 --title \"Non-unicast packets load for $hostname / $servicedesc\" ";
        $opt[$num_graph] .= "--watermark=\"Template: check_interface_table_port_bigip.php by Yannick Charton\" ";
        $def[$num_graph] = "";
        $def[$num_graph] .= rrd::def     ("pkt_in_nucast", $RRDFILE[10], $DS[10], "AVERAGE");
        $def[$num_graph] .= rrd::def     ("pkt_out_nucast", $RRDFILE[11], $DS[11], "AVERAGE");
        $def[$num_graph] .= rrd::area    ("pkt_in_nucast",  '#00FF40', 'in_nucast          ');
        $def[$num_graph] .= rrd::gprint  ("pkt_in_nucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
        $def[$num_graph] .= rrd::line1   ("pkt_out_nucast", '#088A08', 'out_nucast         ');
        $def[$num_graph] .= rrd::gprint  ("pkt_out_nucast", array("LAST","MAX","AVERAGE"), "%5.1lf%S");
    }
}

?>
