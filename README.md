# phpIPAM Utilities

Utilities for [phpIPAM](https://phpipam.net/), which is an open-source web IP address management application (IPAM). 

## Configuration

Copy phpipam.conf.tmp as phpipam.conf and update configuration items.

## Utilities

### Overview
* DHCP
  * phpipam2dhcpd
* DNS
  * phpipam2fzone
  * phpipam2rzone
* Labels
  * phpipam2label-ips
  * phpipam2label-devs
* Administrative
  * nmap2phpipam
  * dns2phpipam
  * updateaddresses
  * phpipam2razor

### phpipam2dhcpd

phpipam2dhcpd generates the configuration file for the ISC DHCP server. The utilitity requires read access for both API and user. Typical usage:

```bash
$ ./phpipam2dhcp [--no-vlan] <subnet>
$ ./phpipam2dhcpd 147.251.90.0/24                      # create 147.251.90.0.conf file for isc-dhcp-server
```

### phpipam2fzone

phpipam2fzone generates the forward zone file for the bind DNS server. The utilitity requires read access for both API and user. Typical usage:

```bash
$ ./phpipam2fzone [--no-vlan] [--no-header] <subnet> [<subnet> ...]
$ ./phpipam2fzone 147.251.90.0/24                      # create db.ncbr.muni.cz file for bind server
```

### phpipam2rzone

phpipam2rzone generates the reverse zone file for the bind DNS server. The utilitity requires read access for both API and user. Typical usage:

```bash
$ ./phpipam2rzone [--no-vlan] [--no-header] <subnet>
$ ./phpipam2rzone 147.251.90.0/24                      # create db.147.251.90 file for bind server
```

### phpipam2label-ips

phpipam2label-ips prints labels for IPs (hosts) on Brother QL-700 printer. This command requires properly setup [brother_ql](https://pypi.org/project/brother_ql/) command. Typical output is then:

![IP Label](/examples/ip.png)


```bash
./phpipam2label-ips -h                                 # print help page   
./phpipam2label-ips -s 147.251.84.0/24 -n wolf18.ncbr.muni.cz
```

### nmap2phpipam

nmap2phpipam populates given subnet with addresses retrieved by ARP scanning of subnet by nmap. The utilitity requires read/write access for both API and user. Typical usage:

```bash
# nmap -sP -PR -oX 147.251.90.0.nmap 147.251.90.0/24
$ ./nmap2phpipam -test 147.251.90.0/24                 # test mode - do not update IPAM
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

### phpipam2razor

phpipam2razor generates list of hosts with razor classes. The utilitity requires read access for both API and user. Typical usage:

```bash
$ ./phpipam2razor 147.251.90.0/24 > nodes
```

