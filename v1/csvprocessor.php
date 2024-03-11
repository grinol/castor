<?php

class CSVProcessor{
    private $record;
    private $inputFilePath;
    private $outputFilePath;
    private $startTime;

    public function __construct($record, $inputFilePath, $outputFilePath, $startTime){
        $this->record = $record;
        $this->inputFilePath = $inputFilePath;
        $this->outputFilePath = $outputFilePath;
        $this->startTime = $startTime;   
    }

    //Function to get the inputfile path 
    private function inputFilePath(){
        if(php_sapi_name() === 'cli'){
            global $argv;
            if(isset($argv[1])){
                return $argv[1];
            }else{
                return null;
            } 
        }elseif (isset($_FILES['input']) && is_uploaded_file($_FILES['input']['tmp_name'])){
            return $_FILES['input']['tmp_name'];
        } else {
            return '../files/input.csv';
        }
    }

    private function outputFilePath(){
        if(php_sapi_name() === 'cli'){
            return './castor/files/output.csv';
        }else{
            return '../files/output.csv';
        }
    }

    private function detectCSVDelimiter($csvFile){
        $delimiters = [';' => 0, ',' => 0, '|' => 0];
        $handle = fopen($csvFile, "r");
        if ($handle !== false) {
            $firstLine = fgets($handle);
            fclose($handle); 
        
            foreach ($delimiters as $delimiter => &$count) {
                $count = count(str_getcsv($firstLine, $delimiter));
            }
            return array_search(max($delimiters), $delimiters);
        }else{
            $jsonData['status'] = '404';
            $jsonData['msg'] = 'Error while opening the file for delimiters';
            echo json_encode($jsonData);
            exit;
        }
    }

    // Function to read CSV file
    private function readCSV() {
        $rows = [];
        $delimiter = $this->detectCSVDelimiter($this->inputFilePath);
        $handle = fopen($this->inputFilePath, "r");
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = $row;
            }
            fclose($handle); 
            return $rows;
        }else{
            $jsonData['status'] = '404';
            $jsonData['msg'] = 'Error while opening the file';
            echo json_encode($jsonData);
            exit;
        }
    }

    // Function to write CSV file
    private function writeCSV($data) {
        $this->outputFilePath = $this->outputFilePath();
        $fp = fopen($this->outputFilePath, 'w');
        foreach ($data as $row) {
            $file = fputcsv($fp, $row);
        }
        if($file === false){
            $jsonData['status'] = '404';
            $jsonData['msg'] = 'Problem while creating file' .PHP_EOL;
            echo json_encode($jsonData);
        }else{
            $time_end = microtime(true);
            $time = $time_end - $this->startTime;
            $jsonData['status'] = '200';
            $jsonData['msg'] = 'File created successfully for row count '. count($data) .' in '. $time .' sec'.PHP_EOL;
            echo json_encode($jsonData);
        }
        fclose($fp);
    }

    //Funtion to map header
    private function mapHeader($headers){
        $newHeader = [];
        foreach($headers as $key=>$value){
            foreach($this->record as $recordKey => $recordValue){ 
                    if(is_array($this->record[$recordKey])){
                        $search_key = array_search($value, $this->record[$recordKey ]);
                        if(!empty($search_key)){
                            array_push($newHeader, ['index'=>$key,'header'=>$recordKey ]);
                            break;
                        }
                    }else{
                        $search_key = array_search($value, $this->record);
                        if(!empty($search_key)){
                            array_push($newHeader, ['index'=>$key,'header'=>$search_key]);
                            break;
                        }
                    }
            } 
        }
        if(!empty($newHeader)){
            return $newHeader;
        }else{
            $jsonData['status'] = '404';
            $jsonData['msg'] = 'Error while reading the header in the file';
            echo json_encode($jsonData);
            exit;
        }
    }

    //Function to convert Data based on header
    private function convertData($rows, $headers){
        $body = [];
        $cnt = 0;
            foreach($rows as $key=>$value){
                if($cnt < count($rows)){
                    foreach($headers as $hkey=>$hvalue){
                        foreach($value as $k=>$v){
                            if($hvalue['index'] == $k){
                                $recordHeader = $hvalue['header'];
                                $body['body'][$cnt][$hkey] = $this->convertValue($recordHeader, $v);
                            }
                        }
                    $body['header'][0][$hkey] = $hvalue['header'];
                }
                $cnt++;
                
            }
        }
        if(!empty($body)){
            return $body;
        }else{
            $jsonData['status'] = '404';
            $jsonData['msg'] = 'Error while Reading the file';
            echo json_encode($jsonData);
            exit;
        }
    }

    // Cases as per requirement
    private function convertValue($header, $value) {
        switch ($header){
            case 'gender':
                if(strtolower($value) === 'male'){
                    return '1';
                }else{
                    return '2';
                }
            case 'height_cm':
                if(is_numeric($value)){
                    return $value*100; 
                }else{
                    return $value;
                }
            case 'pregnant':
                if(strtolower($value) === 'yes'){
                    return '1';
                }else{
                    return '0';
                }
            case 'pregnancy_duration_weeks':
                if(is_numeric($value)){
                    return $value*4;
                }else{
                    return $value;
                }
            case 'date_diagnosis':
                $timestamp = strtotime($value);
                return date("Y-m-d", $timestamp);
            default:
            return $value;
        }
    }

    public function processCSV(){
        $this->inputFilePath = $this->inputFilePath();
        $rows = $this->readCSV();
        $slicedHeaders = array_shift($rows);
        $headers = $this->mapHeader($slicedHeaders);
        $convertData = $this->convertData($rows,$headers);
        $newHeaderRow = $convertData['header'];
        $newBody = $convertData['body'];
        $newCsvData = array_merge($newHeaderRow,$newBody);
        $this->writeCSV($newCsvData);
    }
}

// This array defines the required structure for creating a new CSV.
// In the current implementation, the array is hardcoded, but in the future, it can be fetched dynamically from a database.

$record = [
    'record_id' => ['1'=>'﻿Patient ID','2'=>'Patient ID'],
    'gender' => ['1' => 'Gen', '2' => 'g', '3' => 'Gender'],
    'height_cm' => 'Length',
    'weight_kg' => 'Weight',
    'pregnant' => 'Pregnant',
    'pregnancy_duration_weeks' => 'Months Pregnant',
    'date_diagnosis' => 'Date of diagnosis'
];

$inputFilePath = null;
$outputFilePath = null;
$startTime = microtime(true);

$csvProcessor = new CSVProcessor($record, $inputFilePath, $outputFilePath,$startTime);
$csvProcessor->processCSV();
?>
