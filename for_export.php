<?php
date_default_timezone_set("Asia/Tashkent");
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=daily", "root", '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection error: " . $e->getMessage());
}

$query = $pdo->query("SELECT * FROM daily");
$data = $query->fetchAll(PDO::FETCH_ASSOC);

$filename = "work_data_" . date("Y-m-d_H-i-s") . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Arrived At', 'Leaved At', 'Required Work Off', 'Worked Off'));

foreach ($data as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit;
?>
