<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="index.css">
  <script src="https://kit.fontawesome.com/a4961fc538.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <title>BugTracker</title>
</head>

<body>

  <div class="container">
    <div class="header">
      <p class="website-name">🪲 Bug<span class="blue-text">Tracker</span></p>
      <div class="section">
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
      </div>
      <div class="btns">
        <button class="login-btn" onclick="window.location.href='login.php'">Login</button>
        <button class="getstarted-btn" onclick="window.location.href='register.php'">Get Started</button>
      </div>
    </div>

    <div class="body">
      <div class="box">
        <div class="texts">
          <h1>Track Bugs. Ship Better</h1>
          <h1>Software.</h1>
        </div>
        <div class="texts-2">
          <p>Powerful yet simple bug tracking tool for your team.</p>
          <p>Report, track and resolve issues faster.</p>
        </div>
        <div class="buttonholder">
          <button class="Get-Started-btn" onclick="window.location.href='register.php'">Get Started Free</button>
          <button class="Learn-More-btn" onclick="window.location.href='#features'">Learn More</button>
        </div>
      </div>
      <div class="hero-image-box">
        <img class="image-landingpage" src="Open source-rafiki.png" alt="Bug Tracking Illustration">
      </div>
    </div>

    <div class="features-section" id="features">
      <h2>Everything You Need to Ship Better</h2>
      <div class="features-grid">
        <div class="feature-card">
          <div class="iconbox-1"><i class="fa-solid fa-pen" style="color:#2E7EFE;"></i></div>
          <p class="feature-title">Create & Track</p>
          <p class="feature-desc">Easily create and track bugs across all your projects in one place.</p>
        </div>
        <div class="feature-card">
          <div class="iconbox-2"><i class="fa-solid fa-user-group"></i></div>
          <p class="feature-title">Assign & Collaborate</p>
          <p class="feature-desc">Assign issues to team members and collaborate in real time.</p>
        </div>
        <div class="feature-card">
          <div class="iconbox-3"><i class="fa-solid fa-bars-progress"></i></div>
          <p class="feature-title">Prioritize</p>
          <p class="feature-desc">Set priority levels so nothing critical gets missed.</p>
        </div>
        <div class="feature-card">
          <div class="iconbox-4"><i class="fa-solid fa-bug" style="color:#FFD43B;"></i></div>
          <p class="feature-title">Reports</p>
          <p class="feature-desc">Insights and reports to improve quality and track progress.</p>
        </div>
      </div>
    </div>


    <div class="how-section" id="how-it-works">
      <h2>Up and Running in 3 Steps</h2>
      <div class="steps-grid">
        <div class="step-card">
          <div class="step-number">1</div>
          <p class="step-title">Create Your Account</p>
          <p class="step-desc">Sign up for free and set up your first project in minutes.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step-card">
          <div class="step-number">2</div>
          <p class="step-title">Report & Assign Bugs</p>
          <p class="step-desc">Create issues, set priority, and assign them to your team.</p>
        </div>
        <div class="step-arrow">→</div>
        <div class="step-card">
          <div class="step-number">3</div>
          <p class="step-title">Track & Resolve</p>
          <p class="step-desc">Monitor progress, leave comments, and close issues faster.</p>
        </div>
      </div>
    </div>


    <div class="page-footer">
      <p>© 2026 BugTracker. All rights reserved.</p>
    </div>
  </div>
</body>

</html>