<?php
session_start();
include 'includes/db.php';
// Remove error display in production; log errors securely instead:
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Secure error logging for debugging (log to file, not browser)
// ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log');

$error = "";
$success = "";
$redirect = false;

// Mark that the user is in the registration process
$_SESSION['registering'] = true;

// Helper to safely escape output
function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Defensive: DB connection error
if (!$conn || $conn->connect_error) {
    $error = "Database connection error. Please try again later.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error)) {

    // Always trim inputs and ensure variables exist
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Basic validation
    if (empty($email) || empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // 2. Email format check
        $error = "Invalid email format.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        // 3. Password strength check
        $error = "Password must be at least 8 characters long and include uppercase, lowercase, number, and symbol.";
    } else {
        // 4. Check if email or username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $username);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $error = "Email or Username already exists.";
                } else {
                    // 5. Hash password securely
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // 6. Insert new user with prepared statement
                    $insert = $conn->prepare("INSERT INTO users (email, username, password, role) VALUES (?, ?, ?, 'user')");
                    if ($insert) {
                        $insert->bind_param("sss", $email, $username, $hashedPassword);
                        if ($insert->execute()) {
                            $success = "You're all set! Redirecting to sign in...";
                            $error = "";
                            $_SESSION['registered_user'] = true;
                            unset($_SESSION['registering']);
                            $redirect = true;
                        } else {
                            $error = "Registration failed. Please try again.";
                        }
                        $insert->close();
                    } else {
                        $error = "Server error. Please contact support.";
                    }
                }
            } else {
                $error = "Something went wrong. Try again.";
            }
            $stmt->close();
        } else {
            $error = "Database query error. Please contact support.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Register — Stillframe</title>
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
      :root { --bg-image: url('pictures/register.jpg'); }
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
        background: rgba(255,255,255,0.26);
        border-radius: 16px;
        box-shadow: 0 4px 30px rgba(38,59,47,0.12), 0 1.5px 12px rgba(40,54,24,0.07), 0 1px 0 rgba(255,255,255,0.54) inset;
        backdrop-filter: blur(14px) saturate(120%) brightness(1.08);
        -webkit-backdrop-filter: blur(14px) saturate(120%) brightness(1.08);
        border: 1.4px solid rgba(96, 108, 56, 0.16);
        transition: box-shadow 0.18s, backdrop-filter 0.18s;
      }
      .glass:hover {
        box-shadow: 0 6px 38px rgba(38,59,47,0.16), 0 2px 14px rgba(40,54,24,0.10), 0 1px 0 rgba(255,255,255,0.65) inset;
        backdrop-filter: blur(16px) saturate(125%) brightness(1.13);
        -webkit-backdrop-filter: blur(16px) saturate(125%) brightness(1.13);
      }
    </style>
</head>
<body class="h-full page-bg antialiased text-slate-900 font-display">
  <main class="min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-6 lg:px-12">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-stretch">
        <!-- LEFT: Brand and quote over image -->
        <section class="relative flex flex-col justify-center p-8 md:p-12 lg:p-16 rounded-2xl text-white overflow-hidden" aria-labelledby="stillframe-heading">
          <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/20 to-black/40 rounded-2xl pointer-events-none"></div>
          <div class="z-10">
            <h1 id="stillframe-heading" class="text-4xl md:text-5xl font-extrabold tracking-tight mb-4 text-cream-50">Stillframe</h1>
            <p class="max-w-xl text-lg md:text-xl opacity-95 text-cream-100">Return to your roots. Create. Reflect. Continue.</p>
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
        <!-- RIGHT: Registration form with glass effect -->
        <aside class="flex items-center justify-center">
          <div class="w-full max-w-md glass rounded-2xl shadow-2xl p-8 md:p-10">
            <header class="mb-6">
              <h2 class="text-2xl font-semibold text-slate-800">Create an Account</h2>
              <p class="text-sm text-slate-100 mt-1">Sign up to continue to Stillframe</p>
            </header>
            <?php if(!empty($error)): ?>
                <div role="alert" class="mb-4 p-3 rounded-md bg-red-50 text-red-700 border border-red-100">
                  <?php echo esc($error); ?>
                </div>
            <?php elseif(!empty($success)): ?>
                <div role="status" class="mb-4 p-3 rounded-md bg-green-50 text-green-700 border border-green-100">
                  <?php echo esc($success); ?>
                </div>
                <?php if ($redirect): ?>
                <script>
                  setTimeout(function(){
                    window.location.href = 'login.php';
                  }, 1600);
                </script>
                <?php endif; ?>
            <?php endif; ?>
            <form class="register-form space-y-4" method="POST" action="" novalidate>
              <label class="block">
                <span class="text-sm font-medium text-slate-200">Email</span>
                <input type="email" id="email" name="email" required
                       class="mt-1 block w-full rounded-lg border border-slate-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sf-moss focus:border-transparent"
                       placeholder="you@email.com" autocomplete="email">
              </label>
              <label class="block">
                <span class="text-sm font-medium text-slate-200">Username</span>
                <input type="text" id="username" name="username" required
                       class="mt-1 block w-full rounded-lg border border-slate-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sf-moss focus:border-transparent"
                       placeholder="your username" autocomplete="username">
              </label>
              <label class="block">
                <span class="text-sm font-medium text-slate-200">Password</span>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full rounded-lg border border-slate-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-sf-moss focus:border-transparent"
                       placeholder="At least 8 chars, 1 capital, 1 symbol"
                       pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}" autocomplete="new-password">
                <small class="text-slate-100">
                  Must contain at least 8 characters, including uppercase, lowercase, number, and symbol.
                </small>
              </label>
              <button type="submit" class="w-full mt-4 inline-flex items-center gap-2 rounded-lg bg-sf-moss px-4 py-2 text-white font-semibold shadow hover:bg-sf-deep transition-colors">
                Register
              </button>
            </form>
            <div class="mt-6 border-t pt-4 text-sm text-slate-200">
              Already have an account?
              <a href="login.php" class="text-sf-deep font-medium hover:underline"
                 onclick="return confirm('You have not completed registration yet. Continue anyway?')">
                Login here
              </a>
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
