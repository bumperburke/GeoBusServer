<?php
/**
  * @author Stefan Burke
  * @author Stefan Burke <stefan.burke@mydit.ie>
  */
date_default_timezone_set('Europe/Dublin');

/* HTTP status codes 2xx */
define ( "HTTPSTATUS_OK", 200 );
define ( "HTTPSTATUS_CREATED", 201 );
define ( "HTTPSTATUS_NOCONTENT", 204 );

/* HTTP status codes 3xx */
define ( "HTTPSTATUS_NOTMODIFIED", 304 );

/* HTTP status codes 4xx */
define ( "HTTPSTATUS_BADREQUEST", 400 );
define ( "HTTPSTATUS_UNAUTHORIZED", 401 );
define ( "HTTPSTATUS_FORBIDDEN", 403 );
define ( "HTTPSTATUS_NOTFOUND", 404 );
define ( "HTTPSTATUS_REQUESTTIMEOUT", 408 );
define ( "HTTPSTATUS_TOKENREQUIRED", 499 );

/* HTTP status codes 5xx */
define ( "HTTPSTATUS_INTSERVERERR", 500 );

/* DB Variables */
define('DB_USERNAME', 'phpMyAdmin');
define('DB_PASS', 'phpMyAdmin');
define('DB_HOST', 'phpmyadmin.cqckkzgczjdc.eu-west-1.rds.amazonaws.com');
define('DB_NAME', 'geoBus');
define('DB_PORT', 3306);
define('DB_VENDOR', 'mysql');

/*define('DB_USERNAME', "pgmyadmin");
define('DB_PASS', "user=pgmyadmin password=pgmyadmin");
define('DB_HOST', "host=pgmyadmin.cqckkzgczjdc.eu-west-1.rds.amazonaws.com");
define('DB_NAME', "dbname=GeoBus");
define('PORT', "port=5432");*/

?>
