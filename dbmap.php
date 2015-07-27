
<?php

$dbNames = array(
    "\\\\DATASERVER\\DATA\\04. Well services\\17. WIS 2.0\Software Engineers\\Cerberus\\Wells.mdb",
);

$wisMap;
$cerberusMap;
$tableNames;
$goodArr;

foreach($dbNames as $dbName){
	$odbc['connection'] = odbc_connect("Driver={Microsoft Access Driver (*.mdb)};Dbq=$dbName", "", "");
	$result = odbc_tables($odbc['connection']);
	$tables = array();
	while (odbc_fetch_row($result)){
		if(odbc_result($result,"TABLE_TYPE") == "TABLE")
	 	{
	 		$tables[] = odbc_result($result,"TABLE_NAME");
		}
	}

	//foreach tables get columnnames
	foreach ($tables as $table) {
		/* Select Which Table */
		/* Fetch The Fieldnames into an Array */
		if($result = odbc_exec($odbc['connection'],"select * from [" . $table ."]"))
		{
		    for($i = 1;$i <= odbc_num_fields($result);$i++)
		    {
		    	//convert db name
		    	$convertDbName = substr($dbName, strrpos( $dbName, '\\' )+1 );
		    	$convertDbName = preg_replace('/\\.[^.\\s]{3,4}$/', '', $convertDbName);
		    	$convertDbName = strtolower($convertDbName);
		    	$convertDbName = str_replace(' ', '_', $convertDbName);
		    	$convertDbName = preg_replace('/[^a-zA-Z0-9\']/', '_', $convertDbName);

		    	//convert table name
		    	$convertTableName = strtolower($table);
		    	$convertTableName = str_replace(' ', '_', $convertTableName);
		    	$convertTableName = preg_replace('/[^a-zA-Z0-9\']/', '_', $convertTableName);
		    	$convertTableName = "cerberus" . "_" . $convertDbName . "_" . $convertTableName;

		    	//convert columnname
		    	$columnName = odbc_field_name($result,$i);
		    	$convertColumnName = strtolower($columnName);
		    	$convertColumnName = str_replace(' ', '_', $convertColumnName);
		    	$convertColumnName = preg_replace('/[^a-zA-Z0-9\']/', '_', $convertColumnName);
		    	
		    	$cerberusMap[$dbName][$table][] = odbc_field_name($result,$i);
		      	$wisMap[$convertTableName][] = $convertColumnName;
		    }

		    $cerberusTableCsv = implode (",", $cerberusMap[$dbName][$table]);
		    $wisTableCsv = implode (",", $wisMap[$convertTableName]);
		    $goodArr[$dbName][$table] = array($cerberusTableCsv,$convertTableName,$wisTableCsv); 

		}
		else
		{
		    exit("Error in SQL Query");
		}
	}
	/* Close The Connection */
	if(odbc_close($odbc['connection']))
	{
	    odbc_close($odbc['connection']);
	}
}

return $goodArr;
?>