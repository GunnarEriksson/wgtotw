WGTOTW
======

This project is the final assignment in the phpmvc course held by Blekinge Institute
of Technology (BTH). It is a discussion forum where you can join to discuss
everything concerning landscape photography. The forum is built with the Anax-MVC
using the module CDatabase for database connection and CForm for form generation.


Install Package
---------------
* Clone the project by following this link: https://github.com/GunnarEriksson/wgtotw.git
* Run composer and use the composer update command to install the dependencies. The
composer could be installed from https://getcomposer.org/
* Go to the file app/config/database_mysql.php and enter your database connection details.
* Go to the file webroot/.htaccess and enter the base url in Rewrite to your own base URL.


Install Database
----------------
To install the database, you could choose between two alternatives.

Alternative 1
* Open MySQL Workbench or an other tool you prefer.
* Create a new SQL tab for executing queries.
* Create a database with the command `CREATE DATABASE IF NOT EXISTS <you db name>`
* Copy the text in the file db/sqldbworkbench.sql
* Paste the text in SQL tab in MySQL Workbench.
* Execute the statements in MySQL Workbench.
* Create an administrator with the acronym admin.

Alternative 2
* Import SQL tables from folder db/sqlimport.sql to your SQL host using the import
feature. This will import all the used tables.
* Create an administrator with the acronym admin.


Use WGTOTW
----------
* Create a user
* Edit your own profile.
* Ask questions.
* Answer questions.
* Comment questions and answers.
* Vote others questions, answers and comments.
* As author of a question, accept answers.
* Questions is categorized with Tags.
* Point systems for users. User get diffrent points when asking questions, answer
questions, giving comments, voting and accepts answers.


License
------------------

This software is free software and carries a MIT license.



Use of external libraries
-----------------------------------

The following external modules are included and subject to its own license


### Modernizr
* Website: http://modernizr.com/
* Version: 2.6.2
* License: MIT license
* Path: included in `webroot/js/modernizr.js`



### PHP Markdown
* Website: http://michelf.ca/projects/php-markdown/
* Version: 1.4.0, November 29, 2013
* License: PHP Markdown Lib Copyright © 2004-2013 Michel Fortin http://michelf.ca/
* Path: included in `3pp/php-markdown`




History
-----------------------------------


###History for Anax-MVC

v1.0 (2016-10-30)

* First version of WGTOTW



```
 .  
..:  Copyright (c) 2016 Gunnar Eriksson
```
