<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "photo_album_single";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$conn->query("
  CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255)
  )
");

$conn->query("
  CREATE TABLE IF NOT EXISTS photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100),
    description TEXT,
    filename VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  )
");

if (isset($_POST['register'])) {
  $username = trim($_POST['username']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
  $stmt->bind_param("ss", $username, $password);
  $stmt->execute();
  $success = "Account created successfully! You can log in now.";
}

if (isset($_POST['login'])) {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
      $_SESSION['user_id'] = $row['id'];
      $_SESSION['username'] = $row['username'];
    } else {
      $error = "Incorrect password!";
    }
  } else {
    $error = "User not found!";
  }
}

if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: indexes.php");
  exit;
}

$upload_success = false;
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
    $upload_success = true;
  }
}

if (isset($_POST['delete']) && isset($_SESSION['user_id'])) {
  $photo_id = $_POST['photo_id'];
  $stmt = $conn->prepare("SELECT filename FROM photos WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $photo_id, $_SESSION['user_id']);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) unlink($row['filename']);
  $stmt = $conn->prepare("DELETE FROM photos WHERE id=? AND user_id=?");
  $stmt->bind_param("ii", $photo_id, $_SESSION['user_id']);
  $stmt->execute();
}

if (isset($_POST['edit']) && isset($_SESSION['user_id'])) {
  $photo_id = $_POST['photo_id'];
  $title = $_POST['new_title'];
  $desc = $_POST['new_desc'];
  $stmt = $conn->prepare("UPDATE photos SET title=?, description=? WHERE id=? AND user_id=?");
  $stmt->bind_param("ssii", $title, $desc, $photo_id, $_SESSION['user_id']);
  $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Photo Album - Thicien</title>
<style>
body {font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0;}
.container {max-width: 900px; margin: 30px auto; background: white; padding: 25px; border-radius: 10px;}
h1 {color: #007bff; text-align: center;}
h2 {color: #0c0d0dff; text-align: center;}
input, textarea, button {margin: 5px 0; padding: 10px; border-radius: 5px; border: 1px solid #ccc; font-size: 14px;}
button {background: #007bff; color: white; border: none; cursor: pointer;}
button:hover {background: #0056b3;}
textarea {resize: vertical;}
.gallery {display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;}
.photo {background: #fafafa; border: 1px solid #ddd; border-radius: 8px; padding: 10px;}
.photo img {width: 100%; border-radius: 5px;}
.actions form {display: inline;}
.msg-box {text-align: center; background: #e8f5e9; padding: 20px; border-radius: 10px;}
.msg-box a {background: #007bff; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px;}
.msg-box a:hover {background: #0056b3;}

.login-form {
  max-width: 350px;
  margin: 0 auto 20px auto;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  text-align: center;
}
.login-form h1 {font-size: 24px; margin-bottom: 10px;}
.login-form h2 {font-size: 18px; margin-bottom: 20px;}
.login-form input, .login-form button {
  width: 100%;
  box-sizing: border-box;
}
.login-form button {
  margin-top: 10px;
}

.user-area {
  max-width: 500px;
  margin: 0 auto 20px auto;
  padding: 20px;
  background: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.user-area p {text-align:center; margin-bottom: 15px;}
.user-area input, .user-area textarea, .user-area button {
  width: 100%;
  box-sizing: border-box;
  margin-top: 5px;
}
.user-area button {margin-top: 10px;}
</style>
</head>
<body>

<div class="container">

<?php if (!isset($_SESSION['user_id'])): ?>
  <div class="login-form">
    <h1>📸 My Photo Album - Thicien</h1>
    <h2>Login / Register</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <?php if (!empty($success)) echo "<p style='color:green;'>$success</p>"; ?>
    <form method="post">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button name="login">Login</button>
      <button name="register" style="background:green;">Register</button>
    </form>
  </div>
<?php else: ?>
  <div class="user-area">
    <p>Welcome, <b><?= htmlspecialchars($_SESSION['username']) ?></b> | <a href="?logout" style="color:red;">Logout</a></p>
    <h2>Add New Photo</h2>
    <?php if ($upload_success): ?>
      <div class="msg-box">
        <h3>✅ Photo uploaded successfully!</h3>
        <a href="?show=gallery">Go to Gallery</a>
      </div>
    <?php else: ?>
      <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Photo title" required>
        <textarea name="description" placeholder="Photo description" required></textarea>
        <input type="file" name="photo" accept="image/*" required>
        <button type="submit" name="upload">Upload Photo</button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['show']) && $_GET['show'] === 'gallery'): ?>
    <h2>Your Gallery</h2>
    <div class="gallery">
      <?php
      $stmt = $conn->prepare("SELECT * FROM photos WHERE user_id=? ORDER BY uploaded_at DESC");
      $stmt->bind_param("i", $_SESSION['user_id']);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()):
      ?>
        <div class="photo">
          <img src="<?= htmlspecialchars($row['filename']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
          <h4><?= htmlspecialchars($row['title']) ?></h4>
          <p><?= htmlspecialchars($row['description']) ?></p>
          <div class="actions">
            <form method="post">
              <input type="hidden" name="photo_id" value="<?= $row['id'] ?>">
              <button type="submit" name="delete" style="background:red;">Delete</button>
            </form>
            <form method="post">
              <input type="hidden" name="photo_id" value="<?= $row['id'] ?>">
              <input type="text" name="new_title" placeholder="Edit title" required>
              <input type="text" name="new_desc" placeholder="Edit description" required>
              <button type="submit" name="edit" style="background:orange;">Edit</button>
            </form>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>

<?php endif; ?>
</div>
</body>
</html>
