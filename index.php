<?php

$dbmap = include './dbmap.php';


$startTime = microtime(true);
ini_set('mysql.connect_timeout', 300);
ini_set('default_socket_timeout', 300);
try {
	$dsn = 'mysql:host=127.0.0.1;dbname=wis;port=3307';
	$username = 'wis';
	$password = 'Wis123';
	$options = array(
		PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
	); 

	$wisDb = new PDO($dsn, $username, $password, $options);
    //$wisDb = new PDO('mysql:127.0.0.1:3377;dbname=wis', "mwildeboer", "Mw123245");
    $wisDb->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch (PDOException $e) {
    echo $e->getMessage();
}

foreach ($dbmap as $dbName => $table) {
	$db = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$dbName;");
	$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

	foreach ($table as $accessTableName => $tableData) {
		
		//put all code in double quotes so that query's are able to execute when there are spaces within the table name
		$accesTableNames = wrapCommaSeperatedStrings($tableData[0], '"'); 
		$sql = "SELECT $accesTableNames from [$accessTableName]";

		try {
			$query = $db->prepare($sql);
			$query->execute();		
		} catch (PDOException $e) {
			echo $e->getMessage();
		}	

		//check if table contains data 
		if($query->fetch(PDO::FETCH_ASSOC) > 0)
		{
			//store rows as objects and convert them to string to put together a bulk insert statement
			$mySql = array();
			$tempDate = date('Y-m-j H:i:s');
			$myFields = explode(",", $tableData[0]);

			while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
				$row['date'] = $tempDate;
				$string = "(";
				foreach ($myFields as $field) {
					$string .=  $wisDb->quote($row[$field]) . ',';
				}
				$string .=  "'" . $row['date'] . "'";
	         	$string .= ")";
				$mySql[] = $string;
		    }
		}
		else{
			continue;
		}
		

	   //Insert new rows
	    $convertedColumnNames = wrapCommaSeperatedStrings($tableData[2], '`');

        $mysqlQuery = "INSERT INTO $tableData[1] ($convertedColumnNames,`date`) VALUES " . implode(',', $mySql);
        
        // debug output
        // if($tableData[1] == "cerberus_wells_mandrils")
        // {
       	// 	//file_put_contents('./statement.txt', $mysqlQuery); //temp
        // }

    	$secondQuery = $wisDb->prepare($mysqlQuery);
    	try {
    		$secondQuery->execute();
    	} catch (Exception $e) {
    		echo $e->getMessage();
    	}

	    //Delete old rows
	    $deleteSql =  "DELETE FROM $tableData[1] WHERE Date < '$tempDate'";
	    $deleteQuery = $wisDb->prepare($deleteSql);
	    $deleteQuery->execute();
	}
	$db = null;
}

echo "Time:  " . number_format((microtime(true) - $startTime), 4) . " Seconds\n";
$wisDb = null;

function wrapCommaSeperatedStrings($csv, $sign)
{
    return "$sign" . str_replace(",", "$sign". ',' . "$sign", $csv) . "$sign";
}
echo "done";

?>