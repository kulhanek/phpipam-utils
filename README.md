# phpIPAM Utilities

Utilities for [phpIPAM](https://phpipam.net/), which is an open-source web IP address management application (IPAM). 

## Configuration

Copy phpipam.conf.tmp as phpipam.conf and update configuration items.

## Utilities

### nmap2phpipam

nmap2phpipam populates given subnet with addresses retrieved by ARP scanning of subnet by nmap. The utilitity requires read/write access for both API and user. Typical usage:

```bash
# nmap -sP -PR -oX 147.251.90.0.nmap 147.251.90.0/24
$ ./nmap2phpipam -test 147.251.90.0/24                  # test mode - do not update IPAM
$ ./nmap2phpipam 147.251.90.0/24
```

### dns2phpipam

dns2phpipam populates given subnet with addresses retrieved by scanning of DNS server. The utilitity requires read/write access for both API and user. Typical usage:

```bash
$ ./dns2phpipam -test 147.251.90.0/24                  # test mode - do not update IPAM
$ ./dns2phpipam 147.251.90.0/24
```

### updateaddresses

updateaddresses does mass update of addresses according to the user rules provided in the code. The utilitity requires read/write access for both API and user. Typical usage:

```bash
$ ./updateaddresses 147.251.90.0/24
```

### phpipam2dhcpd

phpipam2dhcpd generate configuration file for the ISC DHCP server. The utilitity requires read access for both API and user. Typical usage:

```bash
$ ./phpipam2dhcpd 147.251.90.0/24                      # create 147.251.90.0.conf file for isc-dhcp-server
```

### phpipam2label-ips

phpipam2label-ips print IP labels on Brother QL-700 printer. Typical example is
![IP Label](/examples/ip.png | width=500)


```bash
./phpipam2label-ips -h                                 # print help page   
./phpipam2label-ips -s 147.251.84.0/24 -n wolf18.ncbr.muni.cz
```
