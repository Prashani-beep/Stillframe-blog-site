<?php
session_start();
include 'includes/db.php';

// Ensure session fixation protection at login and logout (if not already done on login page)
// Example for login page: session_regenerate_id(true);
// Here: regenerate on logout
if (isset($_GET['logout'])) {
    session_unset(); // Clear session variables
    session_destroy(); // Destroy session
    // Regenerate a new session to avoid fixation if reused
    session_start();
    session_regenerate_id(true);
    header("Location: login.php");
    exit();
}

// Only allow logged-in users
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

// Fetch all published blogs with coverPage column!
$blogs = false; // Default value
$blogsQuery = $conn->prepare("
    SELECT b.blogid, b.title, b.createdAt, u.username, b.coverPage
    FROM blogs b
    JOIN users u ON b.userid = u.id
    WHERE b.status = 'published'
    ORDER BY b.createdAt DESC
");
if ($blogsQuery && $blogsQuery->execute()) {
    $blogs = $blogsQuery->get_result();
} else {
    // Log error in a secure way if needed (error_log)
    // You can display a friendly message below instead
    $blogsError = "Error fetching published blogs. Please try again later.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Home — Stillframe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'sf-moss': '#606C38',
              'sf-deep': '#283618',
              'sf-cream': '#F2F3D9'
            },
            fontFamily: {
              'display': ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
              'hero': ['Georgia', 'serif']
            }
          }
        }
      }
    </script>
</head>
<body class="bg-sf-cream antialiased font-display min-h-screen">
  <!-- Frozen glass nav -->
  <header class="fixed top-0 left-0 w-full z-50 bg-white/40 backdrop-blur-[8px] border-b border-sf-moss/10 transition shadow-md">
    <div class="max-w-7xl mx-auto py-6 px-5 flex flex-col sm:flex-row items-center justify-between">
      <div class="font-hero text-3xl md:text-4xl text-sf-deep tracking-tight font-extrabold select-none">Stillframe</div>
      <nav aria-label="User actions" class="mt-4 sm:mt-0 flex gap-4">
        <a href="mock_write.php" class="rounded-lg bg-white px-5 py-2 text-sf-moss font-semibold shadow hover:bg-sf-cream border border-sf-moss transition-colors">Write</a>
        <a href="my_blogs.php" class="rounded-lg bg-white px-5 py-2 text-sf-moss font-semibold shadow hover:bg-sf-cream border border-sf-moss transition-colors">My Blogs</a>
        <!-- Switch logout to form POST for CSRF protection (optional, see below for GET fallback) -->
        <!-- <form method="POST" style="display:inline;" action="home.php">
          <button type="submit" name="logout" class="rounded-lg px-5 py-2 bg-sf-deep text-white font-semibold shadow hover:bg-black/70 transition-colors">Logout</button>
        </form> -->
        <a href="home.php?logout=true" class="rounded-lg px-5 py-2 bg-sf-deep text-white font-semibold shadow hover:bg-black/70 transition-colors">Logout</a>
      </nav>
    </div>
  </header>
  <!-- Spacer for fixed nav -->
  <div class="h-[88px] sm:h-[92px]"></div>
  
  <main id="main" class="relative">
    <!-- HERO SECTION -->
    <section class="text-center py-14 px-3 md:px-0">
      <div class="max-w-2xl mx-auto">
        <p class="uppercase text-xs tracking-[.23em] text-sf-deep mb-2 font-semibold opacity-75">Curated stories for thoughtful creators</p>
        <h1 class="font-hero text-4xl md:text-5xl font-extrabold text-sf-deep mb-4 leading-tight">
          Ideas that frame moments, inspire reflection.
        </h1>
        <p class="text-lg text-sf-moss max-w-xl mx-auto mb-5 font-medium">"The art of storytelling is painting with words; every story is a stillframe of the soul." <span class="italic text-sf-deep">— Stillframe Community</span></p>
        <a href="#published-heading" class="inline-block rounded-full px-8 py-3 font-semibold bg-sf-deep text-white hover:bg-sf-moss shadow transition">Explore Latest Stories</a>
      </div>
    </section>
    <!-- BLOG GRID -->
    <section id="published" aria-labelledby="published-heading" class="max-w-6xl mx-auto mt-14 px-5 pb-16">
      <h2 id="published-heading" class="text-sf-deep text-2xl font-bold mb-8 text-center">Latest Published Blogs</h2>
      <?php if (isset($blogsError)): ?>
        <div class="rounded-xl bg-red-100 px-8 py-10 text-center text-lg text-red-800 font-semibold shadow mt-10">
          <?php echo htmlspecialchars($blogsError); ?>
        </div>
      <?php elseif ($blogs && $blogs->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <?php while ($row = $blogs->fetch_assoc()): ?>
            <?php
              $blogId = (int)$row['blogid'];
              $title = htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
              $author = htmlspecialchars($row['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
              $date = date("M d, Y", strtotime($row['createdAt']));
              $cover = $row['coverPage'] ?? '';
              $safeCover = htmlspecialchars($cover, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
              $viewUrl = "view.php?id=" . $blogId;
            ?>
            <article aria-labelledby="title-<?php echo $blogId; ?>" class="bg-white rounded-2xl shadow-md flex flex-col overflow-hidden border border-sf-moss/10 hover:shadow-lg transition">
              <!-- Cover or placeholder -->
              <?php if ($safeCover): ?>
                <div class="h-48 w-full overflow-hidden relative rounded-t-2xl" style="background:rgba(242,243,217,0.60)">
                  <img src="<?php echo $safeCover; ?>"
                        class="w-full h-full object-cover object-center"
                        alt="Blog cover image" style="filter:brightness(0.96);" />
                </div>
              <?php else: ?>
                <div class="h-48 w-full flex items-center justify-center bg-sf-cream text-sf-moss font-bold tracking-widest text-lg uppercase opacity-75 rounded-t-2xl">
                  Awaiting cover
                </div>
              <?php endif; ?>
              <div class="flex-1 flex flex-col p-8">
                <div class="text-xs uppercase tracking-wide text-sf-moss mb-2"><?php echo $author; ?> &bull; <?php echo $date; ?></div>
                <h3 id="title-<?php echo $blogId; ?>" class="text-2xl font-hero font-bold text-sf-deep mb-1">
                  <a href="<?php echo $viewUrl; ?>" class="hover:text-sf-moss transition"><?php echo $title; ?></a>
                </h3>
                <div class="flex-1"></div>
                <div class="pt-7">
                  <a href="<?php echo $viewUrl; ?>" class="text-sf-deep hover:text-sf-moss font-semibold transition">Read Story &rarr;</a>
                </div>
              </div>
            </article>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="rounded-xl bg-white/70 px-8 py-10 text-center text-lg text-sf-deep font-semibold shadow mt-10">
          No blogs have been published yet.<br>
          <a href="mock_write.php" class="inline-block rounded-lg mt-4 px-6 py-2 bg-sf-moss text-white hover:bg-sf-deep font-bold transition">Write your first blog</a>
        </div>
      <?php endif; ?>
    </section>
  </main>
  
  <footer role="contentinfo" class="border-t border-sf-moss/10 bg-white/60 mt-auto py-10 px-5">
    <div class="max-w-4xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <div class="font-hero text-2xl font-bold tracking-tighter text-sf-deep mb-2">Stillframe</div>
        <div class="text-sf-moss text-sm mb-2">Stories for thoughtful makers.<br>Return to your roots. Create. Reflect. Continue.</div>
      </div>
      <div class="mt-1 md:mt-0 text-right text-xs text-sf-deep opacity-80">
        &copy; <?php echo date("Y"); ?> Stillframe &bull; Crafted with care.
      </div>
    </div>
  </footer>
</body>
</html>
