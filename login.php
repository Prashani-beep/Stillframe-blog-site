<?php
session_start(); // Needed for login sessions

// Block access if user hasn't completed registration
if (isset($_SESSION['registering']) && !isset($_SESSION['registered_user'])) {
    header("Location: register.php");
    exit();
}

include 'includes/db.php'; // Your DB connection

$error = "";
$success = "";
$redirect = false;

// Basic DB connection error handling (assuming your db.php sets $conn)
if (!$conn || $conn->connect_error) {
    $error = "Database connection error. Please try again later.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {
    $user = trim($_POST['user'] ?? "");
    $password = $_POST['password'] ?? "";

    if (empty($user) || empty($password)) {
        $error = "All fields are required.";
    } else {
        // Look up user by email or username
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE email = ? OR username = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $user, $user);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    // Verify the hashed password
                    if (password_verify($password, $row['password'])) {
                        // Session fixation protection
                        session_regenerate_id(true);
                        // Password correct — create session
                        $_SESSION['userid'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        // Remove any old registration session flags
                        unset($_SESSION['registering']);
                        unset($_SESSION['registered_user']);
                        // Set success message and redirect flag
                        $success = "Login successful — just a moment!";
                        $redirect = true;
                    } else {
                        $error = "Incorrect password.";
                    }
                } else {
                    $error = "No user found with that email or username.";
                }
            } else {
                $error = "Something went wrong during login. Try again.";
            }
            $stmt->close();
        } else {
            $error = "Database query error. Please contact support.";
        }
    }
}

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login — Stillframe</title>
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
                'display': ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif']
              }
            }
          }
        }
    </script>
    <style>
      html, body { height: 100%; }
      :root {
        --bg-image: url('pictures/hero.jpg');
      }
      .page-bg {
        background-image:
          linear-gradient(rgba(8,10,8,0.48), rgba(8,10,8,0.48)),
          var(--bg-image);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
      }
      @media (max-width: 640px) {
        .page-bg {
          background-image:
            linear-gradient(rgba(8,10,8,0.32), rgba(8,10,8,0.32)),
            var(--bg-image);
        }
      }
      .glass {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(6px) saturate(120%);
      }
    </style>
</head>
<body class="h-full page-bg antialiased text-slate-900 font-display">

  <main class="min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-6 lg:px-12">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">

        <!-- LEFT: Quote / Brand -->
        <section class="relative flex flex-col justify-center p-8 md:p-12 lg:p-16 rounded-2xl text-white overflow-hidden"
                 aria-labelledby="stillframe-heading">
          <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/20 to-black/40 rounded-2xl pointer-events-none"></div>

          <div class="z-10">
            <h1 id="stillframe-heading" class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4 text-cream-50">Stillframe</h1>
            <p class="max-w-xl text-lg md:text-xl opacity-95 text-cream-100">
              Return to your roots. Create. Reflect.Continue.
            </p>
            <div class="mt-8 flex items-center gap-4">
              <svg class="w-10 h-10 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v4a4 4 0 004 4h10"></path>
              </svg>
              <div>
                <p class="text-sm font-medium opacity-90 text-cream-100">Designed for storytellers!</p>
                <p class="text-xs opacity-80 text-cream-100">Minimal. Thoughtful. Timeless.</p>
              </div>
            </div>
          </div>
          <div class="mt-8 text-xs opacity-60 z-10 text-cream-100">
            © <?php echo date("Y"); ?> Stillframe
          </div>
        </section>

        <!-- RIGHT: Login card -->
        <aside class="flex items-center justify-center">
          <div class="w-full max-w-md glass rounded-2xl shadow-2xl p-8 md:p-10">
            <header class="mb-6">
              <h2 class="text-2xl font-semibold text-slate-800">Welcome back</h2>
              <p class="text-sm text-slate-500 mt-1">Sign in to continue to Stillframe</p>
            </header>
            <?php if (!empty($error)): ?>
              <div role="alert" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 border border-red-100">
                <?php echo esc($error); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
              <div role="status" class="mb-4 p-3 rounded-md bg-green-50 text-green-700 border border-green-100">
                <?php echo esc($success); ?>
              </div>
              <?php if ($redirect): ?>
                <script>
                  setTimeout(function(){
                      window.location.href = 'home.php';
                  }, 1200);
                </script>
              <?php endif; ?>
            <?php endif; ?>
            <form method="POST" action="" class="space-y-4" novalidate>
              <label class="block">
                <span class="text-sm font-medium text-slate-700">Email or Username</span>
                <input
                  type="text"
                  name="user"
                  required
                  autocomplete="username"
                  class="mt-1 block w-full rounded-lg border border-slate-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sf-moss focus:border-transparent"
                  placeholder="you@email.com or username"
                >
              </label>
              <label class="block">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-slate-700">Password</span>
                  
                </div>
                <input
                  type="password"
                  name="password"
                  required
                  autocomplete="current-password"
                  class="mt-1 block w-full rounded-lg border border-slate-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sf-moss focus:border-transparent"
                  placeholder="••••••••"
                >
              </label>
              <div class="flex items-center justify-between gap-4">
                <label class="flex items-center text-sm select-none">
                  <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-sf-moss focus:ring-sf-moss">
                  <span class="ml-2 text-slate-600">Remember me</span>
                </label>
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-sf-moss px-4 py-2 text-white font-semibold shadow hover:bg-sf-deep transition-colors">
                  Log in
                </button>
              </div>
            </form>
            <div class="mt-6 border-t pt-4 text-sm text-slate-600">
              Don't have an account?
              <a href="register.php" class="text-sf-deep font-medium hover:underline">Create one</a>
            </div>
            <footer class="mt-6 text-xs text-slate-400">
              Protected by secure hashing. Stillframe • Crafted with care.
            </footer>
          </div>
        </aside>
      </div>
    </div>
  </main>
  <script>
    (function(){
      document.addEventListener('keydown', function(e){
        if(e.key === 'Tab') document.documentElement.classList.add('user-is-tabbing');
      });
    })();
  </script>
</body>
</html>
