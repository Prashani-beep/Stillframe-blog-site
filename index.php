<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Welcome — Stillframe</title>
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
    <style>
      body {
        background-image: url('pictures/index.jpg'); /* set your image path here */
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center center;
      }
      .glass {
        background: rgba(255, 255, 255, 0.45);
        border-radius: 1.25rem;
        box-shadow: 0 8px 32px 0 rgba(40,54,24,.12);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(96,108,56,0.12);
      }
    </style>
</head>
<body class="min-h-screen font-display bg-sf-cream relative">
  <!-- Glass Nav Bar -->
  <header class="fixed top-0 left-0 w-full z-50 bg-white/40 backdrop-blur-[8px] border-b border-sf-moss/10 shadow transition">
    <div class="max-w-7xl mx-auto py-6 px-5 flex flex-col sm:flex-row items-center justify-between">
      <div class="font-hero text-3xl md:text-4xl text-sf-deep tracking-tight font-extrabold select-none">Stillframe</div>
      <nav aria-label="actions" class="mt-4 sm:mt-0 flex gap-4">
        <a href="login.php" class="rounded-lg bg-white px-5 py-2 text-sf-moss font-semibold shadow hover:bg-sf-cream border border-sf-moss transition-colors">Login</a>
        <a href="register.php" class="rounded-lg bg-sf-moss px-5 py-2 text-white font-semibold shadow hover:bg-sf-deep border border-sf-moss transition-colors">Register</a>
      </nav>
    </div>
  </header>
  <div class="h-[88px] sm:h-[92px]"></div>

  <!-- Hero Section with Cream Background -->
  <main class="flex items-center justify-center min-h-[70vh] px-2">
    <section 
      class="w-full max-w-3xl mx-auto text-center py-16 px-6 rounded-2xl shadow-lg border border-sf-moss/10 glass relative overflow-hidden flex flex-col items-center justify-center"
      style="background-color: #F2F3D9;">
      <h1 class="font-hero text-4xl md:text-5xl text-sf-deep font-extrabold mb-5">
        Stillframe
      </h1>
      <p class="text-lg md:text-xl text-sf-moss mb-7 font-medium">
        Return to your roots. Create. Reflect. Continue.<br>
        <span class="italic text-sf-deep font-hero">A place for mindful creators</span>
      </p>
      <div class="flex justify-center gap-6 mt-8 flex-col sm:flex-row">
        <a href="register.php" class="rounded-xl px-8 py-3 bg-sf-moss text-white text-lg font-semibold shadow hover:bg-sf-deep transition">Register</a>
        <a href="login.php" class="rounded-xl px-8 py-3 border-2 border-sf-moss text-sf-moss text-lg font-semibold shadow bg-white hover:bg-sf-moss hover:text-white transition">Login</a>
      </div>
    </section>
  </main>

  <footer class="border-t border-sf-moss/10 bg-white/60 mt-auto py-10 px-5">
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
