<?php
//mssqlserver连接
$server = !empty($_POST['host'])? trim(strip_tags($_POST['host'])): (!empty($argv[1])? $argv[1] : '');
$username = !empty($_POST['user'])? trim(strip_tags($_POST['user'])) : (!empty($argv[2])? $argv[2] : '');
$password = !empty($_POST['password'])? trim(strip_tags($_POST['password'])) : (!empty($argv[3])? $argv[3] : '');
$database = !empty($_POST['dbname'])? trim(strip_tags($_POST['dbname'])) : (!empty($argv[4])? $argv[4] : '');
$tablename = !empty($_POST['tablename'])? trim(strip_tags($_POST['tablename'])) : (!empty($argv[5])? $argv[5] : '');
$tablename = strtolower($tablename);
$connection = mssql_connect($server, $username, $password);
//数据库连接错误处理
if(!$connection) {
	die("Couldn't connect");
}
 
if (!mssql_select_db($database, $connection)) {
	die('Failed to select DB');
}

//查询数据库
$sql = "SELECT * FROM $tablename";
$query_result = mssql_query($sql, $connection);
//获取表字段
$row = mssql_fetch_assoc($query_result);
$rowKeys = array_keys($row);
mssql_free_result($query_result);
mssql_close($connection);

//write excel
//输出Excel文件头，可把user.csv换成你要的文件名 
$name = $tablename.'_'.date('YmdH');
header('Content-Type: application/vnd.ms-excel;charset=utf-8');  
header('Content-Disposition: attachment;filename="'.$name.'.csv"');  
header('Cache-Control: max-age=0');     
//从数据库中获取数据，为了节省内存，不要把数据一次性读到内存，从句柄中一行一行读即可
// 打开PHP文件句柄
$path = '/usr/lib/ckan/default/src/ckan/ckan/public/base/';
$fp = fopen($path.$name.'.csv', 'w+'); 
   
// 输出Excel列名信息
foreach ($rowKeys as $i => $v) {  
    //CSV的Excel支持GBK编码，一定要转换，否则乱码  
    $head[$i] = iconv('utf-8', 'gb2312', $v);  
}    
// 将数据通过fputcsv写到文件句柄  
fputcsv($fp, $head);   
fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
// 计数器  
$cnt = 0;  
// 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小  
$limit = 100000;    
// 逐行取出数据，不浪费内存
$query_result3 = mssql_query($sql, $connection);
while ($frow = mssql_fetch_row($query_result3)) {   
    $cnt ++;
    if ($limit == $cnt) { 
        //刷新一下输出buffer，防止由于数据过多造成问题  
        ob_flush();  
        flush();  
        $cnt = 0;  
    }  
    foreach ($frow as $i => $v) {  
		if ($i == 0) {
			//mssql guid field!!!
			$v = mssql_guid_string($v);
		} 
        //$frow[$i] = iconv('utf-8', 'gb2312', $v);
		$frow[$i] = $v;
    }
	//保存到csv文件
    fputcsv($fp, $frow);
}


//api ckan curl 1.create dataset 2.create resource
//1.create datasets
//public http header
$httpheader = array(
	'Content-Type' => 'application/x-www-form-urlencoded',
	'Authorization' => 'Authorization:e9ca9f83-0661-4c29-afee-60f8b3244d96'
);
//dataset post array
$curlpost = array(
	'name' => $name,
	'title' => $name,
	'owner_org' => 'yunkai'
);
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, "http://10.0.13.23/api/3/action/package_create");
curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost);
$returnoutput = curl_exec($ch);
curl_close($ch);
//get datasetid
$datasetres = json_decode($returnoutput, true);
$datasetid = $datasetres['result']['id'];
//2.create resource
$resourcename = $name.'_resource'.date('YmdHis');
$pathtofile = realpath($name.'.csv');
$resourcecurlpost = array(
	'package_id' => $datasetid,
	'url' => 'http://10.0.13.23/base/'.$name.'.csv',
	'name' => $resourcename,
	'upload' => $pathtofile
);
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, "http://10.0.13.23/api/3/action/resource_create");
curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $resourcecurlpost);
$resourceoutput = curl_exec($ch);
curl_close($ch);
//get resourceid
$resouceres = json_decode($resourceoutput, true);
$resourceid = $resouceres['result']['id'];
/*
//3.create datastore
$records = json_encode($result);
$datastorecurlpost = array(
	'resource_id' => $resourceid,
	'records' => $records
);
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, "http://10.0.13.23/api/3/action/datastore_create");
curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $datastorecurlpost);
$output = curl_exec($ch);
curl_close($ch);
//4.create resource view
$resourcename = 'resview'.date('YmdHis');
$resviewcurlpost = array(
	'resource_id ' => $resourceid,
	'title' => $resourcename,
	'view_type ' => 'recline_grid_view',
	'config' => $records
);
$ch = curl_init(); 
curl_setopt($ch, CURLOPT_URL, "http://10.0.13.23/api/3/action/resource_view_create");
curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $resviewcurlpost);
$resviewoutput = curl_exec($ch);
curl_close($ch);
*/
?>