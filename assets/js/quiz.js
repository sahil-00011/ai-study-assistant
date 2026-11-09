/**
 * AI Quiz Generator & Interactive Testing Engine
 * AI Study Assistant
 */

let activeQuiz = null;
let currentQuestionIndex = 0;
let userAnswers = {}; // question_id => selected_key
let timerSeconds = 0;
let timerInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    const generateForm = document.getElementById('generateQuizForm');
    const startBtn = document.getElementById('startQuizBtn');
    const prevBtn = document.getElementById('prevQuestionBtn');
    const nextBtn = document.getElementById('nextQuestionBtn');
    const submitBtn = document.getElementById('submitQuizBtn');
    const cancelBtn = document.getElementById('cancelQuizBtn');
    const takeAnotherBtn = document.getElementById('takeAnotherQuizBtn');

    // Generate & Start Quiz Form
    if (generateForm) {
        generateForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const subject = document.getElementById('quizSubject').value.trim();
            const topic = document.getElementById('quizTopic').value.trim();
            const difficulty = document.getElementById('quizDifficulty').value;
            const count = parseInt(document.getElementById('quizCount').value, 10);
            const type = document.getElementById('quizType').value;

            if (!subject || !topic) {
                showToast('Please specify subject and topic.', 'warning');
                return;
            }

            // Button loading state
            startBtn.disabled = true;
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Generating Quiz Questions...';

            try {
                const res = await fetch('api/generate-quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject,
                        topic,
                        difficulty,
                        question_count: count,
                        question_type: type
                    })
                });

                const data = await res.json();
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="bi bi-play-circle-fill me-1"></i> <span>Generate & Start Quiz</span>';

                if (!res.ok || !data.success) {
                    showToast(data.error || 'Failed to generate quiz.', 'error');
                } else {
                    startQuizSession(data);
                }
            } catch (err) {
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="bi bi-play-circle-fill me-1"></i> <span>Generate & Start Quiz</span>';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }

    // Previous Button
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                renderActiveQuestion();
            }
        });
    }

    // Next Button
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (activeQuiz && currentQuestionIndex < activeQuiz.questions.length - 1) {
                currentQuestionIndex++;
                renderActiveQuestion();
            }
        });
    }

    // Submit Quiz Button
    if (submitBtn) {
        submitBtn.addEventListener('click', () => {
            if (!activeQuiz) return;

            const total = activeQuiz.questions.length;
            const answeredCount = Object.keys(userAnswers).length;

            if (answeredCount < total) {
                const unanswered = total - answeredCount;
                if (!confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`)) {
                    return;
                }
            } else {
                if (!confirm('Are you ready to submit your quiz for evaluation?')) {
                    return;
                }
            }

            submitQuizForGrading();
        });
    }

    // Cancel Quiz Button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (confirm('Are you sure you want to exit? Your quiz progress will be lost.')) {
                stopTimer();
                switchToSection('quizConfigSection');
            }
        });
    }

    // Take Another Quiz Button
    if (takeAnotherBtn) {
        takeAnotherBtn.addEventListener('click', () => {
            switchToSection('quizConfigSection');
        });
    }
});

/**
 * Start the interactive quiz test runner
 */
function startQuizSession(quizData) {
    activeQuiz = quizData;
    currentQuestionIndex = 0;
    userAnswers = {};
    timerSeconds = 0;

    document.getElementById('runnerSubjectBadge').textContent = quizData.subject;
    document.getElementById('runnerDifficultyBadge').textContent = quizData.difficulty.toUpperCase();
    document.getElementById('runnerTopicTitle').textContent = quizData.topic;

    switchToSection('quizRunnerSection');
    renderQuestionPills();
    renderActiveQuestion();
    startTimer();
}

/**
 * Renders the question pills navigation
 */
function renderQuestionPills() {
    const container = document.getElementById('questionPillNav');
    if (!container || !activeQuiz) return;

    container.innerHTML = '';
    activeQuiz.questions.forEach((q, idx) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-sm ${idx === currentQuestionIndex ? 'btn-primary' : 'btn-outline-secondary'}`;
        btn.style.width = '36px';
        btn.style.height = '36px';
        btn.style.borderRadius = '50%';
        btn.style.padding = '0';
        btn.style.fontWeight = '600';
        btn.textContent = idx + 1;
        btn.id = `pill-q-${q.id}`;

        if (userAnswers[q.id]) {
            btn.classList.add('border-success');
        }

        btn.onclick = () => {
            currentQuestionIndex = idx;
            renderActiveQuestion();
        };

        container.appendChild(btn);
    });
}

/**
 * Render the current question and options
 */
function renderActiveQuestion() {
    if (!activeQuiz || !activeQuiz.questions[currentQuestionIndex]) return;

    const q = activeQuiz.questions[currentQuestionIndex];
    const total = activeQuiz.questions.length;

    // Header progress
    document.getElementById('runnerQuestionProgressText').textContent = `Question ${currentQuestionIndex + 1} of ${total}`;
    const answeredCount = Object.keys(userAnswers).length;
    document.getElementById('runnerAnsweredCountText').textContent = `${answeredCount} Answered`;

    const pct = Math.round(((currentQuestionIndex + 1) / total) * 100);
    document.getElementById('quizProgressBar').style.width = `${pct}%`;

    // Question text
    document.getElementById('currentQuestionText').textContent = `${currentQuestionIndex + 1}. ${q.question}`;

    // Options container
    const optContainer = document.getElementById('optionsContainer');
    optContainer.innerHTML = '';

    const options = q.options;
    const selectedKey = userAnswers[q.id];

    Object.keys(options).forEach(key => {
        const optCard = document.createElement('div');
        optCard.className = `quiz-option-card ${selectedKey === key ? 'selected' : ''}`;

        optCard.innerHTML = `
            <div class="quiz-option-key">${key}</div>
            <div class="fw-medium">${escapeHtml(options[key])}</div>
        `;

        optCard.onclick = () => {
            userAnswers[q.id] = key;
            renderActiveQuestion();
            updatePillState(q.id);
        };

        optContainer.appendChild(optCard);
    });

    // Button states
    document.getElementById('prevQuestionBtn').disabled = (currentQuestionIndex === 0);
    const nextBtn = document.getElementById('nextQuestionBtn');
    if (currentQuestionIndex === total - 1) {
        nextBtn.classList.add('d-none');
    } else {
        nextBtn.classList.remove('d-none');
    }

    // Highlight active pill
    document.querySelectorAll('#questionPillNav button').forEach((btn, idx) => {
        if (idx === currentQuestionIndex) {
            btn.className = 'btn btn-sm btn-primary';
        } else {
            const qId = activeQuiz.questions[idx].id;
            if (userAnswers[qId]) {
                btn.className = 'btn btn-sm btn-outline-success';
            } else {
                btn.className = 'btn btn-sm btn-outline-secondary';
            }
        }
    });
}

function updatePillState(qId) {
    const pill = document.getElementById(`pill-q-${qId}`);
    if (pill) {
        pill.classList.add('border-success');
    }
    const total = activeQuiz.questions.length;
    const answeredCount = Object.keys(userAnswers).length;
    document.getElementById('runnerAnsweredCountText').textContent = `${answeredCount} Answered`;
}

/**
 * Submit answers to PHP backend for verification
 */
async function submitQuizForGrading() {
    stopTimer();
    const submitBtn = document.getElementById('submitQuizBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Grading Answers...';

    try {
        const res = await fetch('api/submit-quiz.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                quiz_id: activeQuiz.quiz_id,
                answers: userAnswers,
                time_taken_seconds: timerSeconds
            })
        });

        const data = await res.json();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Submit Quiz';

        if (!res.ok || !data.success) {
            showToast(data.error || 'Failed to submit quiz.', 'error');
            startTimer(); // resume if error
        } else {
            renderQuizResults(data);
        }
    } catch (err) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Submit Quiz';
        showToast('Error communicating with server: ' + err.message, 'error');
        startTimer();
    }
}

/**
 * Render the final graded result page
 */
function renderQuizResults(result) {
    switchToSection('quizResultSection');

    // Score badges & colors
    const scorePct = result.score_percentage;
    document.getElementById('resultPercentageText').textContent = `${scorePct}% Score`;
    document.getElementById('resultFractionText').textContent = `${result.correct_answers} out of ${result.total_questions} Questions Correct (${formatTime(result.time_taken_seconds)})`;
    document.getElementById('resultFeedbackMessage').textContent = result.performance_message;

    const avatarCircle = document.getElementById('resultAvatarCircle');
    if (scorePct >= 75) {
        avatarCircle.style.background = 'rgba(22, 163, 74, 0.15)';
        avatarCircle.style.color = '#16A34A';
        avatarCircle.innerHTML = '<i class="bi bi-award-fill"></i>';
    } else if (scorePct >= 50) {
        avatarCircle.style.background = 'rgba(245, 158, 11, 0.15)';
        avatarCircle.style.color = '#F59E0B';
        avatarCircle.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
    } else {
        avatarCircle.style.background = 'rgba(239, 68, 68, 0.15)';
        avatarCircle.style.color = '#EF4444';
        avatarCircle.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i>';
    }

    // Render detailed review
    const revContainer = document.getElementById('reviewQuestionsContainer');
    revContainer.innerHTML = '';

    result.review.forEach(rev => {
        const card = document.createElement('div');
        card.className = `p-4 border rounded-3 ${rev.is_correct ? 'border-success-subtle bg-success-subtle' : 'border-danger-subtle bg-danger-subtle'}`;

        let optionsHtml = '';
        if (rev.options) {
            optionsHtml = '<div class="d-flex flex-column gap-1 my-2">';
            Object.keys(rev.options).forEach(k => {
                const optText = rev.options[k];
                const isSelected = (k === rev.selected_answer);
                const isTheCorrect = (k === rev.correct_answer);

                let badge = '';
                let style = 'padding: 0.45rem 0.75rem; border-radius: 6px; font-size: 0.88rem;';

                if (isTheCorrect) {
                    style += ' background: #DCFCE7; color: #166534; font-weight: 600; border: 1px solid #86EFAC;';
                    badge = '<span class="badge bg-success ms-2">Correct Answer</span>';
                } else if (isSelected && !rev.is_correct) {
                    style += ' background: #FEE2E2; color: #991B1B; font-weight: 600; border: 1px solid #FCA5A5;';
                    badge = '<span class="badge bg-danger ms-2">Your Answer</span>';
                } else {
                    style += ' background: rgba(0,0,0,0.03); color: inherit;';
                }

                optionsHtml += `
                    <div style="${style}" class="d-flex align-items-center justify-content-between">
                        <div><strong>${k}:</strong> ${escapeHtml(optText)}</div>
                        ${badge}
                    </div>
                `;
            });
            optionsHtml += '</div>';
        }

        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge ${rev.is_correct ? 'bg-success' : 'bg-danger'}">Question ${rev.question_order}</span>
                <span class="small fw-bold ${rev.is_correct ? 'text-success' : 'text-danger'}">
                    ${rev.is_correct ? '<i class="bi bi-check-circle-fill"></i> Correct' : '<i class="bi bi-x-circle-fill"></i> Incorrect'}
                </span>
            </div>
            <h6 class="fw-bold mb-2">${escapeHtml(rev.question)}</h6>
            ${optionsHtml}
            <div class="mt-3 p-3 bg-body-tertiary rounded-2 border small">
                <div class="fw-semibold text-primary mb-1"><i class="bi bi-info-circle-fill me-1"></i> Explanation:</div>
                <div class="text-muted">${formatAiMarkdown(rev.explanation)}</div>
            </div>
        `;

        revContainer.appendChild(card);
    });
}

function startTimer() {
    timerSeconds = 0;
    const timerText = document.getElementById('quizTimerText');
    if (timerInterval) clearInterval(timerInterval);

    timerInterval = setInterval(() => {
        timerSeconds++;
        if (timerText) {
            timerText.textContent = formatTime(timerSeconds);
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
}

function formatTime(totalSec) {
    const mins = Math.floor(totalSec / 60);
    const secs = totalSec % 60;
    return `${mins < 10 ? '0' : ''}${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

function switchToSection(sectionId) {
    ['quizConfigSection', 'quizRunnerSection', 'quizResultSection'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.toggle('d-none', id !== sectionId);
        }
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

