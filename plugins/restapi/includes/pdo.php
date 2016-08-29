<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

/**
 * Class PDO.
 */
class PDO extends \PDO
{
    public static function getConnection()
    {
        $dbhost = $GLOBALS['database_host'];
        $dbuser = $GLOBALS['database_user'];
        $dbpass = $GLOBALS['database_password'];
        $dbname = $GLOBALS['database_name'];
		
        # parche para evitar un segmentation fault
        # http://php.net/manual/es/ref.pdo-mysql.connection.php (buscando: PDO::MYSQL_ATTR_INIT_COMMAND
        #$dbh = new \PDO("mysql:host=$dbhost;dbname=$dbname;charset=UTF8;", $dbuser, $dbpass,
        #    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8';"));
        $dbh = new \PDO("mysql:host=$dbhost;dbname=$dbname;charset=UTF8;", $dbuser, $dbpass);

        return $dbh;
    }
}
