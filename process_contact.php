<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $number = htmlspecialchars($_POST['number']);
    $message = htmlspecialchars($_POST['message']);
    $entry = date('Y-m-d') . " | $name | $number | $message\n";
    file_put_contents('contacts.txt', $entry, FILE_APPEND);
    header('Location: Homepage.php?success=1');
    exit;
}
?>