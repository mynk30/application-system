<?php
require_once '../php/db.php';
require_once '../php/config.php';
global $logger, $browserLogger;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Log start of application processing
        $logger->info('Starting application processing');
        $browserLogger->log('Starting application processing');

        // Get and sanitize form data
        $userName = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $address = $conn->real_escape_string($_POST['address']);
        $serviceType = $conn->real_escape_string($_POST['application_type']);
        $userId = $_SESSION['user_id'];

        // Log basic application info
        $logger->info('Application submitted by: ' . $userName);
        $logger->info('Name: ' . $userName);
        $logger->info('Email: ' . $email);
        $logger->info('Phone: ' . $phone);
        $logger->info('Address: ' . $address);
        $logger->info('Service Type: ' . $serviceType);

        $browserLogger->log('Application submitted by: ' . $userName);
        $browserLogger->log('Name: ' . $userName);
        $browserLogger->log('Email: ' . $email);
        $browserLogger->log('Phone: ' . $phone);
        $browserLogger->log('Address: ' . $address);
        $browserLogger->log('Service Type: ' . $serviceType);

        // Handle file uploads
        $uploadedFiles = [];
        $targetDir = "../uploads/";

        // Create uploads directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
            $logger->info('Created uploads directory: ' . $targetDir);
            $browserLogger->log('Created uploads directory: ' . $targetDir);
        }

        // First insert the application to get the application ID
        $stmt = $conn->prepare("INSERT INTO applications (user_id, name, email, phone, address, service_type, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");

        if ($stmt === false) {
            $logger->error('Database error: Failed to prepare application statement');
            throw new Exception('Database error: Statement preparation failed');
        }

        if (!$stmt->bind_param("isssss", $userId, $userName, $email, $phone, $address, $serviceType)) {
            $logger->error('Database error: Failed to bind application parameters');
            throw new Exception('Database error: Parameter binding failed');
        }

        if (!$stmt->execute()) {
            $logger->error('Database error: Failed to execute application statement');
            throw new Exception('Database error: Application insertion failed');
        }

        // Get the application ID
        $applicationId = $conn->insert_id;
        $logger->info('Created application with ID: ' . $applicationId);
        $browserLogger->log('Created application with ID: ' . $applicationId);

        // Now process each uploaded file
        foreach ($_FILES['document']['name'] as $key => $name) {
            // Generate unique filename by appending timestamp before extension
            $originalName = basename($name);
            $timestamp = date('YmdHis');
            $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filenameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
            $uniqueName = $filenameWithoutExt . '_' . $timestamp . '.' . $fileExtension;
            $targetFilePath = $targetDir . $uniqueName;

            if (move_uploaded_file($_FILES["document"]["tmp_name"][$key], $targetFilePath)) {
                // Get file size in bytes
                $fileSize = filesize($targetFilePath);

                // Insert into files table
                $stmt = $conn->prepare("INSERT INTO files (original_name, file_name, file_path, file_size, model_type, model_id) VALUES (?, ?, ?, ?, ?, ?)");

                if ($stmt === false) {
                    $logger->error('Database error: Failed to prepare files statement');
                    throw new Exception('Database error: Files table preparation failed');
                }

                $modelType = 'application';
                if (!$stmt->bind_param("ssssss", $originalName, $uniqueName, $targetFilePath, $fileSize, $modelType, $applicationId)) {
                    $logger->error('Database error: Failed to bind files parameters');
                    throw new Exception('Database error: Files parameters binding failed');
                }

                if (!$stmt->execute()) {
                    $logger->error('Database error: Failed to execute files statement');
                    throw new Exception('Database error: Files insertion failed');
                }

                // Log file upload
                $logger->info("File uploaded: " . $originalName . " -> " . $uniqueName);
                $browserLogger->log("File uploaded: " . $originalName . " -> " . $uniqueName);

                $uploadedFiles[] = $uniqueName;
                $logger->info('File uploaded and recorded: ' . $originalName);
                $browserLogger->log('File uploaded and recorded: ' . $originalName);
            } else {
                $error = error_get_last();
                $logger->error('File upload failed: ' . $originalName);
                $logger->error('Error details: ' . print_r($error, true));
                $browserLogger->error('File upload failed: ' . $originalName);
                $browserLogger->error('Error details: ' . print_r($error, true));
                throw new Exception('File upload failed');
            }
        }

        $logger->info('Total documents uploaded: ' . count($uploadedFiles));
        $logger->info('Uploaded files: ' . implode(', ', $uploadedFiles));
        $browserLogger->log('Total documents uploaded: ' . count($uploadedFiles));
        $browserLogger->log('Uploaded files: ' . implode(', ', $uploadedFiles));

        // Commit transaction
        $conn->commit();

        // Log success
        $logger->info('Application and files successfully saved');
        $browserLogger->log('Application and files successfully saved');

        // Redirect with success message
        if($_SESSION['user_role'] == 'admin') {
            header("Location: /application-system/admin/dashboard.php?success=1&application_id=" . $applicationId);
        } else {
            header("Location: /application-system/staff/dashboard.php?success=1&application_id=" . $applicationId);
        }
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($conn->in_transaction) {
            $conn->rollback();
        }

        // Log error
        $logger->error('Application processing failed: ' . $e->getMessage());
        $browserLogger->error('Application processing failed: ' . $e->getMessage());

        // Redirect with error message
        if($_SESSION['user_role'] == 'admin') {
            header("Location: /application-system/admin/dashboard.php?error=1");
        } else {
            header("Location: /application-system/staff/dashboard.php?error=1");
        }
        exit();
    }
}