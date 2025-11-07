<?php
session_start();
include 'includes/db.php';

// Security: Don't show raw errors to users; log to file only in production
// ini_set('display_errors', 0);

$error = "";

// Authentication check (already present and correct)
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userid = $_SESSION['userid'];
$username = $_SESSION['username'] ?? 'Unknown User';

$blogs = [];
$publishedCount = 0;

// Query for this user's blogs using prepared statements and error handling
$blogsQuery = $conn->prepare(
    "SELECT b.blogid, b.title, b.status, b.createdAt, b.updatedAt, b.coverPage, LEFT(b.content, 140) as snippet, u.username
     FROM blogs b JOIN users u ON b.userid = u.id
     WHERE b.userid = ? ORDER BY b.updatedAt DESC"
);
if (!$blogsQuery) {
    $error = "Blogs query failed. Please contact support.";
} else {
    $blogsQuery->bind_param("i", $userid);
    if ($blogsQuery->execute()) {
        $result = $blogsQuery->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($row['status'] === 'published') $publishedCount++;
            $blogs[] = $row;
        }
    } else {
        $error = "Database error. Try refreshing.";
    }
    $blogsQuery->close();
}

// Helper function for safe output
function esc($s) { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Blogs — Stillframe</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'sf-moss': '#606C38',
              'sf-deep': '#283618',
              'sf-cream': '#F2F3D9',
              'sf-red': '#b91c1c'
            },
            fontFamily: {
              'display': ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif']
            }
          }
        }
      }
    </script>
    <style>
      body {
        background: linear-gradient(to bottom right, #fbfaf5 0%, #f3f3d7 100%);
      }
      .glass-nav-scrolled {
        box-shadow: 0 6px 24px 0 rgba(40,54,24,0.08) !important;
        background: rgba(255,255,255,0.78) !important;
      }
    </style>
</head>
<body class="min-h-screen bg-sf-cream antialiased font-display">
  <!-- Sticky glass nav bar -->
  <header id="myBlogsNav" class="fixed top-0 left-0 w-full z-50 bg-white/40 backdrop-blur-[8px] border-b border-sf-moss/10 transition shadow">
    <div class="max-w-7xl mx-auto px-5 py-6 flex flex-row items-center justify-between gap-4">
      <span class="text-xl md:text-2xl text-sf-deep font-bold">Welcome back, <span class="font-extrabold"><?php echo esc($username); ?></span></span>
      <div class="flex flex-row gap-3">
        <a href="home.php" class="rounded-full bg-white px-5 py-2 text-sf-moss font-semibold shadow hover:bg-sf-cream hover:text-sf-deep border border-sf-moss/30 transition-colors">Back to Home</a>
        <form action="mock_write.php" method="get" class="inline">
          <button type="submit" class="inline-flex items-center gap-2 rounded-full px-5 py-2 bg-white text-sf-moss font-semibold shadow hover:bg-sf-cream border border-sf-moss/30 hover:text-sf-deep transition-colors">
            <span>✍️</span> <span>Write New Blog</span>
          </button>
        </form>
      </div>
    </div>
  </header>
  <div class="h-[92px]"></div><!-- Spacer for nav -->

  <main class="flex flex-col items-center justify-center px-2 py-8 min-h-screen">
    <div class="blog-section w-full max-w-6xl mx-auto bg-white/95 rounded-2xl shadow-lg px-8 py-10">
      <?php if ($publishedCount == 1 && count($blogs) === 1): ?>
        <div class="mb-5 text-center text-sf-deep font-semibold bg-sf-cream/50 rounded shadow py-3">
          You have 1 published post!
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="mb-5 p-3 rounded bg-red-50 text-red-700 border border-red-100 shadow">
          <?php echo esc($error); ?>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($blogs as $blog): ?>
          <div class="bg-white rounded-2xl shadow-md flex flex-col overflow-hidden border border-sf-moss/10">
            <div class="h-48 w-full overflow-hidden relative rounded-t-2xl" style="background: rgba(252,250,243,0.73)">
              <img src="<?php echo esc($blog['coverPage'] ?? 'uploads/default.jpg'); ?>"
                   alt="Blog Cover"
                   class="w-full h-full object-cover object-center"
                   style="filter: brightness(0.92);"/>
              <div class="absolute inset-0 bg-gradient-to-t from-black/15 to-transparent pointer-events-none"></div>
            </div>
            <div class="flex flex-col flex-1 px-6 py-5">
              <div class="text-xs uppercase tracking-wide text-sf-moss mb-1">
                <?php echo esc($blog['username']); ?> &middot; <?php echo date("M d, Y", strtotime($blog['createdAt'])); ?>
              </div>
              <div class="mb-2">
                <a href="view.php?id=<?php echo intval($blog['blogid']); ?>"
                   class="font-semibold text-xl text-sf-deep hover:text-sf-moss transition-colors">
                  <?php echo esc($blog['title']); ?>
                </a>
                <p class="text-slate-700 text-base mt-2 mb-1">
                  <?php echo esc($blog['snippet']); ?>...
                </p>
              </div>
              <div class="flex flex-wrap gap-2 mb-2">
                <?php
                $tags = [];
                if (isset($blog['status'])) $tags[] = $blog['status'];
                foreach ($tags as $tag): ?>
                  <span class="inline-block bg-sf-cream border border-sf-moss/30 px-2.5 py-1 rounded-full text-xs font-medium text-sf-deep">
                    #<?php echo esc($tag); ?>
                  </span>
                <?php endforeach; ?>
              </div>
              <div class="flex gap-3 mt-2">
                <a href="edit_blog.php?id=<?php echo intval($blog['blogid']); ?>"
                   class="inline-block rounded-md px-4 py-2 bg-sf-moss text-white font-semibold shadow hover:bg-sf-deep transition-colors">
                  Edit
                </a>
                <a href="delete_blog.php?id=<?php echo intval($blog['blogid']); ?>"
                   onclick="return confirm('Are you sure you want to delete this blog?');"
                   class="inline-block rounded-md px-4 py-2 bg-sf-red text-white font-semibold shadow hover:bg-red-900 transition-colors">
                  Delete
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (empty($blogs)): ?>
          <div class="flex flex-col items-center justify-center bg-sf-cream/50 rounded-2xl shadow border border-sf-moss/20 p-10 col-span-full">
            <div class="text-4xl mb-3">+</div>
            <div class="text-lg font-semibold text-sf-deep mb-2">Add New Blog</div>
            <form action="mock_write.php" method="get">
              <button type="submit"
                      class="inline-flex items-center gap-2 rounded-lg px-5 py-2 bg-white text-sf-moss font-semibold shadow hover:bg-sf-cream hover:shadow-lg border border-sf-moss transition-colors">
                <span></span> <span>Write Your First Blog!</span>
              </button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
  <script>
    window.addEventListener('scroll', function(){
      const nav = document.getElementById('myBlogsNav');
      if(window.scrollY > 6) nav.classList.add('glass-nav-scrolled');
      else nav.classList.remove('glass-nav-scrolled');
    });
  </script>
</body>
</html>
