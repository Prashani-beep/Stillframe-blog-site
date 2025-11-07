<?php
session_start();
include 'includes/db.php';

// Don't reveal server errors to users in production
// ini_set('display_errors', 0);

$error = "";
$success = "";
$triggerRedirect = false;
$redirectUrl = "";

if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$uploadDir = __DIR__ . '/uploads/covers/';
$webUploadDir = 'uploads/covers/';
$maxFileSize = 3 * 1024 * 1024;
$allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $action  = $_POST['action'] ?? 'save';

    if ($title === '' || $content === '') {
        $error = "Both title and content are required.";
    } else {
        // Create uploads folder if it doesn't exist
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            $error = "Server error: cannot create upload directory.";
        }

        $coverPath = null;
        // Validate file upload
        if (isset($_FILES['coverPage']) && $_FILES['coverPage']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['coverPage'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = "Image upload failed (error code {$file['error']}).";
            } elseif ($file['size'] > $maxFileSize) {
                $error = "Image too large. Max 3 MB allowed.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!isset($allowedMimes[$mime])) {
                    $error = "Unsupported image type. Allowed: JPEG, PNG, WEBP.";
                } elseif (!@getimagesize($file['tmp_name'])) {
                    $error = "File is not a valid image.";
                } else {
                    // Security: Store with unique random name, don't trust original
                    $name = bin2hex(random_bytes(12));
                    $ext = $allowedMimes[$mime];
                    $filename = $name . '.' . $ext;
                    $destination = $uploadDir . $filename;
                    // Prevent directory traversal by allowing only our designated directory
                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $error = "Failed to save uploaded image.";
                    } else {
                        $coverPath = $webUploadDir . $filename;
                    }
                }
            }
        }

        // Save blog if no error
        if (empty($error)) {
            $status = ($action === 'publish') ? 'published' : 'draft';
            if ($coverPath === null) {
                $sql = "INSERT INTO blogs (userid, title, content, status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $_SESSION['userid'], $title, $content, $status);
            } else {
                $sql = "INSERT INTO blogs (userid, title, content, status, coverPage) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issss", $_SESSION['userid'], $title, $content, $status, $coverPath);
            }

            if ($stmt && $stmt->execute()) {
                $msg = ($status === 'published') ? "Congratulations! Your blog has been published." : "Blog saved as draft!";
                $success = htmlspecialchars($msg, ENT_QUOTES);
                $triggerRedirect = true;
                $redirectUrl = ($status === 'published') ? 'home.php' : 'my_blogs.php';
            } else {
                $error = "Failed to save blog (database error).";
            }
            if ($stmt) $stmt->close();
        }
    }
}

// Safe output helper
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Create Blog — Stillframe</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --sf-moss:#606C38;
  --sf-deep:#283618;
  --sf-cream:#F2F3D9;
  --text:#0f1724;
  --muted:#6b7280;
  --card-bg:#ffffff;
}
html,body {
  height:100%;
  margin:0;
  font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial;
  background:var(--sf-cream);
  color:var(--text);
}
.wrap {
  min-height:100vh;
  display:flex;
  justify-content:center;
  padding:3rem 1rem;
}
.editor-shell {
  width:100%;
  max-width:850px;
  display:flex;
  flex-direction:column;
  gap:1.8rem;
}
.editor-panel {
  background:var(--card-bg);
  border-radius:16px;
  box-shadow:0 4px 24px rgba(0,0,0,0.05);
  border:1px solid rgba(16,24,40,0.06);
  padding:2rem 2.5rem;
}
.site-brand {
  color:var(--sf-deep);
  font-weight:800;
  font-size:1.9rem;
  margin-bottom:.8rem;
  text-align:center;
}
.error{color:#b91c1c;margin-bottom:.9rem;text-align:center;}
.success{
  color:#3d5b3f;
  margin-bottom:1.1rem; text-align:center;
  background: #e7f4df;
  border: 1px solid #b8cfb2;
  padding:.8rem 0;
  border-radius: 8px;
  font-weight:600; font-size:1.07rem;
}
label{font-weight:600; color:var(--sf-deep);}
.title-input {
  width:100%;
  border:none;
  font-size:1.6rem;
  font-weight:700;
  color:var(--sf-deep);
  padding:.3rem 0;
  border-bottom:1px dashed rgba(16,24,40,0.08);
  margin-bottom:.9rem;
  transition:border-color .2s ease;
}
.title-input:focus {
  outline:none;
  border-color:var(--sf-moss);
}
.dropzone {
  display:flex;
  align-items:center;
  justify-content:center;
  width:100%;
  height:70px;
  border:2px dashed rgba(16,24,40,0.1);
  border-radius:10px;
  text-align:center;
  background:#fff;
  color:var(--muted);
  cursor:pointer;
  transition:all .2s ease;
}
.dropzone:hover { background:rgba(240,245,230,0.8); border-color:var(--sf-moss); }
.preview-img {max-width:100%; border-radius:8px; margin-top:1rem; border:1px solid rgba(16,24,40,0.1);}
.btn {
  display:inline-block;
  font-weight:700;
  border-radius:8px;
  padding:.55rem 1.2rem;
  border:0;
  cursor:pointer;
  transition:all .2s ease;
}
.btn-draft {
  background:#fff;
  color:var(--sf-deep);
  border:1px solid var(--sf-deep);
}
.btn-draft:hover {
  background:var(--sf-cream);
}
.btn-publish {
  background:linear-gradient(90deg,var(--sf-moss),var(--sf-deep));
  color:#fff;
}
.btn-publish:hover {
  filter:brightness(1.05);
}
.small-muted {
  font-size:.85rem;
  color:var(--muted);
  margin-top:.5rem;
}
@media(max-width:700px){
  .editor-panel{padding:1.5rem;}
  .title-input{font-size:1.3rem;}
}
</style>
<?php if ($triggerRedirect): ?>
<script>
  setTimeout(function(){
    window.location.href = "<?php echo $redirectUrl ?>";
  }, 1200);
</script>
<?php endif; ?>
</head>
<body>
<div class="wrap">
  <form method="POST" action="" enctype="multipart/form-data" class="editor-shell">
    <div class="editor-panel">
      <div class="site-brand">Stillframe</div>
      <?php if (!empty($error)) echo "<div class='error'>".esc($error)."</div>"; ?>
      <?php if (!empty($success)) echo "<div class='success'>".esc($success)."</div>"; ?>

      <div class="field" style="margin-bottom:1.4rem;">
        <label style="font-size:.9rem; color:var(--muted); display:block; margin-bottom:.3rem;">Cover Image</label>
        <div id="dropZone" class="dropzone" tabindex="0" role="button" aria-label="Upload cover image">
          Drag & drop here, or click to choose
          <input id="coverInput" name="coverPage" type="file" accept="image/jpeg,image/png,image/webp" style="display:none;" />
        </div>
        <div id="imagePreview" style="display:none;">
          <img id="previewImg" src="" alt="Cover preview" class="preview-img" />
          <div style="margin-top:.5rem;">
            <button id="removeImage" type="button" class="btn btn-draft">Remove</button>
          </div>
        </div>
        <div class="small-muted">JPEG/PNG/WEBP, max 3 MB.</div>
      </div>

      <label for="title">Title:</label>
      <input type="text" name="title" class="title-input" placeholder="Untitled — write a short, evocative title" required value="<?php echo esc($_POST['title'] ?? '') ?>">

      <label for="content">Content:</label>
      <textarea name="content" id="content"><?php echo esc($_POST['content'] ?? '') ?></textarea>

      <div style="margin-top:1.5rem; display:flex; flex-wrap:wrap; gap:.8rem;">
        <button type="submit" name="action" value="save" class="btn btn-draft">Save Draft</button>
        <button type="submit" name="action" value="publish" class="btn btn-publish">Publish</button>
      </div>
      <div class="small-muted">Tip: Ctrl+S to save, Ctrl+Enter to publish.</div>
    </div>
  </form>
</div>
<script>
  var simplemde = new SimpleMDE({ element: document.getElementById("content") });
  (function(){
    var dropZone = document.getElementById('dropZone');
    var fileInput = document.getElementById('coverInput');
    var imgPreview = document.getElementById('previewImg');
    var previewWrap = document.getElementById('imagePreview');
    var removeBtn = document.getElementById('removeImage');
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });
    fileInput.addEventListener('change', handleFiles);
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
      e.preventDefault(); dropZone.classList.remove('dragover');
      if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFiles();
      }
    });
    function handleFiles(){
      const f = fileInput.files[0];
      if(!f){ previewWrap.style.display = 'none'; return; }
      if(!f.type.match(/image.*/)){ alert('Please choose an image file.'); fileInput.value=''; return; }
      if(f.size > 3*1024*1024){ alert('Image too large. Max 3 MB.'); fileInput.value=''; return; }
      imgPreview.src = URL.createObjectURL(f);
      previewWrap.style.display = 'block';
    }
    removeBtn.addEventListener('click', () => {
      fileInput.value=''; imgPreview.src=''; previewWrap.style.display='none';
    });
  })();
</script>
</body>
</html>
