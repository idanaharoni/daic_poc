# Distributed Account Information Certification (DAIC) Proof of Concept

A proof of concept of DAIC, a suggested method for prventing invoice scams through Business E-mail Compromised (BEC) fraud. [More information on BEC fraud](https://www.fbi.gov/scams-and-safety/common-scams-and-crimes/business-email-compromise).

DAIC provides CFOs and Account Payable teams with the ability to validate that the account number they have on file is truly of the vendor, before issuing a payment.
The validation is performed using a certification server, or DAIC server, which hosts the updated and certified account number of the vendor.

DAIC is envisioned as an open source and distributed system, giving companies the choice of privacy and control levels by enabling them to either use it as a SaaS service offered by third parties, or set up their own infrastructure.

## How It Works


Each company implementing DAIC chooses which server to host this information in - whether it is operated by them or by a third party. This is done by setting up a TXT DNS record to their domain.

Prior to issuing a payment, the sender provides a client with the company domain and account number. The client then retrieves the DNS record, extracts the DAIC server, then queries it to validate the account number.

### DAIC DNS Record

A company implementing DAIC adds a DNS record indicating the server of their choice. 
The DNS record contains the name _daic and contains the value

```v=DAIC;cs=intelfinder.io```

where intelfinder.io is replaced by the DAIC server location.

## Proof of Concept

This repository contains a proof of concept for DAIC, written in PHP, for both the client and the server.
