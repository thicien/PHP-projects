<?php
session_start();

// --- Database connection ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "photo_album_single";
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// --- Tables creation ---
$conn->query("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  title VARCHAR(100),
  description TEXT,
  filename VARCHAR(255),
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// --- User Registration ---
if (isset($_POST['register'])) {
  $username = trim($_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $check = $conn->query("SELECT * FROM users WHERE username='$username'");
  if ($check->num_rows > 0) {
    $error = "Username already exists!";
  } else {
    $conn->query("INSERT INTO users (username, password) VALUES ('$username', '$password')");
    $success = "Account created successfully! Please log in.";
  }
}

// --- User Login ---
if (isset($_POST['login'])) {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  $result = $conn->query("SELECT * FROM users WHERE username='$username'");
  if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
      $_SESSION['user_id'] = $row['id'];
      $_SESSION['username'] = $username;
      header("Location: index.php");
      exit;
    } else {
      $error = "Invalid password!";
    }
  } else {
    $error = "User not found!";
  }
}

// --- Logout ---
if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: index.php");
  exit;
}

// --- Delete Photo ---
if (isset($_GET['delete']) && isset($_SESSION['user_id'])) {
  $id = intval($_GET['delete']);
  $user_id = $_SESSION['user_id'];
  $result = $conn->query("SELECT filename FROM photos WHERE id=$id AND user_id=$user_id");
  if ($row = $result->fetch_assoc()) {
    if (file_exists($row['filename'])) unlink($row['filename']);
  }
  $conn->query("DELETE FROM photos WHERE id=$id AND user_id=$user_id");
  header("Location: index.php");
  exit;
}

// --- Edit Photo ---
if (isset($_POST['edit_id'])) {
  $id = intval($_POST['edit_id']);
  $title = $_POST['title'];
  $description = $_POST['description'];
  $conn->query("UPDATE photos SET title='$title', description='$description' WHERE id=$id AND user_id=".$_SESSION['user_id']);
  header("Location: index.php");
  exit;
}

// --- Upload Photo ---
if (isset($_POST['upload']) && isset($_FILES["photo"]) && isset($_SESSION['user_id'])) {
  $title = $_POST['title'];
  $description = $_POST['description'];
  $dir = "uploads/";

  if (!is_dir($dir)) mkdir($dir);
  $file = $dir . time() . "_" . basename($_FILES["photo"]["name"]);

  if (move_uploaded_file($_FILES["photo"]["tmp_name"], $file)) {
    $stmt = $conn->prepare("INSERT INTO photos (user_id, title, description, filename) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $_SESSION['user_id'], $title, $description, $file);
    $stmt->execute();
  }
}

$result = isset($_SESSION['user_id']) ? $conn->query("SELECT * FROM photos WHERE user_id=".$_SESSION['user_id']." ORDER BY uploaded_at DESC") : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Photo Album - Thicien</title>
<style>
  /* --- General --- */
  body {font-family: Poppins, sans-serif; background:#f5f5f5; margin:0; padding:0;}
  header {background:#007bff; color:white; text-align:center; padding:20px; font-size:24px; font-weight:600;}
  .container {max-width:900px; margin:40px auto; background:white; padding:30px; border-radius:15px; box-shadow:0 0 15px rgba(0,0,0,0.1);}
  input, textarea {width:100%; padding:12px; margin-bottom:15px; border:1px solid #ccc; border-radius:5px; font-size:16px; transition:0.3s;}
  input:focus, textarea:focus {border-color:#007bff; outline:none;}
  button {background:#007bff; color:white; border:none; padding:12px 20px; border-radius:8px; cursor:pointer; font-size:16px; transition:0.3s;}
  button:hover {background:#0056b3;}
  button.register {background:green;}
  button.register:hover {background:#006400;}

  /* --- Gallery --- */
  .gallery {display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:20px; margin-top:30px;}
  .photo-card {background:#fafafa; border-radius:12px; padding:15px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.1); transition:0.3s;}
  .photo-card:hover {box-shadow:0 4px 12px rgba(0,0,0,0.2);}
  .photo-card img {max-width:100%; border-radius:8px; margin-bottom:10px;}
  .photo-card h3 {margin:5px 0; font-size:18px; color:#333;}
  .photo-card p {font-size:14px; color:#666; margin-bottom:10px;}
  .photo-card a {color:red; text-decoration:none; font-weight:500;}
  .photo-card a:hover {text-decoration:underline;}

  /* --- Messages --- */
  .error {color:red; text-align:center; font-weight:500; margin-bottom:15px;}
  .success {color:green; text-align:center; font-weight:500; margin-bottom:15px;}

  /* --- Footer --- */
  footer {text-align:center; margin-top:40px; color:#666; padding:20px; font-size:14px;}
</style>
</head>
<body>
<header>📸 My Photo Album - Thicien</header>

<div class="container">
<?php if (!isset($_SESSION['user_id'])): ?>
  <h2>Login / Register</h2>
  <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
  <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>

  <form method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button name="login">Login</button>
    <button name="register" class="register">Register</button>
  </form>

<?php else: ?>
  <p>Welcome, <b><?= htmlspecialchars($_SESSION['username']) ?></b> | 
  <a href="?logout" style="color:red;">Logout</a></p>

  <h2>Add New Photo</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Photo title" required>
    <textarea name="description" placeholder="Photo description" required></textarea>
    <input type="file" name="photo" accept="image/*" required>
    <button type="submit" name="upload">Upload Photo</button>
  </form>

  <h2>Your Gallery</h2>
  <div class="gallery">
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="photo-card">
        <img src="<?= htmlspecialchars($row['filename']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <a href="?delete=<?= $row['id'] ?>">Delete</a>
      </div>
    <?php endwhile; ?>
  </div>
<?php endif; ?>
</div>

<footer>© 2025 Thicien | Secure Photo Album</footer>
</body>
</html>

