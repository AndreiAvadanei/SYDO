#SYDO - Secure Your Data by Obscurity
* __Version__ : 0.1 Alpha
* __Website__ : [WorldIT.info](http://www.worldit.info)
* __Contact__ : andrei [at] worldit [dot] info

##Description

> SYDO aims to protect your data that is stored in SQL Databases with a built-in interface for SQL functions (for now MySQL), that helps you to store and manage your data in a safer manner. 

> * How many times have you made a compromise with your hosting provider because you don't trust his privacy policy? 
> * How many times you don't know what is the hosting provider's aim? 

> This tool encrypts your data with AES, based on random keys, which is then sent to a SQL database. SYDO creates special hashes (if needed) that are sent to a safe webserver (which has SYDO Hash Center installed and configured), which stores only the real keys (used for decryption) and the special keys used for identification. Thus, your data is a litle bit safer because you cannot understand encrypted rows and hackers cannot access hashes that are used for decryption. Of course, full access is an option for them, but that is trickier. 


##Features
### v0.1 
  - Column encryption based on special key manufactured on the fly
  - Table encryption based on special key manufactured on the fly
  - Special token + IP authentication

##Help

##Installation

##TODO
- support for various SQL interfaces
- support for encrypting whole tables/databases (encrypt even tables name and table columns)
- support data statistics, request information
- multiple website management for SYDO Hash Center
- encache-ing (support for set/get data even if they weren't stored/removed by SYDO Hash Center)
- encrypted communication
- P2P Hash Server Service
- PGP Authentication/encryption
- RESTful API Communication
- understanding sql queries
- Anti-DOS for SYDO Hash Center
- learn your query patterns and understand when your query is modified and you face with an SQL Injection attack

##License