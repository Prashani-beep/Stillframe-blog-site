<?php
session_start();
include 'includes/db.php';

// Authentication: Only allow logged-in users
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";
$blog = null;
$triggerRedirect = false;
$redirectUrl = "";

// Get blog id from URL and sanitize
$blogid = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch blog and cover
$stmt = $conn->prepare("SELECT title, content, status, coverPage FROM blogs WHERE blogid = ? AND userid = ?");
if ($stmt) {
    $stmt->bind_param("ii", $blogid, $_SESSION['userid']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $error = "Blog not found or you don't have permission to edit it.";
            $blog = ['title'=>'', 'content'=>'', 'coverPage'=>''];
        } else {
            $blog = $result->fetch_assoc();
        }
    } else {
        $error = "Database error. Please try again.";
    }
    $stmt->close();
} else {
    $error = "Failed to prepare blog query. Please contact support.";
}

// Helper for safe output
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$uploadDir = __DIR__ . '/uploads/covers/';
$webUploadDir = 'uploads/covers/';
$maxFileSize = 3 * 1024 * 1024;
$allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $action = $_POST['action'] ?? 'update';
    $status = ($action === 'publish') ? 'published' : 'draft';

    $newCoverPath = null;
    $removeCover = isset($_POST['remove_cover']) && $_POST['remove_cover'] === '1';

    // Handle new upload (if not removing cover)
    if (!$removeCover && isset($_FILES['coverPage']) && $_FILES['coverPage']['error'] !== UPLOAD_ERR_NO_FILE) {
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
                try { $name = bin2hex(random_bytes(12)); }
                catch (Exception $e) { $name = time() . '_' . mt_rand(1000,9999); }
                $ext = $allowedMimes[$mime];
                $filename = $name . '.' . $ext;
                $destination = $uploadDir . $filename;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    $error = "Could not save uploaded image.";
                } else {
                    @chmod($destination, 0644);
                    $newCoverPath = $webUploadDir . $filename;
                }
            }
        }
    }

    // Remove cover image logic
    if ($removeCover && !empty($blog['coverPage'])) {
        $coverFileServer = $uploadDir . basename($blog['coverPage']);
        if (is_file($coverFileServer)) @unlink($coverFileServer);
        $newCoverPath = '';
    }

    if (empty($error)) {
        // Update blog
        if ($removeCover) {
            $update = $conn->prepare("UPDATE blogs SET title = ?, content = ?, status = ?, coverPage = '', updatedAt = NOW() WHERE blogid = ? AND userid = ?");
            if ($update) {
                $update->bind_param("sssii", $title, $content, $status, $blogid, $_SESSION['userid']);
            }
        } elseif ($newCoverPath !== null) {
            $update = $conn->prepare("UPDATE blogs SET title = ?, content = ?, status = ?, coverPage = ?, updatedAt = NOW() WHERE blogid = ? AND userid = ?");
            if ($update) {
                $update->bind_param("ssssii", $title, $content, $status, $newCoverPath, $blogid, $_SESSION['userid']);
            }
        } else {
            $update = $conn->prepare("UPDATE blogs SET title = ?, content = ?, status = ?, updatedAt = NOW() WHERE blogid = ? AND userid = ?");
            if ($update) {
                $update->bind_param("sssii", $title, $content, $status, $blogid, $_SESSION['userid']);
            }
        }
        if ($update) {
            if ($update->execute()) {
                $success = $status === 'published' ? "Blog published successfully!" : "Blog saved as draft!";
                $triggerRedirect = true;
                $redirectUrl = ($status === 'published') ? 'home.php' : 'my_blogs.php';
            } else {
                $error = "Failed to update blog entry.";
            }
            $update->close();
        } else {
            $error = "Failed preparing blog update. Please contact support.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Blog – Stillframe</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
    <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
      :root {
        --sf-moss: #606C38;
        --sf-deep: #283618;
        --sf-cream: #F2F3D9;
        --text: #0f1724;
        --muted: #6b7280;
        --card-bg: rgba(255,255,255,0.98);
      }
      html, body { height:100%; margin:0; font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial; background:var(--sf-cream); color:var(--text); -webkit-font-smoothing:antialiased;}
      .wrap{min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:3rem 1rem;}
      .editor-shell{width:100%; max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:1.8rem;}
      .editor-panel{background:var(--card-bg); border-radius:18px; box-shadow:0 8px 28px rgba(12,16,12,0.05); border:1px solid rgba(16,24,40,0.06); padding:2rem;}
      .site-brand { color:var(--sf-deep); font-weight:800; margin-bottom:.8rem; font-size:2rem;}
      .error{color:#b91c1c;margin-bottom:.9rem;}
      .success{
        color:#3d5b3f;
        margin-bottom:1.1rem;
        text-align:center;
        background: #e7f4df;
        border: 1px solid #b8cfb2;
        padding:.8rem 0;
        border-radius: 8px;
        font-weight:600; font-size:1.07rem;
      }
      .title-input{width:100%; border:0; font-size:1.7rem; font-weight:700; color:var(--sf-deep); padding:.12rem 0 .5rem 0; border-bottom:1px dashed rgba(16,24,40,0.07); margin-bottom:.7rem;}
      .title-input::placeholder{color:#9aa6a0;}
      label{font-weight:600; color:var(--sf-deep);}
      input, textarea { font-size:1.04rem; }
      .small-muted { font-size:.84rem; color:var(--muted); margin-top:.5rem; }
      .btn {display:inline-block; font-weight:700; border-radius:8px; padding:.45rem 1.1rem; border:0; margin-right:.6rem; cursor:pointer;}
      .btn-draft { background:#fff; color:var(--sf-deep); border:1px solid var(--sf-deep); }
      .btn-draft:hover { background:var(--sf-cream);}
      .btn-danger { background: #fde6e6; color: #911b1b; border: 1.5px solid #f4b4b4; }
      .btn-danger:hover { background: #fcb5b5; color: #510b0b; }
      .btn-publish { background:linear-gradient(90deg,var(--sf-moss),var(--sf-deep)); color:#fff; }
      .btn-publish:hover { filter:brightness(1.05); }
      .dropzone{display:flex; align-items:center; justify-content:center; width:100%; height:62px; border:2px dashed rgba(16,24,40,0.10); border-radius:10px; padding:.6rem; text-align:center; background:rgba(255,255,255,0.96); color:var(--muted); cursor:pointer;}
      .dropzone.dragover{border-color:rgba(96,108,56,0.32);  background:rgba(240,245,220,0.88);}
      .preview-img{max-width:100%; border-radius:8px; margin-top:.9rem; border:1px solid rgba(16,24,40,0.10);}
      @media(max-width:600px) {.editor-panel{padding:1rem;} .editor-shell{padding-bottom:2rem;} }
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

            <!-- Existing cover image preview if available -->
            <?php if (!empty($blog['coverPage'])): ?>
                <div style="margin-bottom:1.1rem; display:flex; flex-direction:column; align-items:flex-start; position:relative;">
                  <div style="width: 100%; display: flex; justify-content: flex-end; margin-bottom: .35rem;">
                    <button type="submit" name="remove_cover" value="1" class="btn btn-danger" style="margin-left:auto;" onclick="return confirm('Remove cover image permanently?');">
                      Remove Cover
                    </button>
                  </div>
                  <label style="font-size:.85rem;color:var(--sf-deep);font-weight:bold;margin-bottom:.2rem;">Current Cover:</label>
                  <img src="<?php echo esc($blog['coverPage']); ?>" alt="Current cover" class="preview-img" style="max-width:360px;">
                </div>
            <?php endif; ?>

            <!-- Drop Zone and Preview -->
            <div class="field" style="margin-bottom:1.4rem;">
              <label style="font-size:.9rem; color:var(--muted); display:block; margin-bottom:.3rem;">Change Cover Image</label>
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
              <div class="small-muted">JPEG/PNG/WEBP, max 3 MB. Will replace previous cover when saved.</div>
            </div>

            <label for="title">Title:</label>
            <input type="text" name="title" class="title-input" value="<?php echo esc($blog['title'] ?? ''); ?>" required>

            <label for="content">Content:</label>
            <textarea name="content" id="content"><?php echo esc($blog['content'] ?? ''); ?></textarea>

            <div style="margin-top:1.5rem;">
              <button type="submit" name="action" value="update" class="btn btn-draft">Save Draft</button>
              <button type="submit" name="action" value="publish" class="btn btn-publish" style="margin-right:0;">Publish</button>
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

    dropZone.addEventListener('click', function(){ fileInput.click(); });
    dropZone.addEventListener('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); } });
    fileInput.addEventListener('change', handleFiles);

    dropZone.addEventListener('dragover', function(e){ e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', function(){ dropZone.classList.remove('dragover'); });
    dropZone.addEventListener('drop', function(e){
      e.preventDefault(); dropZone.classList.remove('dragover');
      var dtFiles = e.dataTransfer.files;
      if (dtFiles && dtFiles.length) { fileInput.files = dtFiles; handleFiles(); }
    });

    function handleFiles(){
      var f = fileInput.files && fileInput.files[0];
      if(!f){ previewWrap.style.display = 'none'; return; }
      if(!f.type.match(/image.*/)){ alert('Please choose an image file.'); fileInput.value=''; return; }
      if (f.size > 3*1024*1024) { alert('Image too large. Max 3 MB.'); fileInput.value=''; return; }
      var url = URL.createObjectURL(f);
      imgPreview.src = url;
      previewWrap.style.display = 'block';
    }
    removeBtn.addEventListener('click', function(){
      fileInput.value = '';
      imgPreview.src = '';
      previewWrap.style.display = 'none';
    });
  })();
</script>
</body>
</html>
