<?php
$record = ['record_id'=> array('1'=>'﻿Patient ID','2'=>'Patient ID'),
'gender'=> array('1'=>'Gen','2'=>'g','3'=>'Gender'),
'height_cm'=>'Length',
'weight_kg'=>'Weight',
'pregnant'=>'Pregnant',
'pregnancy_duration_weeks'=>'Months Pregnant',
'date_diagnosis'=>'Date of diagnosis'];

$path = 'input.csv';
// Parse the rows
$rows = [];
$handle = fopen($path, "r");
while (($row = fgetcsv($handle, 0, ";")) !== false) {
   // while (($row = fgetcsv($handle)) !== false) {
    $rows[] = $row;
}

fclose($handle);
$newHeader = [];
$original = $rows;
$headers = array_shift($rows);
foreach($headers as $key=>$value){
   foreach($record as $k=>$v){
        if(is_array($record[$k])){
            $search_key = array_search($value, $record[$k]);
            if(!empty($search_key)){
                array_push($newHeader, ['index'=>$key,'header'=>$k]);
                break;
            }
        }else{
            $search_key = array_search($value, $record);
            if(!empty($search_key)){
                array_push($newHeader, ['index'=>$key,'header'=>$search_key]);
                break;
            }
        }
   } 
}

$newBody = [];
$cnt = 0;
    foreach($rows as $key=>$value){
        if($cnt < count($rows)){
            foreach($newHeader as $hkey=>$hvalue){
                foreach($value as $k=>$v){
                    if($hvalue['index'] == $k){
                        switch ($hvalue['header']){
                            case 'gender':
                                if(strtolower($v) === 'male'){
                                    $newBody[$cnt][$hkey] = '1';
                                }else{
                                    $newBody[$cnt][$hkey] = '2';
                                }
                                break;
                            case 'height_cm':
                                if(is_numeric($v)){
                                    $newBody[$cnt][$hkey] = $v*100; 
                                }else{
                                    $newBody[$cnt][$hkey] = $v;
                                }
                                break;
                            case 'pregnant':
                                if(strtolower($v) === 'yes'){
                                    $newBody[$cnt][$hkey] = '1';
                                }else{
                                    $newBody[$cnt][$hkey] = '0';
                                }
                                break;
                            case 'pregnancy_duration_weeks':
                                if(is_numeric($v)){
                                    $newBody[$cnt][$hkey] = $v*4;
                                }else{
                                    $newBody[$cnt][$hkey] = $v;
                                }
                                break;
                            case 'date_diagnosis':
                                $timestamp = strtotime($v);
                                $newBody[$cnt][$hkey] = date("Y-m-d", $timestamp);
                                break;
                            default:
                            $newBody[$cnt][$hkey] = $v;
                            break;
                        }
                    }
                }
             $newHeaderRow[0][$hkey] = $hvalue['header'];
        }
        $cnt++;
        
    }
}
$newCsvData = array_merge($newHeaderRow,$newBody);
$path = 'output.csv';
$fp = fopen($path, 'w'); // open in write only mode (write at the start of the file)
foreach ($newCsvData as $row) {
    $file = fputcsv($fp, $row);
}
if($file === false){
    echo 'Problem while creating file';
}else{
    echo 'File created successfully';
}
fclose($fp);
?>
