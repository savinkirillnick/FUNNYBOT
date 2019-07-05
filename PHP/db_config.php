<?PHP

define ("DBHOST", "localhost");
define ("DBNAME", "database");
define ("DBUSER", "user");
define ("DBPASS", "pass");

$db = new db;
$db->query("SET NAMES utf8");

?>
