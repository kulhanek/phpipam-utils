#!/usr/bin/env php
<?php
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

    if( $dev_data->data->description != '' ){
        $name = sprintf("%s (%s)",$dev_data->data->hostname,str_replace("\r",'',str_replace("\n", ' ', $dev_data->data->description)));
    } else {
        $name = sprintf("%s",$dev_data->data->hostname);
    }
    $devsdb[$id] = $name;
    return($name);
}

?>
