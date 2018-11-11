#!/usr/bin/env php
<?php
// -----------------------------------------------------------------------------
// Usage: $ ./phpipam2label-devs -n <name>
//
// Examples:
//        $ ./phpipam2label-devs -n 1.18SW1
//
// Output:
//        labels sent to the printer
// -----------------------------------------------------------------------------

$arguments = getopt("n:h");

if(array_key_exists("h",$arguments)) {
    echo "\n";
    echo "Usage: phpipam2label-devs -n <name>\n";
    echo "\n";
    echo "       -n device name\n";
    echo "\n";    
    echo "Examples:\n";
    echo "  $ phpipam2label-devs -n 1.18SW1\n"; 
    echo "\n";
    exit(0);
}

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
        
$picname = __DIR__ . "/label-dev.png";
        
echo "\n";
printf("Printer: %s\n",$model);
printf("Labels : %s\n",$labels);
echo "\n";        

require_once(__DIR__ . '/lib.php');

// -----------------------------------------------------------------------------

function get_dev_type($id)
{
    switch($id){
        case 1: return('switch');
        default:
                return('unknown');
    }
}

// -----------------------------------------------------------------------------

function list_devs($items)
{

    printf("\n");          
    printf("        DeviceName         \n");          
    printf("---------------------------\n");
    
    $i = 0;
    foreach($items as $item){
        printf("%-25s\n",trim($item->hostname));
        
        printf("   # Admin:        %s\n",trim($item->custom_Admin));

        printf("   # Type:         %s\n",get_dev_type($item->type));       
        
        if( $item->location > 0 ){
        printf("   # Room:         %s\n",get_location_name($item->location));
        }
        if( $item->custom_NumOfPorts != '' ){
        printf("   # NumOfPorts:   %s\n",trim($item->custom_NumOfPorts));
        }
        if( $item->custom_Port != "" ){
        printf("   # Plug/port:    %s\n",trim($item->custom_Port));
        }        

        if( $item->ip != '' ){
        printf("   # IP:           %s\n",trim($item->ip));
        }      
        if( $item->description != '' ){
        printf("   # Description:  %s\n",trim($item->description));
        }          
        
        if( $item->custom_Institute != "" ){
        printf("   # Institute:    %s\n",trim($item->custom_Institute));
        }  
        if( $item->custom_AssetNo != "" ){
        printf("   # Asset number: %s\n",trim($item->custom_AssetNo));
        } 
        if( $item->custom_Acquired != "" ){
        printf("   # Acquired:     %s\n",trim($item->custom_Acquired));
        } 
             
        printf("\n");   
        $i++;
    }

    printf("Number of devices: %d\n",$i);
    printf("\n");      
}

// -----------------------------------------------------------------------------

function label_dev($item)
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
    $name = $item->hostname;
    print_rectangle($img,array(0,0,$w-50,300),5,$black);
    print_text($img,array(5,5,$w-60,290),$font2,180,0,$black,strtoupper($name));
    
    // administrator
    print_rectangle($img,array(0,295,$w-50,80),5,$black);
    $admin = $item->custom_Admin;
    if( $admin != '' ){
        $notice = sprintf("Admin: %s",$admin);
    } else {
        $notice = " ";
    }
    print_text($img,array(5,300,$w-60,80),$font1,36,0,$black,$notice);
    
    $notice = '';
    $notice = $notice . sprintf("Type : %-22s\n",trim(get_dev_type($item->type)));
    if( $item->location > 0 ){
    $notice = $notice . sprintf("Room : %-22s\n",trim(get_location_name($item->location)));
    }
    if( $item->custom_NumOfPorts != '' ){
    $notice = $notice . sprintf("Ports: %-22s\n",trim($item->custom_NumOfPorts));
    }

    if( $item->custom_Port != '' ){
    $notice = $notice . sprintf("Plug : %-22s\n",trim($item->custom_Port));
    }
    if( $item->ip != '' ){
    $notice = $notice . sprintf("IP   : %-22s\n",trim($item->ip));
    }
    if( $item->description != '' ){
    $notice = $notice . trim($item->description);
    }
          
    // technical data
    print_text_na($img,array(25,400,750,275),$font3,27,0,$black,$notice);
    
    
    // asset number
    $notice = '';
    $notice = $notice . sprintf("%s\n",trim($item->custom_Institute));
    if( $item->custom_AssetNo != '' ){
        $notice = $notice . sprintf("%s\n",trim($item->custom_AssetNo));
    }
    $notice = $notice . sprintf("%s\n",trim($item->custom_Acquired));
    
    // cluster
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
get_devices();

$print_devs = [];

// filter hosts
foreach($devices->data as $item){

    // test required files
    $itemname = trim($item->hostname);
    if( $itemname == '' ) continue;
        
    $selected = true;
        
    // filter data
    if( array_key_exists("n",$arguments) == true ){
        if( $arguments["n"] != $itemname ) $selected = false;
    }

    if( $selected == true ){
        array_push($print_devs,$item);
    }
}

// print selected hosts
list_devs($print_devs);

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
printf("        DeviceName         \n");          
printf("---------------------------\n");

// print labels
foreach($print_devs as $item){
    printf("%-25s\n",$item->hostname);
    // create label picture
    if( label_dev($item) == false ){
        echo "\n";
        printf(">>> ERROR: Unable to print: %s\n",$item->hostname);
        echo "\n";    
        exit(1);
    }
    // send it to printer
    $cmd = 'brother_ql -m ' . $model . ' -p ' . $port . ' print -l ' . $labels . ' -r 90 ' . $picname;
    system($cmd);
    printf("\n");
}

?>
