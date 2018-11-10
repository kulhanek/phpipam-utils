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
$subnetid = 0;
$data = [];
$hosts = [];
$subnet_data = "";
$locationsdb = [];
$devsdb = [];

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
        
$picname = "label.png";
        
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
        $hostname = trim($item->hostname);
        if ( strpos($hostname, ".") == false ){
            $hostname = $hostname . "." . $subnet_data->data->custom_Domain;
        }    
        printf("%-16s | %-25s\n",$item->ip,$hostname);
        $i++;
    }
    printf("\n");
    printf("Number of hosts: %d\n",$i);
    printf("\n");      
}

// -----------------------------------------------------------------------------

function imagettfbboxextended($size, $angle, $fontfile, $text) {
    /*this function extends imagettfbbox and includes within the returned array
    the actual text width and height as well as the x and y coordinates the
    text should be drawn from to render correctly.  This currently only works
    for an angle of zero and corrects the issue of hanging letters e.g. jpqg*/
    $bbox = imagettfbbox($size, $angle, $fontfile, $text);

    //calculate x baseline
    if($bbox[0] >= -1) {
        $bbox['x'] = abs($bbox[0] + 1) * -1;
    } else {
        //$bbox['x'] = 0;
        $bbox['x'] = abs($bbox[0] + 2);
    }

    //calculate actual text width
    $bbox['width'] = abs($bbox[2] - $bbox[0]);
    if($bbox[0] < -1) {
        $bbox['width'] = abs($bbox[2]) + abs($bbox[0]) - 1;
    }

    //calculate y baseline
    $bbox['y'] = abs($bbox[5] + 1);

    //calculate actual text height
    $bbox['height'] = abs($bbox[7]) - abs($bbox[1]);
    if($bbox[3] > 0) {
        $bbox['height'] = abs($bbox[7] - $bbox[1]) - 1;
    }

    return $bbox;
}

// -----------------------------------------------------------------------------

function print_text($img,$box,$font,$size,$angle,$color,$text)
{
    do {
        $shrink = false;
        $bbox = imagettfbboxextended($size,0,$font,$text);
        if( $angle == 0 ){
            $x = $box[0] + $bbox['x'] + ($box[2]-$bbox['width'])/2;
            $y = $box[1] + $bbox['y'] + ($box[3]-$bbox['height'])/2;
        } else if ( $angle == 90 ) {
            $x = $box[0] + $bbox['y'] + ($box[2]-$bbox['height'])/2;
            $y = $box[1] + $box[3] - ($bbox['x'] + ($box[3]-$bbox['width'])/2); 
        } else {
            printf("not supported angle!\n");
            exit(1);
        }
        // autoshrink for horizontal text
        if( $angle == 0 ){        
            if( $box[2] < $bbox['width'] ){
                $size -= 5;
                $shrink = true;
            } 
        }
    } while( $shrink == true );
    
//     $red = imagecolorallocate($img,  255,   0,    0);
//     imagerectangle($img,$box[0],$box[1],$box[0]+$box[2]-1,$box[1]+$box[3]-1,$red);
    
    imagettftext($img,$size,$angle,$x,$y,$color,$font,$text); 
}

// -----------------------------------------------------------------------------

function print_text_na($img,$box,$font,$size,$angle,$color,$text)
{
    $bbox = imagettfbboxextended($size,0,$font,$text);
    if( $angle == 0 ){
        $x = $box[0] + $bbox['x'];
        $y = $box[1] + $bbox['y'];
    } else if ( $angle == 90 ) {
        $x = $box[0] + $bbox['y'];
        $y = $box[1] + $box[3] - $bbox['x']; 
    } else {
        printf("not supported angle!\n");
        exit(1);
    }
    
//     $red = imagecolorallocate($img,  255,   0,    0);
//     imagerectangle($img,$box[0],$box[1],$box[0]+$box[2]-1,$box[1]+$box[3]-1,$red);
    
    imagettftext($img,$size,$angle,$x,$y,$color,$font,$text); 
}

// -----------------------------------------------------------------------------

function print_rectangle($img,$box,$width,$color)
{
    $x1 = $box[0];
    $y1 = $box[1];
    $x2 = $x1 + $box[2] - 1;
    $y2 = $y1 + $box[3] - 1;

    for($i=0; $i < $width; $i++){
        imagerectangle($img,$x1,$y1,$x2,$y2,$color);
        $x1++;
        $y1++;
        $x2--;
        $y2--;
    }
}

// -----------------------------------------------------------------------------

function label_host($host)
{
    global $picname, $dims, $labels;
    
    $font1 = __DIR__ . '/extras/fonts/Roboto-Regular.ttf';
    $font2 = __DIR__ . '/extras/fonts/Roboto-Bold.ttf';
    $font3 = __DIR__ . '/extras/fonts/RobotoMono-Regular.ttf';
    
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
    $hostname = trim($host->hostname);
    if ( strpos($hostname, ".") == false ){
        $hostname = $hostname . "." . $subnet_data->data->custom_Domain;
    }
    $mname = explode(".",$hostname);
    print_rectangle($img,array(0,0,$w-50,300),5,$black);
    print_text($img,array(5,5,$w-60,290),$font2,180,0,$black,strtoupper($mname[0]));
    
    // administrator
    print_rectangle($img,array(0,295,$w-50,80),5,$black);
    
    $admin = "";
    if( ($host->custom_Admin != "User") && ($host->custom_Admin != "") ){
        $admin = $host->custom_Admin;
    } else {
        $admin = $host->custom_User;
    }
    if( $admin != '' ){
        $notice = sprintf("Admin: %s",$admin);
    } else {
        $notice = " ";
    }
    print_text($img,array(5,300,$w-60,80),$font1,36,0,$black,$notice);
    
    $notice = '';
    $notice = $notice . sprintf("DNS  : %-22s\n",trim($hostname));
    $notice = $notice . sprintf("IP   : %-22s\n",trim($host->ip));
    $notice = $notice . sprintf("MAC  : %-22s\n",trim($host->mac));
    $notice = $notice . sprintf("DHCP : %-22s\n",trim($host->custom_DHCP));
    $plug = $host->port;
    if( $host->deviceId != 0 ){
        $devstr = get_device_name($host->deviceId);
        if( $plug == '' ) $plug = 'Px';
        $plug = $plug . '(' . trim($devstr) . ')';
        }
    $notice = $notice . sprintf("Plug : %-22s\n",trim($plug));
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
    
    if( $selected == true ){
        array_push($print_hosts,$item);
    }
}

// print selected hosts
list_hosts($print_hosts);

// ask to confirm
// TODO

// print labels
foreach($print_hosts as $item){
    // create label picture
    if( label_host($item) == false ){
        echo "\n";
        printf(">>> ERROR: Unable to print: %s\n",$item->ip);
        echo "\n";    
        exit(1);
    }
    // send it to printer
}

?>
