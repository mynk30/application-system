<?php
require_once '../php/auth.php';
requireRole(['staff']);
require_once '../php/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Get the current profile picture
        $stmt = $conn->prepare("SELECT * FROM files WHERE model_type = 'admin' AND model_id = ? ORDER BY uploaded_at DESC LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $file = $result->fetch_assoc();
            $file_path = $file['file_path'];
            
            // Delete the file record from database
            $delete_stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $delete_stmt->bind_param("i", $file['id']);
            
            if ($delete_stmt->execute()) {
                // Delete the actual file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Update session if needed
                if (isset($_SESSION['profile_picture'])) {
                    unset($_SESSION['profile_picture']);
                }
                
                $conn->commit();
                $response = [
                    'success' => true, 
                    'message' => 'Profile picture removed successfully',
                    'default_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_name'] ?? 'User') . '&size=150&background=random'
                ];
            } else {
                throw new Exception('Failed to delete file record');
            }
        } else {
            $response = ['success' => false, 'message' => 'No profile picture found'];
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request method'];
}

echo json_encode($response);
?>
