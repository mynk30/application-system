<?php
require_once '../php/db.php';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $link = mysqli_real_escape_string($conn, $_POST['link']);

    // Handle Image Upload
    $imageName = 'default.jpg';
    if (!empty($_FILES['image']['name'])) {
        $imageName = uniqid('article_') . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $uploadPath = '../uploads/articles/' . $imageName;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);
    }

    // Insert into DB
    $query = "INSERT INTO articles (title, description, image, link) 
              VALUES ('$title', '$description', '$imageName', '$link')";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Article Added Successfully!'); window.location.href='manage_articles.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<form method="POST" enctype="multipart/form-data" class="mt-5">
    <label>Title</label>
    <input type="text" name="title" required class="form-control"><br>

    <label>Description</label>
    <textarea name="description" class="form-control" required></textarea><br>

    <label>Link</label>
    <input type="text" name="link" class="form-control"><br>

    <label>Image</label>
    <input type="file" name="image" class="form-control"><br>

    <button type="submit" class="btn btn-primary">Add Article</button>

</form>

<?php require_once '../includes/footer.php'; ?>