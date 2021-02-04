# Distributed Account Information Certification (DAIC) Proof of Concept

A proof of concept of DAIC, a suggested method for prventing invoice scams through Business E-mail Compromised (BEC) fraud. [More information on BEC fraud](https://www.fbi.gov/scams-and-safety/common-scams-and-crimes/business-email-compromise).

DAIC provides CFOs and Account Payable teams with the ability to validate that the account number they have on file is truly of the vendor, before issuing a payment.
The validation is performed using a certification server, or DAIC server, which hosts the updated and certified account number of the vendor.

DAIC is envisioned as an open source and distributed system, giving companies the choice of privacy and control levels by enabling them to either use it as a SaaS service offered by third parties for ease of use, or to set up their own infrastructure.

## How It Works

Each company implementing DAIC chooses which server to host the certified account information in. This is done by setting up a TXT DNS record on their domain. 
The certified account information is then added to the chosen DAIC server.

Prior to issuing a payment, the sender inputs the recipient's domain name and account number to a DAIC client. The client then retrieves the DNS record, extracts the DAIC server, then queries it to validate the account number. The server then checks its records whether the provided account number fits the domain on file. The server response dictates to the client whether the account information is accurate, incorrect or that the domain is not on file.

Due to its open source nature, the client can be a web interface, local software or an embedded feature in a more robust solution.

See process below:

![DAIC checking process](https://intelfinder.io/wp-content/uploads/2021/02/daic.png)

### DAIC DNS Record

A company implementing DAIC adds a DNS record indicating the server of their choice. 
The DNS record contains the name _daic and contains the value

```v=DAIC;cs=intelfinder.io```

where intelfinder.io is replaced by the DAIC server location.

## Proof of Concept

This repository contains a proof of concept for DAIC, for both a DAIC client and server.
The functionality is limited and is used only to showcase the concept.

### Installation

The POC was written in PHP, with MongoDB used to store records. These technologies were chosen for the proof-of-concept because I'm used to them and should not be indicative of the technologies used in an actual implementation of the concept.
The recommended enviornment is Ubuntu, with Apache, MongoDB, PHP and Composer installed.

It is necessary to install MongoDB drivers in the project using PHP's Composer. In ./server/lib/ run the following command:

```composer install```

Note that DAIC server has a configuration file at ./server/daic.conf which you may want to review.

### DAIC server

To run the DAIC server, run 






