<?php
include_once 'config.php';

header('Content-Type: application/json');

if(isset($_GET['brand'])) {
    $brand = $conn->real_escape_string($_GET['brand']);
    $sql = "SELECT DISTINCT model FROM vehicles WHERE make = '$brand'";
    $result = $conn->query($sql);
    
    $models = [];
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $models[] = $row['model'];
        }
    }
    
    echo json_encode($models);
} else {
    echo json_encode([]);
}
?>