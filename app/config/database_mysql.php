<?php
if (isset($_SERVER['REMOTE_ADDR'])) {
    if ($_SERVER['REMOTE_ADDR'] == '::1') {
        return [
            'dsn'             => "mysql:host=localhost;dbname=wgtotw;",
            'username'        => "root",
            'password'        => "",
            'driver_options'  => [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"],
            'table_prefix'    => "lf_",

            // Display details on what happens
            //'verbose' => true,

            // Throw a more verbose exception when failing to connect
            //'debug_connect' => 'true',
        ];
    } else {
        define('DB_PASSWORD', '<Your password for DB>');
        return [
            'dsn'             => "mysql:host=<Your path and db name>;",
            'username'        => "<Your user name>",
            'password'        => DB_PASSWORD,
            'driver_options'  => [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"],
            'table_prefix'    => "lf_",
        ];
    }
}
