#!/usr/bin/env php
<?php
// -----------------------------------------------------------------------------
// Usage: $ ./phpipam2label-ips -s <subnet> [-n <name>] [-r <regexp>] [-f <ipam_field> -v <value>]
//
// Examples:
//        $ ./phpipam2label-ips -s 147.251.90.0/24 -n fes.ncbr.muni.cz
//        $ ./phpipam2label-ips -s 147.251.84.0/24 -r /wolf[012][0-9].ncbr.muni.cz/
//
// Output:
//        labels sent to the printer
// -----------------------------------------------------------------------------

$arguments = getopt("s:n:r:v:f:h");

if(array_key_exists("h",$arguments)) {
    echo "\n";
    echo "Usage: phpipam2label-ips -s <subnet> [-n <name>] [-r <regexp>] [-f <ipam_field> -v <value>]\n";
    echo "\n";
    echo "       -s subnet in CDIR format (mandatory)\n";
    echo "       -n single hostname\n";
    echo "       -r regular expression match on hostname\n";
    echo "       -f ipam_field and its value -v\n";
    echo "\n";    
    echo "Examples:\n";
    echo "  $ phpipam2label-ips -s 147.251.90.0/24 -n fes.ncbr.muni.cz\n";
    echo "  $ phpipam2label-ips -s 147.251.84.0/24 -r /wolf[012][0-9].ncbr.muni.cz/\n";   
    echo "\n";
    exit(0);
}

if(array_key_exists("s",$arguments) == FALSE) {
    echo "\n";
    echo ">>> ERROR: Subnet not specified!\n";
    echo "\n";
    exit(1);
}

$subnet = $arguments["s"];

// -----------------------------------------------------------------------------
// access to phpIPAM api
include 'phpipam.conf';
// -----------------------------------------------------------------------------

// printer setup
$model  = 'QL-700';
$labels = '62x100';
$port   = '/dev/usb/lp0';

$dims = array (
        '62x100' => array(696,1109)
        );
        
$picname = __DIR__ . "/label-ip.png";
        
echo "\n";
printf("SubNet : %s\n",$subnet);
printf("Printer: %s\n",$model);
printf("Labels : %s\n",$labels);
echo "\n";        

require_once(__DIR__ . '/lib.php');

// -----------------------------------------------------------------------------

function list_hosts($hosts)
{
    global $subnet_data;

    printf("\n");          
    printf("        IP               HostName             \n");          
    printf("------------------ ---------------------------\n");
    
    $i = 0;
    foreach($hosts as $item){
        printf("%-16s | %-25s\n",$item->ip,get_host_fqdn($item));
        
        printf("   # Admin:        %s\n",get_host_admin($item));

        if( $item->mac != "" ){
        printf("   # MAC:          %s\n",trim($item->mac));
        }
        if( $item->custom_DHCP != "" ){
        printf("   # DHCP:         %s\n",trim($item->custom_DHCP));
        }         
        
        if( ($item->port != "") || ($item->deviceId > 0) ){
        printf("   # Plug/port:    %s\n",get_host_plug($item));
        }
        if( $item->location > 0 ){
        printf("   # Room:         %s\n",get_location_name($item->location));
        }
        printf("   # Group:        %s\n",$item->custom_Group);
        
        if( $item->custom_Institute != "" ){
        printf("   # Institute:    %s\n",trim($item->custom_Institute));
        }  
        if( $item->custom_AssetNo != "" ){
        printf("   # Asset number: %s\n",trim($item->custom_AssetNo));
        } 
        if( $item->custom_Acquired != "" ){
        printf("   # Acquired:     %s\n",trim($item->custom_Acquired));
        } 
        
        if( $item->custom_Cluster != "" ){
        printf("   # Cluster:      %s\n",$item->custom_Cluster);
        }        
        printf("\n");   
        $i++;
    }

    printf("Number of hosts: %d\n",$i);
    printf("\n");      
}

// -----------------------------------------------------------------------------

function label_host($host)
{
    global $picname, $dims, $labels;
    
    $font1 = __DIR__ . '/fonts/Roboto-Regular.ttf';
    $font2 = __DIR__ . '/fonts/Roboto-Bold.ttf';
    $font3 = __DIR__ . '/fonts/RobotoMono-Regular.ttf';
    
    $result = true;
    $w = $dims[$labels][1]; // reverse order by intention
    $h = $dims[$labels][0];
    
    $img = imagecreate($w,$h); 
    if( $img == false ) return(false);
    
    $black = imagecolorallocate($img,  0,   0,    0);
    $white = imagecolorallocate($img, 255, 255, 255);
    
    // white background
    imagefilledrectangle($img,0,0,$w-1,$h-1,$white);
    
    // right notice        
    $notice = "Any changes need to be reported to support@lcc.ncbr.muni.cz";
    print_text($img,array($w-50,0,50,$h),$font1,18,90,$black,$notice);

    // main box
    print_rectangle($img,array(0,0,$w-50,$h),5,$black);

    // name
    $hostname = get_host_fqdn($host);
    $name = get_host_name($host);
    print_rectangle($img,array(0,0,$w-50,300),5,$black);
    print_text($img,array(5,5,$w-60,290),$font2,180,0,$black,strtoupper($name));
    
    // administrator
    print_rectangle($img,array(0,295,$w-50,80),5,$black);
    $admin = get_host_admin($host);
    if( $admin != '' ){
        $notice = sprintf("Admin: %s",$admin);
    } else {
        $notice = " ";
    }
    print_text($img,array(5,300,$w-60,80),$font1,36,0,$black,$notice);
    
    $notice = '';
    $notice = $notice . sprintf("Name : %-22s\n",trim($hostname));
    $notice = $notice . sprintf("IP   : %-22s\n",trim($host->ip));
    $notice = $notice . sprintf("MAC  : %-22s\n",trim($host->mac));
    $notice = $notice . sprintf("DHCP : %-22s\n",trim($host->custom_DHCP));
    $notice = $notice . sprintf("Plug : %-22s\n",trim(get_host_plug($host)));
    $notice = $notice . sprintf("Room : %-22s\n",trim(get_location_name($host->location)));
    $notice = $notice . sprintf("Group: %-22s\n",trim($host->custom_Group));    
    
    // technical data
    print_text_na($img,array(25,400,750,275),$font3,27,0,$black,$notice);
    
    
    // asset number
    $notice = '';
    $notice = $notice . sprintf("%s\n",trim($host->custom_Institute));
    $notice = $notice . sprintf("%s\n",trim($host->custom_AssetNo));
    $notice = $notice . sprintf("%s\n",trim($host->custom_Acquired));
    $notice = $notice . "\n";
    $notice = $notice . "\n";
    
    // cluster
    if( $host->custom_Cluster != '' ){
        $notice = $notice . "Cluster:\n";
        $notice = $notice . trim($host->custom_Cluster);
    }
    
    print_text_na($img,array(785,400,250,275),$font3,27,0,$black,$notice);    
    
    // save image
    if( imagepng($img,$picname) == false ){
        $result = false;
    }
    imagedestroy($img);
    
    return($result);
}

// -----------------------------------------------------------------------------

init_session();
get_subnetid();
get_subnet();
get_hosts();

$print_hosts = [];

// filter hosts
foreach($hosts->data as $item){

    // test required files
    $hostname = trim($item->hostname);
    if ( strpos($hostname, ".") == false ){
        $hostname = $hostname . "." . $subnet_data->data->custom_Domain;
    }
    if( $hostname == '' ) continue;
    if( $item->ip == '' ) continue;
        
    $selected = true;
        
    // filter data
    if( array_key_exists("n",$arguments) == true ){
        if( $arguments["n"] != $hostname ) $selected = false;
    }
    if( array_key_exists("r",$arguments) == true ){
        if( preg_match($arguments["r"],$hostname) == 0 ) $selected = false;
    } 
    if( array_key_exists("f",$arguments) == true ){
        $key = $arguments["f"];
        $value = $arguments["v"];
        if( $item->$key != $value  ) $selected = false;
    }     
    
    if( $selected == true ){
        array_push($print_hosts,$item);
    }
}

// print selected hosts
list_hosts($print_hosts);

// ask to confirm
echo "Is this correct?  Type 'yes' to continue: ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if( trim($line) != 'yes' ){
    echo "ABORTING!\n";
    exit(1);
}

printf("\n");
printf("Printing ...\n"); 
printf("\n"); 
printf("        IP               HostName             \n");          
printf("------------------ ---------------------------\n");

// print labels
foreach($print_hosts as $item){
    printf("%-16s | %-25s\n",$item->ip,get_host_fqdn($item));
    // create label picture
    if( label_host($item) == false ){
        echo "\n";
        printf(">>> ERROR: Unable to print: %s\n",$item->ip);
        echo "\n";    
        exit(1);
    }
    // send it to printer
    $cmd = 'brother_ql -m ' . $model . ' -p ' . $port . ' print -l ' . $labels . ' -r 90 ' . $picname;
    system($cmd);
    printf("\n");
}

?>
