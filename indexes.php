  

<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "photo_album_single";
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$conn->query("CREATE TABLE IF NOT EXISTS photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100),
  description TEXT,
  filename VARCHAR(255),
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = $conn->query("SELECT filename FROM photos WHERE id=$id");
    if ($row = $result->fetch_assoc()) {
        if (file_exists($row['filename'])) unlink($row['filename']);
    }
    $conn->query("DELETE FROM photos WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $conn->query("UPDATE photos SET title='$title', description='$description' WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["photo"])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $dir = "uploads/";

    if (!is_dir($dir)) mkdir($dir);
    $file = $dir . time() . "_" . basename($_FILES["photo"]["name"]);

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $file)) {
        $stmt = $conn->prepare("INSERT INTO photos (title, description, filename) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $description, $file);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<p style='color:red;text-align:center;'>Error uploading file!</p>";
    }
}

$result = $conn->query("SELECT * FROM photos ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Photo Album - Thicien</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #f3f3f3;
  margin: 0;
  padding: 0;
}
header {
  background: #007bff;
  color: white;
  text-align: center;
  padding: 15px;
}
h1 { margin: 0; }
.container {
  max-width: 900px;
  margin: 20px auto;
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
form {
  text-align: center;
  margin-bottom: 30px;
}
form input, form textarea {
  width: 80%;
  padding: 8px;
  margin: 8px 0;
  border-radius: 5px;
  border: 1px solid #ccc;
}
form button {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
}
form button:hover {
  background: #0056b3;
}
.gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
}
.photo {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  text-align: center;
  overflow: hidden;
  padding-bottom: 10px;
}
.photo img {
  width: 100%;
  height: 200px;
  object-fit: cover;
}
.photo h3 {
  margin: 10px 0 5px;
}
.photo p {
  padding: 0 10px;
  color: #555;
}
.photo a {
  display: inline-block;
  margin: 5px;
  padding: 5px 10px;
  border-radius: 5px;
  color: white;
  text-decoration: none;
}
.delete-btn { background: #dc3545; }
.edit-btn { background: #17a2b8; }
.edit-btn:hover { background: #138496; }
.delete-btn:hover { background: #c82333; }
footer {
  text-align: center;
  padding: 10px;
  color: #777;
}
.edit-form {
  text-align: center;
  margin: 20px;
  background: #f8f9fa;
  padding: 15px;
  border-radius: 10px;
}
.edit-form input, .edit-form textarea {
  width: 80%;
  margin: 5px 0;
  padding: 8px;
}
</style>
</head>
<body>
<header>
  <h1>📸 My Photo Album - Thicien</h1>
</header>

<div class="container">
  <h2 style="text-align:center;">Add New Photo</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Photo title" required><br>
    <textarea name="description" placeholder="Photo description" required></textarea><br>
    <input type="file" name="photo" accept="image/*" required><br>
    <button type="submit">Upload Photo</button>
  </form>

  <h2 style="text-align:center;">Gallery</h2>
  <div class="gallery">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="photo">
        <img src="<?= htmlspecialchars($row['filename']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <a href="?edit=<?= $row['id'] ?>" class="edit-btn">Edit</a>
        <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Delete this photo?');">Delete</a>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php if (isset($_GET['edit'])):
  $id = intval($_GET['edit']);
  $photo = $conn->query("SELECT * FROM photos WHERE id=$id")->fetch_assoc();
?>
<div class="edit-form">
  <h2>Edit Photo</h2>
  <form method="POST">
    <input type="hidden" name="edit_id" value="<?= $photo['id'] ?>">
    <input type="text" name="title" value="<?= htmlspecialchars($photo['title']) ?>" required><br>
    <textarea name="description" required><?= htmlspecialchars($photo['description']) ?></textarea><br>
    <button type="submit">Save Changes</button>
    <a href="<?= $_SERVER['PHP_SELF'] ?>" style="text-decoration:none;background:#6c757d;color:white;padding:8px 12px;border-radius:5px;">Cancel</a>
  </form>
</div>
<?php endif; ?>

<footer>
  <p>© <?= date("Y") ?> Thicien | Photo Album</p>
</footer>

</body>
</html>

