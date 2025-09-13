<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=ekta_tay;charset=utf8", "root", "");
    echo "✅ DB Connected!";
} catch (PDOException $e) {
    echo "❌ DB Connection Failed: ".$e->getMessage();
}
?>
