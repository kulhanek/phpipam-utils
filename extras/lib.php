#!/usr/bin/env php
<?php

// -----------------------------------------------------------------------------
// global data

$subnetid = 0;
$data = [];
$token = "";
$hosts = [];
$subnet_data = "";
$dhcp_classes = [];
$locationsdb = [];
$devsdb = [];
$device_data = [];

// -----------------------------------------------------------------------------

// stop on any warning
// https://stackoverflow.com/questions/10520390/stop-script-execution-upon-notice-warning

function errHandle($errNo, $errStr, $errFile, $errLine) {
    $msg = "$errStr in $errFile on line $errLine";
    if ($errNo == E_NOTICE || $errNo == E_WARNING) {
        throw new ErrorException($msg, $errNo);
    } else {
        echo $msg;
    }
}

set_error_handler('errHandle');

// -----------------------------------------------------------------------------

function init_session()
{   
    global $url, $username, $passwd, $token;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "user/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$passwd");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $json = json_decode($server_output);

    $token = $json->data->token;
    
    //printf("Session token: %s\n",$token);
}

// -----------------------------------------------------------------------------

function get_subnetid()
{   
    global $url, $token, $subnetid, $subnet;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "subnets/cidr/" . $subnet ."/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        "token: $token"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $subnet_data = json_decode($server_output);
    
    if( $subnet_data->success == false ){
        printf(">>> ERROR: Unable to get subnet id for %s\n\n",$subnet);
        var_dump($subnet_data); 
        exit(1);
    }   
        
    $subnetid = $subnet_data->data[0]->id;
}

// -----------------------------------------------------------------------------

function get_subnet()
{   
    global $url, $token, $subnetid, $subnet, $subnet_data;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "subnets/" . $subnetid ."/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        "token: $token"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $subnet_data = json_decode($server_output);
       
    if( $subnet_data->success == false ){
        printf(">>> ERROR: Unable to get hosts for %s\n\n",$subnet);
        var_dump($subnet_data); 
        exit(1);
    } 
}

// -----------------------------------------------------------------------------

function get_vlan()
{   
    global $url, $token, $subnet_data, $subnet, $vlan_data;

    if( $subnet_data->data->vlanId == 0 ){
        printf("\n>>> ERROR: No VLAN defined for %s subnet!\n\n",$subnet);
        exit(1);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "vlans/" . $subnet_data->data->vlanId ."/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        "token: $token"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $vlan_data = json_decode($server_output);
        
    if( $vlan_data->success == false ){
        printf(">>> ERROR: Unable to get hosts for %s\n\n",$subnet);
        var_dump($vlan_data); 
        exit(1);
    } 
}

// -----------------------------------------------------------------------------

function get_hosts()
{   
    global $url, $token, $subnetid, $subnet, $hosts;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "subnets/" . $subnetid ."/addresses/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        "token: $token"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $hosts = json_decode($server_output);
        
    if( $hosts->success == false ){
        printf(">>> ERROR: Unable to get hosts for %s\n\n",$subnet);
        var_dump($hosts); 
        exit(1);
    } 
    
}

// -----------------------------------------------------------------------------

function get_location_name($id)
{   
    global $url, $token, $locationsdb;

    // is it in cache?
    if( array_key_exists($id, $locationsdb) == true ){
        return($locationsdb[$id]);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "tools/locations/" . $id . "/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        "token: $token"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $loc_data = json_decode($server_output);
        
    if( $loc_data->success == false ){
        printf(">>> ERROR: Unable to get location for %d\n\n",$id);
        var_dump($loc_data); 
        exit(1);
    } 
    
    $name = $loc_data->data->name;
    $locationsdb[$id] = $name;
    return($name);
}

// -----------------------------------------------------------------------------

function get_devices()
{   
    global $url, $token, $devices;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "devices/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        "token: $token"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);    

    $server_output = curl_exec ($ch);
    
    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }    

    curl_close ($ch);
    
    $devices = json_decode($server_output);
    
    if( $devices->success == false ){
        printf("\n>>> ERROR: Unable to devices!\n\n");
        var_dump($devices); 
        exit(1);
    } 
    // var_dump($devices);  
}

// -----------------------------------------------------------------------------

function get_device_name($id)
{
    if( $id == 0 ) return("");

    global $url, $token, $subnet, $devsdb;

    // is it in cache?
    if( array_key_exists($id, $devsdb) == true ){
        return($devsdb[$id]);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . "devices/" . $id . "/");
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        "token: $token"
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $server_output = curl_exec ($ch);

    if( $server_output == FALSE ){
        printf(">>> ERROR: Unable to connect to IPAM: %s\n\n",curl_error($ch));
        curl_close ($ch);
        exit(1);
    }

    curl_close ($ch);

    $dev_data = json_decode($server_output);

    if( $dev_data->success == false ){
        printf(">>> ERROR: Unable to get device %d\n\n",$id);
        var_dump($dev_data);
        exit(1);
    }

    $name = sprintf("%s",$dev_data->data->hostname);
    if( $dev_data->data->custom_Port !=  '' ){
        $name = $name . '/' . trim($dev_data->data->custom_Port);
    }
    $devsdb[$id] = $name;
    return($name);
}

// -----------------------------------------------------------------------------

function get_host_fqdn($host)
{
    global $subnet_data;

    $fqdn= trim($host->hostname);
    if ( strpos($fqdn, ".") == false ){
        $fqdn = $fqdn . "." . $subnet_data->data->custom_Domain;
    }
    return($fqdn);
}

// -----------------------------------------------------------------------------

function get_host_name($host)
{
    $names = explode(".",trim($host->hostname));
    return($names[0]);
}

// -----------------------------------------------------------------------------

function get_host_admin($host)
{
    if( $host->custom_Admin == "User" ){
        $admin = $host->custom_User;
    } else {
        $admin = $host->custom_Admin;
    }
    return(trim($admin));
}

// -----------------------------------------------------------------------------

function get_host_plug($host)
{
    $plug = $host->port;
    if( $host->deviceId != 0 ){
        $devstr = get_device_name($host->deviceId);
        if( $plug == '' ) $plug = 'Px';
        $plug = $plug . '[' . trim($devstr) . ']';
    }
    return($plug);
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

?>
