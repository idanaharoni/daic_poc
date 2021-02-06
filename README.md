# Distributed Account Information Certification (DAIC) Proof of Concept

A proof of concept of DAIC, a suggested method for prventing invoice scams through Business E-mail Compromised (BEC) fraud. [More information on BEC fraud](https://www.fbi.gov/scams-and-safety/common-scams-and-crimes/business-email-compromise).

DAIC provides CFOs and Account Payable teams with the ability to validate that the account number they have on file is truly of the vendor, before issuing a payment.
The validation is performed using a certification server, or DAIC server, which hosts the updated and certified account number of the vendor.

DAIC is envisioned as an open source and distributed system, giving companies the choice of privacy and control levels by enabling them to either use it as a SaaS service offered by third parties for ease of use, or to set up their own infrastructure.

## How It Works

Each company implementing DAIC chooses which server to host the certified account information in. This is done by setting up a TXT DNS record on their domain. 
The certified account information is then added to the chosen DAIC server.

Prior to issuing a payment, the sender inputs the recipient's domain name and account number to a DAIC client. The client then retrieves the DNS record, extracts the DAIC server, then queries it to validate the account number. The server checks whether the provided account number matches the account number on file that is associated with the domain. The server response dictates to the client whether the account information is accurate, incorrect or that the domain is not on file.

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

The POC was written in PHP, with MongoDB used to store records. These technologies were chosen for the proof-of-concept due to the author's familiarity with them and should not be indicative of the technologies used in an actual implementation of the concept.
The recommended enviornment is Ubuntu, with Apache, MongoDB, PHP and Composer installed.

It is necessary to install MongoDB drivers in the project using PHP's Composer. In `./server/lib/` run the following command:

```composer install```

### DAIC server

The DAIC server listens for connections on the a specified port, by default port 1380. 
While most queries in DAIC should not require authentication, the server can be configured to only allow queries for authenticated users. 
The server can also be configured to either allow or prevent account information updates by remote sessions. Remote updates should be enabled for servers that provide DAIC as a SaaS service and disabled when the server is managed by the company who wishes to host its certified account information.
Similarly, the server can be configured to either allow or prevent account registration on the server. Registration is necessary for updating information as well as perform queries when authetication is required. 

The aforementioned settings can be set by editing the file daic.conf in the server directory.

To run the server, execute `run.php` in the server directory and it should start listening to incoming connections.

```php run.php```

### DAIC client

Run the DAIC client using the following command:

```php poc.php [[DOMAIN]] [[ACCOUNT NUMBER]]```

For example:

```php poc.php intelfinder.io 12345678```

### Protocol

The protocol in which the DAIC client and server communicate in the POC is similar to SMTP and similar protocols.
Upon connection, the server sends the client an initial handshake of "202 Accepted".
After the initial handshake, the client performs a secondary handshake of "HELO". The server should respond with "200 Approved".
Performing a query is done by sending the server "QUERY domain:account number", for example "QUERY intelfinder.io:12345678".
The server then responds in one of the following responses:

| Response | Description |
| -------- | ----------- |
| 200 Confirmed | The domain exists in the server's database and the account information matches what is on file |
| 201 Unconfirmed | The domain exists in the server's database however the account information does not match what is on file |
| 401 Unauthorized | Guest queries are not allowed on the server |
| 404 Not found | The domain could not be found in the server's database |
| 500 Invalid format | The format of the query was invalid |
| 501 Invalid domain | The format of the domain supplied in the query was invalid |
| 502 Invalid account | The format of the account number supplied in the query was invalid |

The client then sends a "QUIT" command to disconnect the session.

The server supports additional actions, which are not part of the client POC.
When performed, if the action is successful, the server should respond with "200 Approved".
The actions are:

| Action | Description |
| ------ | ----------- |
| AUTH username:password | Authenticates the user, required for performing account information updates and if the server does not allow guest queries |
| REGISTER username:password | Registers a new user on the server |
| CREATE domain: account number | Adds a new domain to the server's database, assuming it does not already exist. The domain will be automatically associated with the authenticated user and only they will be able to later update it |
| UPDATE domain:account number | Updates a domain on the server's database, if the domain is associated with the authenticated user |

Any other command would return a "500 Unknown command" response from the server.








