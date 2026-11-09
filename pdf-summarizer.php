<?php
$pageTitle = 'PDF Summarizer';
$currentPage = 'pdf-summarizer';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Load user's previously uploaded documents
$previousDocs = [];
try {
    $db = getDbConnection();
    $stmt = $db->prepare("SELECT d.id, d.original_filename, d.file_size, d.created_at, s.id as summary_id 
                          FROM pdf_documents d 
                          LEFT JOIN pdf_summaries s ON d.id = s.document_id 
                          WHERE d.user_id = ? 
                          ORDER BY d.created_at DESC LIMIT 15");
    $stmt->execute([$userId]);
    $previousDocs = $stmt->fetchAll();
} catch (Exception $e) {}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-content py-3">
    <!-- Header -->
    <div class="mb-4">
        <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>Smart PDF Summarizer</h4>
        <p class="text-muted small mb-0">Upload syllabus slides, textbooks, or research papers. The AI extracts core principles, key definitions, and generates test questions.</p>
    </div>

    <div class="row g-4">
        <!-- Left: Upload & Document Information -->
        <div class="col-lg-5">
            <!-- 1. Upload Card -->
            <div class="card p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Upload Study PDF</h5>
                
                <form id="pdfUploadForm">
                    <div class="mb-3">
                        <label for="pdfFileInput" class="form-label small fw-semibold">Select PDF File (Max 10MB) <span class="text-danger">*</span></label>
                        <input class="form-control" type="file" id="pdfFileInput" accept=".pdf,application/pdf" required>
                    </div>

                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Uploaded files are parsed securely on the server. Text content is summarized via OpenAI API.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="uploadPdfBtn">
                        <i class="bi bi-upload"></i>
                        <span>Upload & Extract Text</span>
                    </button>
                </form>
            </div>

            <!-- 2. Active Uploaded Document Status -->
            <div class="card p-4 shadow-sm mb-4 d-none" id="activeDocCard">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge badge-success-soft"><i class="bi bi-check-circle-fill"></i> Extracted</span>
                    <span class="small text-muted" id="docFileSizeText">0 KB</span>
                </div>
                <h6 class="fw-bold mb-2 text-truncate" id="docFileNameText">document.pdf</h6>
                
                <div class="p-2 bg-body-tertiary rounded-2 border small mb-3" style="max-height: 100px; overflow-y: auto;">
                    <div class="text-muted" style="font-size:0.75rem;" id="docPreviewText">Text excerpt will appear here...</div>
                </div>

                <button type="button" class="btn btn-success w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="summarizeNowBtn">
                    <i class="bi bi-magic"></i>
                    <span>Generate AI Study Summary</span>
                </button>
            </div>

            <!-- 3. Previous Uploaded PDFs List -->
            <div class="card p-4 shadow-sm">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>Recent Documents</h6>
                <?php if (empty($previousDocs)): ?>
                    <p class="small text-muted mb-0">No documents uploaded yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush" id="recentDocsList">
                        <?php foreach ($previousDocs as $doc): ?>
                            <div class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
                                <div class="text-truncate me-2">
                                    <div class="small fw-semibold text-truncate"><?php echo htmlspecialchars($doc['original_filename']); ?></div>
                                    <span class="text-muted" style="font-size:0.72rem;"><?php echo round($doc['file_size'] / 1024, 1); ?> KB &bull; <?php echo formatDate($doc['created_at'], 'M d'); ?></span>
                                </div>
                                <?php if (!empty($doc['summary_id'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary load-prev-summary-btn" data-doc-id="<?php echo $doc['id']; ?>">
                                        <i class="bi bi-eye"></i> Summary
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary re-summarize-btn" data-doc-id="<?php echo $doc['id']; ?>">
                                        Summarize
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: AI Summary Display -->
        <div class="col-lg-7">
            <div class="card p-4 shadow-sm h-100" id="summaryContainerCard">
                <!-- Empty Placeholder -->
                <div id="summaryPlaceholder" class="text-center my-auto py-5 text-muted">
                    <div class="avatar-circle mx-auto mb-3" style="width: 72px; height: 72px; font-size: 2.2rem; background: rgba(239, 68, 68, 0.12); color: #EF4444;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h5 class="fw-bold mb-2">No Document Summary Active</h5>
                    <p class="small text-muted mx-auto mb-0" style="max-width: 440px;">
                        Upload a PDF on the left and click <strong>Generate AI Study Summary</strong> to receive an executive overview, revision points, and practice questions.
                    </p>
                </div>

                <!-- Loading State -->
                <div id="summaryLoading" class="text-center my-auto py-5 d-none">
                    <div class="spinner-border text-danger mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h5 class="fw-bold mb-1">Analyzing Document with AI...</h5>
                    <p class="small text-muted mb-0">Extracting core principles, high-yield revision bullet points, and practice exam questions.</p>
                </div>

                <!-- Summary Content View -->
                <div id="summaryContentView" class="d-none">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
                        <div>
                            <span class="badge badge-danger-soft mb-1">AI Document Summary</span>
                            <h5 class="fw-bold mb-0 text-primary" id="summaryDocTitle">Document Name</h5>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="copySummaryBtn">
                                <i class="bi bi-clipboard"></i> <span>Copy</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" id="printSummaryBtn">
                                <i class="bi bi-printer"></i> <span>Print</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-3 bg-body-tertiary rounded-3 border" style="max-height: 580px; overflow-y: auto;" id="summaryMarkdownBody">
                        <!-- Rendered Summary Body -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = "
<script>
let currentUploadedDocId = 0;
let currentSummaryContent = '';

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('pdfUploadForm');
    const uploadBtn = document.getElementById('uploadPdfBtn');
    const summarizeNowBtn = document.getElementById('summarizeNowBtn');
    const copyBtn = document.getElementById('copySummaryBtn');
    const printBtn = document.getElementById('printSummaryBtn');

    // Handle Upload
    if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('pdfFileInput');
            if (!fileInput.files || fileInput.files.length === 0) {
                showToast('Please select a PDF file.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('pdf', fileInput.files[0]);

            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-1\"></span> Uploading & Extracting...';

            try {
                const res = await fetch('api/upload-pdf.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class=\"bi bi-upload\"></i> <span>Upload & Extract Text</span>';

                if (data.success) {
                    currentUploadedDocId = data.document_id;
                    showToast('PDF uploaded and text extracted!', 'success');

                    // Display active doc box
                    document.getElementById('activeDocCard').classList.remove('d-none');
                    document.getElementById('docFileNameText').textContent = data.original_filename;
                    document.getElementById('docFileSizeText').textContent = Math.round(data.file_size / 1024) + ' KB (' + data.char_count + ' chars)';
                    document.getElementById('docPreviewText').textContent = data.preview_text;
                } else {
                    showToast(data.error || 'Upload failed.', 'error');
                }
            } catch (err) {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class=\"bi bi-upload\"></i> <span>Upload & Extract Text</span>';
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }

    // Handle Summarize Action
    if (summarizeNowBtn) {
        summarizeNowBtn.addEventListener('click', async () => {
            if (!currentUploadedDocId) {
                showToast('No active document selected.', 'warning');
                return;
            }

            setSummaryLoading(true);

            try {
                const res = await fetch('api/summarize-pdf.php?action=summarize', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ document_id: currentUploadedDocId })
                });

                const data = await res.json();
                setSummaryLoading(false);

                if (data.success) {
                    currentSummaryContent = data.summary_content;
                    displaySummary(data.original_filename, data.summary_content);
                    showToast('Document summarized successfully!', 'success');
                } else {
                    showToast(data.error || 'Summarization failed.', 'error');
                }
            } catch (err) {
                setSummaryLoading(false);
                showToast('Error: ' + err.message, 'error');
            }
        });
    }

    // Load Previous Summaries
    document.querySelectorAll('.load-prev-summary-btn').forEach(btn => {
        btn.onclick = async () => {
            const docId = btn.getAttribute('data-doc-id');
            setSummaryLoading(true);

            try {
                const res = await fetch(`api/summarize-pdf.php?action=get_summary&document_id=\${docId}`);
                const data = await res.json();
                setSummaryLoading(false);

                if (data.success && data.summary) {
                    currentSummaryContent = data.summary.summary_content;
                    displaySummary(data.summary.original_filename, data.summary.summary_content);
                } else {
                    showToast(data.error || 'Failed to load summary', 'error');
                }
            } catch (err) {
                setSummaryLoading(false);
                showToast('Network error: ' + err.message, 'error');
            }
        };
    });

    // Re-summarize existing document
    document.querySelectorAll('.re-summarize-btn').forEach(btn => {
        btn.onclick = () => {
            currentUploadedDocId = parseInt(btn.getAttribute('data-doc-id'), 10);
            summarizeNowBtn.click();
        };
    });

    // Copy Summary
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            if (!currentSummaryContent) return;
            navigator.clipboard.writeText(currentSummaryContent).then(() => {
                copyBtn.innerHTML = '<i class=\"bi bi-check2 text-success\"></i> <span>Copied!</span>';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class=\"bi bi-clipboard\"></i> <span>Copy</span>';
                }, 2000);
            });
        });
    }

    // Print Summary
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            if (!currentSummaryContent) return;
            const title = document.getElementById('summaryDocTitle').textContent;
            const w = window.open('', '_blank');
            w.document.write(`
                <!DOCTYPE html>
                <html><head><title>\${title} - Summary</title>
                <style>body{font-family:Arial,sans-serif;line-height:1.6;padding:30px;color:#1e293b;max-width:800px;margin:0 auto;}
                h1{color:#ef4444;border-bottom:2px solid #e2e8f0;padding-bottom:10px;} h2,h3{margin-top:20px;color:#1e293b;}</style></head>
                <body><h1>\${title}</h1><div>\${formatAiMarkdown(currentSummaryContent)}</div></body></html>
            `);
            w.document.close();
            w.focus();
            setTimeout(() => w.print(), 300);
        });
    }
});

function setSummaryLoading(isLoading) {
    const placeholder = document.getElementById('summaryPlaceholder');
    const loading = document.getElementById('summaryLoading');
    const view = document.getElementById('summaryContentView');

    if (isLoading) {
        if (placeholder) placeholder.classList.add('d-none');
        if (view) view.classList.add('d-none');
        if (loading) loading.classList.remove('d-none');
    } else {
        if (loading) loading.classList.add('d-none');
    }
}

function displaySummary(title, content) {
    const placeholder = document.getElementById('summaryPlaceholder');
    const view = document.getElementById('summaryContentView');

    if (placeholder) placeholder.classList.add('d-none');
    if (view) view.classList.remove('d-none');

    document.getElementById('summaryDocTitle').textContent = title;
    document.getElementById('summaryMarkdownBody').innerHTML = formatAiMarkdown(content);
}
</script>
";

require_once __DIR__ . '/includes/footer.php';
?>

