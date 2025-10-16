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

$upload_success = false;

// Delete photo
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

// Edit photo
if (isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $title = $_POST['title'];
    $description = $_POST['description'];
    $conn->query("UPDATE photos SET title='$title', description='$description' WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Upload photo
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
        $upload_success = true;
    } else {
        echo "<p style='color:red;text-align:center;'>Error uploading file!</p>";
    }
}

$result = $conn->query("SELECT * FROM photos ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Photo Album - Thicien</title>

  <style>
    body {
      font-family: "Poppins", sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 0;
    }

    header {
      background-color: #007bff;
      color: white;
      text-align: center;
      padding: 20px 0;
      font-size: 24px;
      font-weight: bold;
    }

    header img {
      width: 40px;
      vertical-align: middle;
      margin-right: 10px;
    }

    .container {
      max-width: 800px;
      margin: 40px auto;
      background-color: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    input, textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 16px;
    }

    button {
      background-color: #007bff;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      transition: 0.3s ease;
    }

    button:hover {
      background-color: #0056b3;
    }

    .show-btn {
      display: inline-block;
      background-color: #007bff;
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s ease;
    }

    .show-btn:hover {
      background-color: #0056b3;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .gallery {
      display: none;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 30px;
    }

    .photo-card {
      background-color: #fafafa;
      border-radius: 10px;
      padding: 10px;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    .photo-card img {
      max-width: 100%;
      border-radius: 8px;
    }

    footer {
      text-align: center;
      margin-top: 40px;
      color: #666;
      padding: 20px;
      font-size: 14px;
    }
  </style>
</head>
<body>
  <header>
    📸 My Photo Album - Thicien
  </header>

  <div class="container">
    <h2>Add New Photo</h2>

    <form id="photoForm">
      <input type="text" id="title" placeholder="Photo title" required />
      <textarea id="description" placeholder="Photo description" required></textarea>
      <input type="file" id="photo" accept="image/*" required />
      <button type="submit">Upload Photo</button>
    </form>

    <!-- ✅ Always Visible Gallery Button -->
    <div style="text-align:center; margin-top:20px;">
      <button class="show-btn" onclick="toggleGallery()">🖼️ Open My Gallery</button>
    </div>

    <h2>Gallery</h2>
    <div id="gallery" class="gallery">
      <!-- Uploaded photos will appear here -->
    </div>
  </div>

  <footer>
    © 2025 Thicien | Photo Album
  </footer>

  <script>
    const photoForm = document.getElementById("photoForm");
    const gallery = document.getElementById("gallery");

    // Handle photo upload
    photoForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const title = document.getElementById("title").value;
      const description = document.getElementById("description").value;
      const photo = document.getElementById("photo").files[0];

      if (!photo) {
        alert("Please select a photo to upload.");
        return;
      }

      const reader = new FileReader();
      reader.onload = function (event) {
        const photoCard = document.createElement("div");
        photoCard.classList.add("photo-card");

        photoCard.innerHTML = `
          <img src="${event.target.result}" alt="${title}" />
          <h3>${title}</h3>
          <p>${description}</p>
        `;

        gallery.appendChild(photoCard);
      };

      reader.readAsDataURL(photo);
      photoForm.reset();
      gallery.style.display = "grid";
    });

    // Toggle gallery visibility
    function toggleGallery() {
      if (gallery.style.display === "grid") {
        gallery.style.display = "none";
      } else {
        gallery.style.display = "grid";
        window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
      }
    }
  </script>
</body>
</html>



