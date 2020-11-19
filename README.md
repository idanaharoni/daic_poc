# Distributed Account Information Certification (DAIC) Proof of Concept

A proof of concept of DAIC, a suggested method for validating payment account information of organizations, for the purpose of BEC fraud prevention.

## How It Works

DAIC provides CFOs and Account Payable teams with the ability to validate that the account number they have on file is truly of the vendor, before issuing a payment.
The validation is performed using a certification server, or DAIC server, which hosts the updated and certified account number of the vendor.
Each company implementing DAIC chooses which server to host this information in - whether it is operated by them or by a third party.

A company implementing DAIC adds a DNS record indicating the server of their choice. 
The DNS record contains the name _daic and contains the value

```v=DAIC;cs=intelfinder.io```

where intelfinder.io is replaced by the DAIC server location.



## Components

This DAIC POC contains two components:
- DAIC Server - 
