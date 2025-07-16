<?php
require_once '../php/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userName = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $serviceType = $conn->real_escape_string($_POST['application_type']);

    $uploadedFiles = [];
    $targetDir = "../uploads/";

    foreach ($_FILES['document']['name'] as $key => $name) {
        $documentName = basename($name);
        $targetFilePath = $targetDir . $documentName;
        if (move_uploaded_file($_FILES["document"]["tmp_name"][$key], $targetFilePath)) {
            $uploadedFiles[] = $documentName;
        }
    }

    $documentNames = implode(",", $uploadedFiles);

    $stmt = $conn->prepare("INSERT INTO applications (name, email, phone, address, service_type, documents, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssssss", $userName, $email, $phone, $address, $serviceType, $documentNames);

    if ($stmt->execute()) {
        header("Location: ../admin/dashboard.php?success=1");
        exit;
    } else {
        echo "Failed to submit application.";
    }
}


?>