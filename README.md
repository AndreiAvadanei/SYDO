#SYDO - Secure Your Data by Obscurity
* __Version__ : 0.1 Alpha
* __Website__ : [WorldIT.info](http://www.worldit.info)
* __Contact__ : andrei [at] worldit [dot] info

##Description

> SYDO aims to protect your data stored in SQL Databases with a built-in interface for SQL function (for now MySQL), that helps you to store and manage your data in a safer manner. 

> * How many times you've made a compromise with your hosting provider because you have no trust in his privacy rules? 
> * How many times you don't know what is hosting provider aim? 

> This tool encrypt your data with AES, based on random keys, and then are sended to SQL database. SYDO create special hashes (if needed) that are sended to a safe webserver (with SYDO Hash Center installed and configured) which stores only the real keys (used for decryption) and the special keys used for identification. Thus, your data is a litle bit safer because you cannot understand encrypted rows and hackers cannot access hashes that are used for decryption. Ofcourse, full access is an option for them, but this is trickier. 


##Features
### v0.1 
  - Column encryption based on special key manufactured on the fly
  - Table encryption based on special key manufactured on the fly
  - Special token + IP authentication

##Help

##Installation

##TODO

##License