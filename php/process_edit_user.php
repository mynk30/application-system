<?php
header('Content-Type: application/json');
require_once '../php/config.php';
require_once '../php/auth.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if user exists in admin table
        $stmt = $conn->prepare("SELECT id, role FROM admin WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($result->num_rows > 0) {
            // Update admin user
            $updateQuery = "UPDATE admin SET ";
            $params = [];
            $types = "";

            if (!empty($name)) {
                $updateQuery .= "name = ?, ";
                $params[] = $name;
                $types .= "s";
            }
            if (!empty($email)) {
                $updateQuery .= "email = ?, ";
                $params[] = $email;
                $types .= "s";
            }
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateQuery .= "password = ?, ";
                $params[] = $hashedPassword;
                $types .= "s";
            }
            if (!empty($status)) {
                $updateQuery .= "status = ? ";
                $params[] = $status;
                $types .= "s";
            }

            // Remove trailing comma
            $updateQuery = rtrim($updateQuery, ', ') . " WHERE id = ?";
            $params[] = $userId;
            $types .= "i";

            // Prepare and execute update
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param($types, ...$params);

        } else {
            // Update regular user
            $updateQuery = "UPDATE users SET ";
            $params = [];
            $types = "";

            if (!empty($name)) {
                $updateQuery .= "name = ?, ";
                $params[] = $name;
                $types .= "s";
            }
            if (!empty($email)) {
                $updateQuery .= "email = ?, ";
                $params[] = $email;
                $types .= "s";
            }
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateQuery .= "password = ?, ";
                $params[] = $hashedPassword;
                $types .= "s";
            }
            if (!empty($status)) {
                $updateQuery .= "status = ? ";
                $params[] = $status;
                $types .= "s";
            }

            // Remove trailing comma
            $updateQuery = rtrim($updateQuery, ', ') . " WHERE id = ?";
            $params[] = $userId;
            $types .= "i";

            // Prepare and execute update
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to update user: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error updating user: ' . $e->getMessage()
        ]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
