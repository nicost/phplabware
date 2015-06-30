# phplabware
Web-based configurable database.  

Phplabware is a web-driven database management system. Phplabware runs on a server and is accessed through a web browser. Databases can be designed within a web-interface. Data-entry takes place in forms, which can be altered using phplabware's plugin mechanism. Search results can be output in reports, which are generated based on simple HTML templates. Phplabware supports file and image uploads and allows for full-text searches in uploaded materials. A UNIX-like user and group access control mechanism allows for fine-grained read and write control at both the level of a complete database as well as individual records. The (php-based) plugin mechanism allows for easy and fast adaptation of phplabware to any specific needs.

Phplabware was developed at a number of Molecular Biology labs, and therefore ships with predefined databases targeted to the needs of Life Sciences labs. Currently, modules are available for antibodies, protocols, pdfs, pdbs, and files.

One of the design goals is that users only have to enter as little data as possible. The local pdf reprint module, for instance, (the virtual library) only requires the pdf file, and the unique identifier from Pubmed.

Apart from the provided modules, phplabware's functionality can be extended and tailored to your specific needs. A web-interface lets the system administrator easily design new tables, which are completely integrated with phplabware

Phplabware consists of a number of php scripts. It uses adodb as a database wrapper, and is developed using postgres and mysql as an SQL server (and will probably work with others too). Installation is simple, and version upgrades are completely taken care of by the php scripts.

It has been tested on both Linux (Suse, RedHat, Mandrake) and Mac OS X, and should work in Windows too. 
