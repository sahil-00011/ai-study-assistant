<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$isUserLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo htmlspecialchars($_SESSION['theme'] ?? 'light'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - AI-Powered Academic Companion</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Main Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg landing-nav sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
                <div class="sidebar-logo" style="width:34px; height:34px; font-size:1.1rem;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <span>AI Study<span class="text-primary">Assistant</span></span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar">
                <i class="bi bi-list fs-2"></i>
            </button>

            <div class="collapse navbar-collapse" id="landingNavbar">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link px-3" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#benefits">Benefits</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#testimonials">Testimonials</a></li>
                    <li class="nav-item"><a class="nav-link px-3" href="#faq">FAQ</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <button class="topbar-icon-btn theme-toggle-btn" title="Toggle Theme" type="button">
                        <i class="bi bi-moon-stars-fill theme-toggle-icon"></i>
                    </button>

                    <?php if ($isUserLoggedIn): ?>
                        <a href="dashboard.php" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="bi bi-speedometer2"></i> My Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-link text-decoration-none fw-semibold text-primary">Log In</a>
                        <a href="register.php" class="btn btn-primary">Get Started</a>
                    <?php ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="landing-hero text-center">
        <div class="container">
            <div class="hero-badge">
                <i class="bi bi-stars"></i> Next-Gen AI Learning Platform for Students
            </div>
            <h1 class="hero-title mx-auto" style="max-width: 850px;">
                Learn Smarter with AI Study Assistant
            </h1>
            <p class="hero-subtitle">
                Your personal AI-powered learning companion. Generate structured notes, practice adaptive quizzes, understand difficult concepts, and organize your academic schedule in one place.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="<?php echo $isUserLoggedIn ? 'dashboard.php' : 'register.php'; ?>" class="btn btn-primary btn-lg px-4 shadow">
                    <i class="bi bi-rocket-takeoff-fill me-2"></i> Get Started Free
                </a>
                <a href="#features" class="btn btn-secondary btn-lg px-4">
                    <i class="bi bi-compass me-2"></i> Explore Features
                </a>
            </div>

            <!-- Dashboard Preview Visual -->
            <div class="mt-5 pt-3 position-relative">
                <div class="card p-2 mx-auto border-0 shadow-lg" style="max-width: 1000px; border-radius: 20px; background: rgba(255,255,255,0.7); backdrop-filter: blur(8px);">
                    <div class="card-body p-4 text-start">
                        <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger rounded-circle p-1"></span>
                                <span class="badge bg-warning rounded-circle p-1"></span>
                                <span class="badge bg-success rounded-circle p-1"></span>
                                <span class="ms-2 small text-muted">ai-study-assistant.local</span>
                            </div>
                            <span class="badge badge-primary-soft"><i class="bi bi-cpu"></i> OpenAI Responses Engine Active</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-body-tertiary rounded-3 border">
                                    <h6 class="text-primary mb-1"><i class="bi bi-chat-dots-fill me-1"></i> AI Academic Tutor</h6>
                                    <p class="small text-muted mb-0">Ask deep theoretical or coding questions and receive structured mark-wise breakdowns with examples.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-body-tertiary rounded-3 border">
                                    <h6 class="text-primary mb-1"><i class="bi bi-file-earmark-text-fill me-1"></i> Exam Notes Engine</h6>
                                    <p class="small text-muted mb-0">Synthesize comprehensive syllabus topics into quick revision sheets, definitions, and key takeaways.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-body-tertiary rounded-3 border">
                                    <h6 class="text-primary mb-1"><i class="bi bi-patch-question-fill me-1"></i> Adaptive Quizzes</h6>
                                    <p class="small text-muted mb-0">Generate instant MCQs and True/False questions with secure server-side verification and feedback.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Features Overview -->
    <section id="features" class="py-5">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="badge badge-primary-soft mb-2">POWERFUL CAPABILITIES</span>
                <h2 class="fw-bold">Everything You Need for Academic Excellence</h2>
                <p class="text-muted mx-auto" style="max-width: 620px;">
                    Designed specifically for undergraduate and university students across engineering, sciences, and commerce disciplines.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-4 card-hover">
                        <div class="feature-icon-box" style="background: rgba(79, 70, 229, 0.12); color: #4F46E5;">
                            <i class="bi bi-chat-left-quote-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Interactive AI Academic Tutor</h5>
                        <p class="text-muted small mb-0">
                            Get answers calibrated to academic exam standards. Request 2-mark definitions, 5-mark short answers, or 10-mark comprehensive explanations with code snippets.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-4 card-hover">
                        <div class="feature-icon-box" style="background: rgba(16, 185, 129, 0.12); color: #10B981;">
                            <i class="bi bi-journal-richtext"></i>
                        </div>
                        <h5 class="fw-bold mb-2">AI Notes Generator</h5>
                        <p class="text-muted small mb-0">
                            Generate structured, curriculum-aligned notes by subject, topic, and difficulty. Save them to your library, copy in one click, or export to clean print format.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-4 card-hover">
                        <div class="feature-icon-box" style="background: rgba(245, 158, 11, 0.12); color: #F59E0B;">
                            <i class="bi bi-patch-question"></i>
                        </div>
                        <h5 class="fw-bold mb-2">AI Quiz & Test Generator</h5>
                        <p class="text-muted small mb-0">
                            Challenge yourself with dynamic MCQs and True/False questions. Correct answers remain strictly on the server until submission to ensure genuine self-assessment.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-4 card-hover">
                        <div class="feature-icon-box" style="background: rgba(14, 165, 233, 0.12); color: #0EA5E9;">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Smart PDF Summarizer</h5>
                        <p class="text-muted small mb-0">
                            Upload lecture slides, research papers, or syllabus handouts. The AI extracts core principles, bullet summaries, and generates practice questions from the document.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-4 card-hover">
                        <div class="feature-icon-box" style="background: rgba(168, 85, 247, 0.12); color: #A855F7;">
                            <i class="bi bi-calendar-check-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Study Task Planner</h5>
                        <p class="text-muted small mb-0">
                            Organize assignments, lab deadlines, and exam revisions. Track priority, categorize by subject, and view upcoming due dates with a clean visual checklist.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 p-4 card-hover">
                        <div class="feature-icon-box" style="background: rgba(239, 68, 68, 0.12); color: #EF4444;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Progress Analytics</h5>
                        <p class="text-muted small mb-0">
                            Track your weekly study velocity, average quiz score improvements, and learning logs visually with responsive Chart.js interactive charts.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 bg-body-tertiary border-top border-bottom">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="badge badge-primary-soft mb-2">SEAMLESS WORKFLOW</span>
                <h2 class="fw-bold">How AI Study Assistant Works</h2>
                <p class="text-muted">Mastering your coursework in three simple steps</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4">
                        <div class="avatar-circle mx-auto mb-3" style="width:64px; height:64px; font-size:1.5rem; background: var(--primary-color);">1</div>
                        <h5 class="fw-bold">Specify Subject & Topic</h5>
                        <p class="text-muted small">Select your subject and choose your goal—whether you need deep concept explanations, exam notes, or a practice quiz.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <div class="avatar-circle mx-auto mb-3" style="width:64px; height:64px; font-size:1.5rem; background: var(--secondary-color);">2</div>
                        <h5 class="fw-bold">AI Synthesizes Knowledge</h5>
                        <p class="text-muted small">Our OpenAI-powered backend analyzes academic concepts and formats structured educational content with clear examples and definitions.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <div class="avatar-circle mx-auto mb-3" style="width:64px; height:64px; font-size:1.5rem; background: var(--success-color);">3</div>
                        <h5 class="fw-bold">Practice, Save & Excel</h5>
                        <p class="text-muted small">Attempt quizzes, review instant feedback, keep your revision library organized, and ace your semester exams.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits for Students -->
    <section id="benefits" class="py-5">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge badge-primary-soft mb-2">STUDENT-CENTRIC DESIGN</span>
                    <h2 class="fw-bold mb-3">Why Students Choose AI Study Assistant</h2>
                    <p class="text-muted mb-4">
                        Unlike generic chatbots that provide unstructured walls of text, AI Study Assistant is tailored to university grading standards and exam requirements.
                    </p>
                    
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="text-success fs-4"><i class="bi bi-check-circle-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Mark-Oriented Answer Structuring</h6>
                            <p class="text-muted small mb-0">Answers are segmented into Introduction, Formal Definition, Bullet Points, Code/Diagram explanation, and Conclusion.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="text-success fs-4"><i class="bi bi-check-circle-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Cheating-Proof Quiz Validation</h6>
                            <p class="text-muted small mb-0">Quiz keys are kept securely on the MySQL server; you cannot inspect frontend JavaScript to reveal correct answers.</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3">
                        <div class="text-success fs-4"><i class="bi bi-check-circle-fill"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1">Unified Study Dashboard</h6>
                            <p class="text-muted small mb-0">Everything from notes, quiz scores, tasks, and document summaries are persistent in your personal account.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card p-4 shadow-sm border-0 bg-primary text-white" style="border-radius: 20px;">
                        <h4 class="fw-bold mb-3"><i class="bi bi-quote"></i> Designed for Busy College Students</h4>
                        <p class="mb-4 text-white-50">
                            "Before AI Study Assistant, I spent hours compiling scattered notes and formatting answers for semester exams. Now I can generate topic-specific revision cards and test my understanding in minutes."
                        </p>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-circle" style="background: rgba(255,255,255,0.2);">SK</div>
                            <div>
                                <div class="fw-bold">Sahil Kumar</div>
                                <div class="small text-white-50">Computer Science Undergraduate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials (Sample marked content) -->
    <section id="testimonials" class="py-5 bg-body-tertiary border-top">
        <div class="container py-4">
            <div class="text-center mb-5">
                <span class="badge badge-primary-soft mb-2">STUDENT FEEDBACK</span>
                <h2 class="fw-bold">Trusted by Learners Everywhere</h2>
                <p class="text-muted small">Sample student reviews demonstrating platform satisfaction</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="text-warning mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="text-muted small mb-3">
                            "The 5-mark and 10-mark response formatting matches exactly what our professors look for in university exams. It saved me during finals week!"
                        </p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                            <div class="avatar-circle" style="width:32px; height:32px; font-size:0.8rem;">AP</div>
                            <div>
                                <div class="fw-semibold small">Ananya Patel</div>
                                <div class="text-muted" style="font-size:0.75rem;">Information Technology, 3rd Year</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="text-warning mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="text-muted small mb-3">
                            "The PDF summarizer takes dense 40-page lecture slides and generates key revision points and sample quiz questions instantly."
                        </p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                            <div class="avatar-circle" style="width:32px; height:32px; font-size:0.8rem;">RS</div>
                            <div>
                                <div class="fw-semibold small">Rahul Sharma</div>
                                <div class="text-muted" style="font-size:0.75rem;">Electronics & Comm., 4th Year</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 p-4">
                        <div class="text-warning mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="text-muted small mb-3">
                            "Having tasks, notes, and quiz score analytics in one clean dashboard keeps me disciplined throughout the semester."
                        </p>
                        <div class="d-flex align-items-center gap-2 mt-auto">
                            <div class="avatar-circle" style="width:32px; height:32px; font-size:0.8rem;">PV</div>
                            <div>
                                <div class="fw-semibold small">Pooja Verma</div>
                                <div class="text-muted" style="font-size:0.75rem;">Computer Applications (MCA)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-5">
        <div class="container py-4" style="max-width: 800px;">
            <div class="text-center mb-5">
                <span class="badge badge-primary-soft mb-2">FREQUENTLY ASKED QUESTIONS</span>
                <h2 class="fw-bold">Got Questions? We Have Answers</h2>
            </div>

            <div class="accordion" id="faqAccordion">
                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How does the AI Academic Chatbot differ from standard ChatGPT?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted small">
                            Our chatbot is prompted with a specialized academic persona. It answers questions using formal definitions, structured points, code explanations, and university exam formats (e.g. 2-mark definitions or 10-mark essays), rather than unstructured conversational text.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            Can I upload my own lecture notes and PDFs?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted small">
                            Yes! The PDF Summarizer module allows you to upload lecture slides and textbooks. The system extracts the text securely and lets you generate key summary bullets, revision flash notes, and practice MCQs directly from the uploaded material.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How are quiz answers and scores evaluated?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted small">
                            Quiz questions are generated by the AI and saved securely in your MySQL database. Correct answers are kept strictly on the backend and are never sent to the browser during the quiz. When you submit your answers, the PHP backend verifies them and calculates your score.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border rounded-3 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Is my study data and conversation history saved?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted small">
                            Yes, all conversations, generated notes, quiz attempts, study tasks, and PDF summaries are stored securely in MySQL and tied specifically to your student account.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white text-center">
        <div class="container py-4">
            <h2 class="fw-bold mb-3">Ready to Elevate Your Academic Journey?</h2>
            <p class="text-white-50 mx-auto mb-4" style="max-width: 580px;">
                Join students who are learning faster, studying smarter, and achieving higher grades with AI Study Assistant.
            </p>
            <a href="<?php echo $isUserLoggedIn ? 'dashboard.php' : 'register.php'; ?>" class="btn btn-light btn-lg px-4 fw-bold text-primary shadow">
                Get Started Now <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 border-top">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="sidebar-logo" style="width:28px; height:28px; font-size:0.9rem;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <span class="fw-bold text-primary">AI Study Assistant</span>
                <span class="text-muted small">&copy; <?php echo date('Y'); ?> All rights reserved.</span>
            </div>
            <ul class="nav">
                <li class="nav-item"><a href="index.php" class="nav-link px-2 text-muted small">Home</a></li>
                <li class="nav-item"><a href="login.php" class="nav-link px-2 text-muted small">Login</a></li>
                <li class="nav-item"><a href="register.php" class="nav-link px-2 text-muted small">Register</a></li>
                <li class="nav-item"><a href="admin/index.php" class="nav-link px-2 text-muted small">Admin Portal</a></li>
            </ul>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>

