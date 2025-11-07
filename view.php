<?php
session_start();
include 'includes/db.php';
include 'includes/Parsedown.php';

$Parsedown = new Parsedown();

// Get blog ID from URL, sanitize
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Blog not found.";
    exit();
}
$blogId = intval($_GET['id']);
$userId = $_SESSION['userid'] ?? null;

function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Fetch blog info, only show if published or you are the author
$stmt = $conn->prepare("
    SELECT b.title, b.content, b.createdAt, b.updatedAt, b.coverPage, b.status, b.userid, u.username
    FROM blogs b
    JOIN users u ON b.userid = u.id
    WHERE b.blogid = ? AND (b.status = 'published' OR b.userid = ?)
");
if ($stmt) {
    $stmt->bind_param("ii", $blogId, $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            echo "Blog not found or you do not have permission to view it.";
            exit();
        }
        $blog = $result->fetch_assoc();
    } else {
        echo "Database error. Please try later.";
        exit();
    }
    $stmt->close();
} else {
    echo "Server error. Please try later.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo esc($blog['title']); ?> – Stillframe</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <!-- Tailwind with Typography plugin via CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'sf-moss': '#606C38',
              'sf-deep': '#283618',
              'sf-cream': '#F2F3D9',
              'sf-sunset': '#FFD9B3',
              'sf-draft': '#ffed8a'
            },
            fontFamily: {
              'display': ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
              'hero': ['Georgia', 'serif']
            }
          }
        }
      }
    </script>
    <style>
      body {
        background: linear-gradient(to bottom right, #fbfaf5 0%, #f3f3d7 100%);
      }
    </style>
</head>
<body class="bg-sf-cream antialiased font-display min-h-screen flex flex-col">
<header id="mainNav" class="fixed top-0 left-0 w-full z-50 bg-white/40 backdrop-blur-[10px] border-b border-sf-moss/10 transition shadow">
  <div class="max-w-7xl mx-auto py-6 px-5 flex flex-col sm:flex-row items-center justify-between">
    <div class="font-hero text-3xl md:text-4xl text-sf-deep tracking-tight font-extrabold select-none">Stillframe</div>
    <nav aria-label="User actions" class="mt-4 sm:mt-0 flex gap-4">
      <a href="mock_write.php" class="rounded-full bg-white px-6 py-2 text-sf-moss font-semibold shadow hover:bg-sf-sunset hover:text-sf-deep border border-sf-moss/30 transition-colors">Write</a>
      <a href="my_blogs.php" class="rounded-full bg-white px-6 py-2 text-sf-moss font-semibold shadow hover:bg-sf-sunset hover:text-sf-deep border border-sf-moss/30 transition-colors">My Blogs</a>
      <a href="home.php?logout=true" class="rounded-full px-6 py-2 bg-sf-deep text-white font-semibold shadow hover:bg-black/70 transition-colors">Logout</a>
    </nav>
  </div>
</header>
<div class="h-[88px] sm:h-[92px]"></div>

<main class="flex-1 w-full">
  <?php if (!empty($blog['coverPage'])): ?>
    <div class="w-full flex justify-center bg-gradient-to-b from-white/85 to-sf-cream/50 pb-2">
      <img src="<?php echo esc($blog['coverPage']); ?>"
           class="max-w-4xl w-full h-[270px] md:h-[370px] object-cover object-center rounded-3xl shadow"
           alt="Blog Cover"
           style="filter:brightness(0.98)"/>
    </div>
  <?php endif; ?>

  <div class="max-w-3xl mx-auto px-2 md:px-5 mt-10 mb-6">
    <div class="flex flex-col items-center mb-3">
      <div class="text-center uppercase text-xs tracking-[0.15em] text-sf-moss font-semibold opacity-75 mb-2">
        Stillframe Feature
      </div>
      <h1 class="font-hero text-4xl md:text-5xl font-extrabold text-sf-deep mb-2 leading-tight text-center">
        <?php echo esc($blog['title']); ?>
      </h1>
      <?php if ($blog['status'] !== 'published'): ?>
        <span class="mt-2 mb-3 px-4 py-1 bg-sf-draft/60 text-sf-deep text-xs font-bold rounded-full uppercase tracking-widest shadow border border-sf-moss/20">
          DRAFT (Not published)
        </span>
      <?php endif; ?>
    </div>
    <div class="flex flex-col sm:flex-row justify-center items-center mb-6 gap-2">
      <div class="ml-0 sm:ml-3 text-center sm:text-left">
        <span class="uppercase text-sf-deep font-bold tracking-wide text-sm"><?php echo esc($blog['username']); ?></span>
        <span class="mx-2 hidden sm:inline" aria-hidden="true">·</span>
        <span class="text-sf-moss text-sm"><?php echo date("M d, Y", strtotime($blog['createdAt'])); ?></span>
      </div>
    </div>

    <div class="prose prose-lg max-w-none prose-h1:font-hero prose-h2:font-hero prose-h3:font-hero prose-h4:font-hero prose-h5:font-hero prose-h6:font-hero text-slate-900 mx-auto mb-16">
      <?php
      // Escape and render Markdown safely
      $safeContent = $Parsedown->text(esc($blog['content']));
      echo $safeContent;
      ?>
    </div>
    <div class="text-center pb-10">
      <a href="home.php" class="inline-block px-7 py-2 bg-sf-moss text-white rounded-full font-semibold shadow hover:bg-sf-deep transition">Back to Home</a>
    </div>
  </div>
</main>
<footer class="text-center text-xs text-sf-deep/70 mt-auto pb-7 pt-2">
  &copy; <?php echo date("Y"); ?> Stillframe • Crafted with care.
</footer>
</body>
</html>
