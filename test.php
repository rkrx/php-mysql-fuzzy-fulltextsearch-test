<?php
require_once 'src/Converter.php';
require_once 'src/Database.php';

$converter = new Converter();
$database = new Database('mysql:host=127.0.0.1;dbname=test', 'root', '', $converter);

$database->truncate();

$content = file_get_contents('https://www.ncjrs.gov/txtfiles/161839.txt');
$lines = array_filter(explode("\n", $content), function ($sentence) { return strlen(trim($sentence)) > 40; });

foreach($lines as $line) {
	$database->insert($line);
}

$data = $database->search('probablity');
print_r($data);