# AI Study Assistant - Full-Stack Educational Web Platform

A comprehensive, dynamic, full-stack educational web application designed for college and university students. The platform leverages modern generative AI (OpenAI API), Core PHP 8+, and MySQL to provide an intelligent academic tutor, syllabus notes generator, cheating-proof quiz evaluation engine, task planner, and smart lecture PDF summarizer.

---

## 1. System Architecture & Tech Stack

- **Frontend:** HTML5, CSS3, Vanilla JavaScript, Bootstrap 5.3, Bootstrap Icons, Chart.js.
- **Backend:** Core PHP 8.0+ (Strictly no heavy frameworks like Laravel, Node.js, or React).
- **Database:** MySQL 8.0+ / MariaDB 10.4+ (InnoDB engine, foreign keys with `CASCADE`).
- **AI Integration:** OpenAI API (`gpt-4o-mini`, `gpt-4o`, `gpt-3.5-turbo`) via server-side PHP cURL.
- **Local Server:** XAMPP (Apache + MySQL/MariaDB).
- **Security:** PDO prepared statements, bcrypt password hashing, CSRF tokens on all state-changing endpoints, session fixation prevention, server-side quiz grading, rate limiting, and script execution prevention in upload directories.

---

## 2. Project Directory Structure

```
ai-study-assistant/
│
├── index.php                  # Public landing page with features, testimonials & FAQ
├── login.php                  # Secure authentication with bcrypt verification & remember me
├── register.php               # Student registration with password strength meter
├── logout.php                 # Safe session destruction and redirection
├── dashboard.php             # Student dashboard with MySQL statistics & Chart.js graphs
├── profile.php               # Student profile details and academic specializations
├── chatbot.php               # ChatGPT-style Academic AI Tutor interface
├── notes.php                 # AI Notes Generator & saved study notes library
├── quiz.php                  # Interactive AI Quiz generator with server-side validation
├── planner.php               # Study task management and deadline tracking
├── history.php               # Student learning activity audit log
├── pdf-summarizer.php        # Upload lecture slides & generate revision summaries
├── settings.php              # Security, password change, theme toggle & account management
│
├── admin/                    # Administrative Control Panel
│   ├── index.php             # Admin route verification
│   ├── dashboard.php         # Platform analytics, active users, total quizzes & notes
│   ├── users.php             # User management, role elevation & account activation
│   ├── subjects.php          # Academic course catalogue and icon management
│   └── activity.php          # Platform-wide audit logs
│
├── api/                      # REST-style JSON API Endpoints
│   ├── chat.php              # Conversation threads & AI academic tutor
│   ├── generate-notes.php    # AI Notes generation & library management
│   ├── generate-quiz.php     # AI Quiz questions generation (excludes answer keys)
│   ├── submit-quiz.php       # Server-side quiz evaluation & score calculation
│   ├── tasks.php             # Study planner CRUD operations
│   ├── profile.php           # Profile updates, password changes & account deletion
│   ├── history.php           # User learning history logs
│   ├── upload-pdf.php        # PDF upload, MIME validation & pure-PHP text extraction
│   └── summarize-pdf.php     # AI document summarizer & practice questions generator
│
├── config/
│   ├── config.php            # Global constants, dynamic base URL & session security
│   ├── database.php          # PDO connection singleton & connection error diagnostics
│   └── openai.php            # OpenAI API cURL client, model selection & error handlers
│
├── includes/
│   ├── auth.php              # Role-based access control, session authentication guards
│   ├── csrf.php              # CSRF token generator & validator (Form & Header/JSON)
│   ├── functions.php         # Sanitization, JSON responses, rate limiters & formatters
│   ├── header.php            # Authenticated topbar, theme toggler & user menu
│   ├── sidebar.php           # Dark navy responsive navigation sidebar
│   ├── footer.php            # Authenticated footer & dynamic script loader
│   └── PdfExtractor.php      # Pure PHP PDF text extractor (Zero external binaries needed)
│
├── assets/
│   ├── css/
│   │   ├── style.css         # SaaS color palette, CSS variables, dark mode styles
│   │   ├── dashboard.css     # Dashboard layout, stat cards, chat bubbles & quiz cards
│   │   └── responsive.css    # Mobile, tablet, and desktop media queries
│   │
│   ├── js/
│   │   ├── app.js            # Global toasts, theme switcher, CSRF header injector
│   │   ├── chatbot.js        # AI chat manager, code blocks formatting & copy buttons
│   │   ├── notes.js          # Notes generator, download (.md), print & search logic
│   │   ├── quiz.js           # Multi-question test runner, timer & review renderer
│   │   └── planner.js        # Dynamic task filtering, status toggling & edit modals
│   │
│   └── images/               # Media and branding assets
│
├── uploads/
│   ├── .htaccess             # Hardened security: Disables PHP engine in uploads
│   └── index.html            # Prevents directory indexing
│
├── database/
│   └── ai_study_assistant.sql # Complete 14-table schema with foreign keys and seeds
│
├── README.md
└── .gitignore
```

---

## 3. Installation & Setup Guide (XAMPP)

### Step 1: Install XAMPP
1. Download and install **XAMPP for Windows** (with PHP 8.0 or higher) from [apachefriends.org](https://www.apachefriends.org).
2. Open the **XAMPP Control Panel**.
3. Click **Start** for **Apache** and **MySQL**.

### Step 2: Project Placement
Ensure the project is located inside your XAMPP web root directory:
```
C:/xampp/htdocs/ai-study-assistant/
```
*(If you developed in another workspace, create a directory junction using `cmd /c mklink /J "C:\xampp\htdocs\ai-study-assistant" "YOUR_WORKSPACE_PATH"`).*

### Step 3: Database Setup
1. Open your browser and navigate to **phpMyAdmin**:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click on the **Databases** tab, type `ai_study_assistant` as the database name, and click **Create**.
3. Select the newly created `ai_study_assistant` database from the left navigation panel.
4. Go to the **Import** tab.
5. Click **Choose File**, select `database/ai_study_assistant.sql` from the project directory, and click **Import** (or **Go**).
6. Verify that the 14 tables have been created:
   - `users`, `user_settings`, `subjects`, `chat_conversations`, `chat_messages`
   - `notes`, `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_answers`
   - `study_tasks`, `pdf_documents`, `pdf_summaries`, `activity_logs`

### Step 4: Configure Database Connection
Open `config/database.php` and verify your credentials (default XAMPP credentials):
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ai_study_assistant');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Step 5: Configure OpenAI API Key
Open `config/openai.php`:
```php
define('OPENAI_API_KEY', 'YOUR_ACTUAL_OPENAI_API_KEY_HERE');
define('OPENAI_MODEL', 'gpt-4o-mini'); // or gpt-4o, gpt-3.5-turbo
```
*Note: You can also set `OPENAI_API_KEY` as an environment variable in Windows or in Apache's `httpd.conf`.*

### Step 6: Verify PHP Extensions
Ensure standard extensions are enabled in `C:\xampp\php\php.ini`:
- `extension=curl` (enabled by default in XAMPP)
- `extension=pdo_mysql` (enabled by default in XAMPP)
- `extension=mbstring` (enabled by default in XAMPP)
- `extension=fileinfo` (enabled by default in XAMPP)

### Step 7: Launch Website
Open your browser and navigate to:
```
http://localhost/ai-study-assistant/
```

---

## 4. Default Credentials

### Administrator Portal
- **URL:** `http://localhost/ai-study-assistant/admin/`
- **Email:** `admin@studyassistant.ai`
- **Password:** `Admin@12345`

### Student Registration
- You can register as many student accounts as you wish via:
  `http://localhost/ai-study-assistant/register.php`
- All student performance data, notes, and quiz histories remain completely private and isolated to the logged-in student.

---

## 5. Key Feature Highlights

### 1. AI Academic Tutor (`chatbot.php`)
- **Academic Persona:** Calibrated to university exam marking criteria.
- **Answer Formatting Options:**
  - *Standard Explanation:* Definition, theory, code/architecture, and conclusion.
  - *2-Mark Answer:* High-scoring concise definition, formula, and example (60-80 words).
  - *5-Mark Answer:* Pointwise breakdown with architecture or code snippet.
  - *10-Mark Answer:* Comprehensive semester exam essay with deep analysis.
  - *Simplified Analogy (ELI5):* Intuitive everyday analogy followed by technical wrap-up.
- **Copy & Code Formatting:** Code blocks with syntax highlighting and 1-click clipboard copy.

### 2. AI Notes Generator (`notes.php`)
- Generates curriculum-aligned notes by subject, topic, and academic level.
- Formats: Detailed Textbook Notes, Short Notes, Exam Prep Notes, Revision Cheat Sheet, or Q&A.
- Instant 1-click actions: Copy, Download as Markdown/Text file, and Print-friendly layout.
- Searchable notes library saved in MySQL.

### 3. Cheating-Proof AI Quiz Generator (`quiz.php`)
- Supports **Multiple Choice Questions (4 options)** and **True/False**.
- **Crucial Security:** The server **never** sends correct answer keys or explanations to the browser during the quiz.
- Questions are attempted one by one with pill navigation, answer state tracking, and a live timer.
- All scores are calculated strictly on the PHP backend upon submission.
- Detailed question-by-question review with explanations and score breakdown.

### 4. Smart PDF Summarizer (`pdf-summarizer.php`)
- Pure PHP PDF text extractor (`includes/PdfExtractor.php`) with zero external dependencies.
- Restricts file types to valid MIME `application/pdf` (max 10MB).
- Uploads directory hardened with `.htaccess` to prevent script execution.
- Generates Executive Summaries, Key Definitions, Revision Bullets, and Practice Questions.

### 5. Study Task Planner (`planner.php`)
- Full task tracking: Title, description, subject category, priority, and due dates.
- Interactive checkboxes with instant strike-through animations.
- Filter by status (All, Pending, Completed), category, priority, and live search.
- Summary metric counters for pending, completed, overdue, and total tasks.

### 6. Learning History (`history.php`)
- Complete audit trail of all student learning activities: AI chats, notes generated, quizzes completed, and logins.
- Real-time search, filter by activity type, and sort by date.

### 7. Administration Panel (`admin/`)
- Platform-wide statistics: Registered students, active accounts, total AI interactions, total quizzes taken.
- User management: Activate or suspend student accounts, manage administrative roles.
- Subjects catalog: Add and toggle visibility of curriculum subjects and icons.
- Platform audit logging.

---

## 6. Security Features Implemented

1. **SQL Injection Defense:** All database queries utilize **PHP PDO Prepared Statements** with parameterized inputs.
2. **Password Security:** Passwords hashed with `password_hash($pwd, PASSWORD_BCRYPT)` and verified with `password_verify()`.
3. **CSRF Protection:** Cryptographically secure CSRF tokens verified on every state-changing POST request and AJAX call (`X-CSRF-Token` header).
4. **Session Hardening:** `session_regenerate_id(true)` upon login, `HttpOnly` cookies, `SameSite=Lax`, and 30-minute inactivity timeouts.
5. **Secure Uploads:** File MIME-type verification, extension validation, random server-side filename generation, and `php_flag engine off` via `uploads/.htaccess`.
6. **Rate Limiting:** Session-based rate limiters on AI and task endpoints to prevent abusive request loops.
7. **Role-Based Authorization:** Strict `requireAdmin()` and `requireAuth()` guards on all protected controllers.

---

## 7. Troubleshooting

| Issue | Cause | Solution |
| :--- | :--- | :--- |
| **"Database Not Initialized"** | The `ai_study_assistant` database has not been imported. | Open phpMyAdmin (`http://localhost/phpmyadmin/`), create `ai_study_assistant`, and import `database/ai_study_assistant.sql`. |
| **"Can't connect to MySQL server"** | MySQL service is stopped in XAMPP. | Open XAMPP Control Panel and click **Start** next to MySQL. |
| **"OpenAI API key is not configured"** | Missing or default API key in `config/openai.php`. | Place your OpenAI API key in `config/openai.php` or set the `OPENAI_API_KEY` environment variable. |
| **"Rate limit or quota exceeded"** | Your OpenAI account has exceeded its credit quota. | Verify your OpenAI billing balance at [platform.openai.com](https://platform.openai.com). |
| **Port 80/443 Conflict** | Another software (e.g. Skype, IIS) is using port 80. | In XAMPP Control Panel, click Apache **Config** -> `httpd.conf` and change `Listen 80` to `Listen 8080`. The website will then be accessible at `http://localhost:8080/ai-study-assistant/`. |

---

## 8. License & Academic Demonstration
Developed for undergraduate Computer Science and Engineering capstone and final-year project demonstrations.
Designed with educational SaaS standards, modular design patterns, and responsive UI principles.

