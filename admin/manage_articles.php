<?php
require_once '../php/db.php';
require_once '../includes/header.php';
$result = mysqli_query($conn, "SELECT * FROM articles ORDER BY created_at DESC");
?>

<h2 class="mt-5">Manage Articles</h2>
<a href="add_article.php" class="btn btn-success mb-3">Add New Article</a>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Description</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <tr>
            <td><img src="../uploads/articles/<?php echo $row['image']; ?>" width="80"></td>
            <td><?php echo $row['title']; ?></td>
            <td><?php echo substr($row['description'], 0, 50) . '...'; ?></td>
            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
            <td>
                <a href="edit_article.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                <a href="delete_article.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this article?');">Delete</a>
            </td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php require_once '../includes/footer.php'; ?>
