<?php
if (isset($_SERVER['REMOTE_ADDR'])) {
    if ($_SERVER['REMOTE_ADDR'] == '::1') {
        return [
            'dsn'             => "mysql:host=localhost;dbname=wgtotw;",
            'username'        => "root",
            'password'        => "",
            'driver_options'  => [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"],
            'table_prefix'    => "Lf_",

            // Display details on what happens
            //'verbose' => true,

            // Throw a more verbose exception when failing to connect
            //'debug_connect' => 'true',
        ];
    } else {
        define('DB_PASSWORD', 'uiJ7A6:g');
        return [
            'dsn'             => "mysql:host=blu-ray.student.bth.se;dbname=guer16;",
            'username'        => "guer16",
            'password'        => DB_PASSWORD,
            'driver_options'  => [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"],
            'table_prefix'    => "Lf_",
        ];
    }
}
