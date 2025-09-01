<?php
require_once '../includes/header.php';  
require_once '../php/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = intval($_POST['id']);
    $title       = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $link        = mysqli_real_escape_string($conn, $_POST['link']);

    // handle image upload if new file provided
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "../uploads/articles/";
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        $uploadPath = $uploadDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
            $image = $imageName;
        }
    }

    // build update query
    if ($image) {
        $sql = "UPDATE articles 
                SET title='$title', description='$description', link='$link', image='$image' 
                WHERE id=$id";
    } else {
        $sql = "UPDATE articles 
                SET title='$title', description='$description', link='$link' 
                WHERE id=$id";
    }

    if (mysqli_query($conn, $sql)) {
        echo "<div class='alert alert-success text-center'>Article updated successfully.</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>Error updating article: " . mysqli_error($conn) . "</div>";
    }
}

// fetch article for display (GET or after POST)
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($id <= 0) {
    die("Invalid Article ID.");
}

$result = mysqli_query($conn, "SELECT * FROM articles WHERE id = $id");
$article = mysqli_fetch_assoc($result);

if (!$article) {
    die("Article not found.");
}
?>

<!-- Page Content -->
<div class="container mt-5">
    <h2>Edit Article</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $article['id']; ?>">

        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" 
                   value="<?php echo htmlspecialchars($article['title']); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($article['description']); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Link</label>
            <input type="text" name="link" class="form-control" 
                   value="<?php echo htmlspecialchars($article['link']); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Current Image</label><br>
            <?php if (!empty($article['image'])) { ?>
                <img src="../uploads/articles/<?php echo $article['image']; ?>" width="120" class="mb-2"><br>
            <?php } else { ?>
                <span class="text-muted">No image</span><br>
            <?php } ?>
            <input type="file" name="image">
        </div>

        <button type="submit" class="btn btn-primary">Update Article</button>
        <a href="manage_articles.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>
