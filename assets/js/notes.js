/**
 * AI Notes Generator & Library Frontend Logic
 * AI Study Assistant
 */

let currentActiveNote = null;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('generateNotesForm');
    const searchInput = document.getElementById('searchNotesInput');

    // Note Generation Submit
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const subject = document.getElementById('noteSubject').value.trim();
            const topic = document.getElementById('noteTopic').value.trim();
            const academicLevel = document.getElementById('noteLevel').value;
            const format = document.getElementById('noteFormat').value;
            const language = document.getElementById('noteLanguage').value;

            if (!subject || !topic) {
                showToast('Please provide both subject and topic.', 'warning');
                return;
            }

            // UI state
            setNotesLoadingState(true);

            try {
                const res = await fetch('api/generate-notes.php?action=generate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject,
                        topic,
                        academic_level: academicLevel,
                        format,
                        language
                    })
                });

                const data = await res.json();
                setNotesLoadingState(false);

                if (!res.ok || !data.success) {
                    showToast(data.error || 'Failed to generate notes. Please check API settings.', 'error');
                } else {
                    currentActiveNote = data.note;
                    displayNote(data.note);
                    showToast('Notes generated and saved to your library!', 'success');
                    prependNoteToLibrary(data.note);
                }
            } catch (err) {
                setNotesLoadingState(false);
                showToast('Network error: ' + err.message, 'error');
            }
        });
    }

    // Copy Button
    const copyBtn = document.getElementById('copyNoteBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            if (!currentActiveNote || !currentActiveNote.content) return;
            navigator.clipboard.writeText(currentActiveNote.content).then(() => {
                copyBtn.innerHTML = '<i class="bi bi-check2 text-success"></i> <span>Copied!</span>';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> <span>Copy</span>';
                }, 2000);
            });
        });
    }

    // Download Button
    const downloadBtn = document.getElementById('downloadNoteBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => {
            if (!currentActiveNote || !currentActiveNote.content) return;
            const sanitizedTopic = (currentActiveNote.topic || 'notes').replace(/[^a-zA-Z0-9_-]/g, '_');
            const filename = `${sanitizedTopic}_notes.md`;
            const blob = new Blob([currentActiveNote.content], { type: 'text/markdown;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Notes downloaded successfully.', 'info');
        });
    }

    // Print Button
    const printBtn = document.getElementById('printNoteBtn');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            if (!currentActiveNote) return;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${escapeHtml(currentActiveNote.topic)} - Notes</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 30px; color: #1e293b; max-width: 800px; margin: 0 auto; }
                        h1 { color: #4f46e5; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
                        h2, h3, h4 { color: #1e293b; margin-top: 20px; }
                        pre { background: #f1f5f9; padding: 12px; border-radius: 6px; overflow-x: auto; }
                        code { font-family: monospace; }
                        .meta { color: #64748b; font-size: 0.9em; margin-bottom: 25px; }
                    </style>
                </head>
                <body>
                    <h1>${escapeHtml(currentActiveNote.topic)}</h1>
                    <div class="meta">
                        <strong>Subject:</strong> ${escapeHtml(currentActiveNote.subject)} | 
                        <strong>Format:</strong> ${escapeHtml(currentActiveNote.format)} | 
                        <strong>Generated:</strong> ${currentActiveNote.created_at || 'Recently'}
                    </div>
                    <div>${formatAiMarkdown(currentActiveNote.content)}</div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
            }, 300);
        });
    }

    // Search Library
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.note-card-item');

            cards.forEach(card => {
                const topic = card.getAttribute('data-topic') || '';
                const subject = card.getAttribute('data-subject') || '';
                if (topic.includes(query) || subject.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Attach library action handlers
    attachLibraryActionEvents();
});

function setNotesLoadingState(isLoading) {
    const placeholder = document.getElementById('notesPlaceholder');
    const loading = document.getElementById('notesLoading');
    const display = document.getElementById('notesDisplay');
    const generateBtn = document.getElementById('generateNotesBtn');

    if (isLoading) {
        if (placeholder) placeholder.classList.add('d-none');
        if (display) display.classList.add('d-none');
        if (loading) loading.classList.remove('d-none');
        if (generateBtn) {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
        }
    } else {
        if (loading) loading.classList.add('d-none');
        if (generateBtn) {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="bi bi-sparkles"></i> <span>Generate Notes with AI</span>';
        }
    }
}

function displayNote(note) {
    const display = document.getElementById('notesDisplay');
    const placeholder = document.getElementById('notesPlaceholder');
    if (placeholder) placeholder.classList.add('d-none');
    if (display) display.classList.remove('d-none');

    document.getElementById('displaySubject').textContent = note.subject;
    document.getElementById('displayFormat').textContent = note.format;
    document.getElementById('displayDate').textContent = note.created_at || 'Just now';
    document.getElementById('displayTopic').textContent = note.topic;

    const bodyEl = document.getElementById('displayContentBody');
    bodyEl.innerHTML = formatAiMarkdown(note.content);
}

function attachLibraryActionEvents() {
    // View button
    document.querySelectorAll('.view-note-btn').forEach(btn => {
        btn.onclick = async () => {
            const noteId = btn.getAttribute('data-id');
            try {
                const res = await fetch(`api/generate-notes.php?action=get&id=${noteId}`);
                const data = await res.json();
                if (data.success && data.note) {
                    currentActiveNote = data.note;
                    displayNote(data.note);

                    // Switch to generator tab
                    const tabBtn = document.getElementById('tab-generator-btn');
                    if (tabBtn) {
                        const tab = new bootstrap.Tab(tabBtn);
                        tab.show();
                    }
                    showToast('Note loaded into viewer.', 'info');
                } else {
                    showToast(data.error || 'Failed to load note', 'error');
                }
            } catch (err) {
                showToast('Error loading note: ' + err.message, 'error');
            }
        };
    });

    // Delete button
    document.querySelectorAll('.delete-note-btn').forEach(btn => {
        btn.onclick = async () => {
            const noteId = btn.getAttribute('data-id');
            if (!confirm('Are you sure you want to delete this saved note?')) return;

            try {
                const res = await fetch('api/generate-notes.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: noteId })
                });
                const d = await res.json();
                if (d.success) {
                    const card = btn.closest('.note-card-item');
                    if (card) card.remove();
                    showToast('Note deleted.', 'info');

                    // Update count
                    const countEl = document.getElementById('savedNotesCount');
                    if (countEl) {
                        const current = parseInt(countEl.textContent, 10) || 1;
                        countEl.textContent = Math.max(0, current - 1);
                    }
                } else {
                    showToast(d.error || 'Failed to delete note', 'error');
                }
            } catch (err) {
                showToast('Failed to delete: ' + err.message, 'error');
            }
        };
    });
}

function prependNoteToLibrary(note) {
    const grid = document.getElementById('savedNotesGrid');
    if (!grid) return;

    const placeholder = grid.querySelector('.empty-lib-placeholder');
    if (placeholder) placeholder.remove();

    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4 note-card-item';
    col.setAttribute('data-id', note.id);
    col.setAttribute('data-topic', (note.topic || '').toLowerCase());
    col.setAttribute('data-subject', (note.subject || '').toLowerCase());

    const excerpt = note.content.length > 160 ? note.content.substring(0, 160) + '...' : note.content;

    col.innerHTML = `
        <div class="card h-100 p-3 card-hover border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="badge badge-primary-soft small">${escapeHtml(note.subject)}</span>
                <span class="text-muted" style="font-size: 0.75rem;">${note.created_at || 'Just now'}</span>
            </div>
            <h6 class="fw-bold mb-2 text-truncate" title="${escapeHtml(note.topic)}">
                ${escapeHtml(note.topic)}
            </h6>
            <p class="small text-muted mb-3 flex-grow-1" style="font-size: 0.82rem; line-height: 1.5;">
                ${escapeHtml(excerpt)}
            </p>
            <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-auto">
                <button type="button" class="btn btn-sm btn-outline-primary view-note-btn" data-id="${note.id}">
                    <i class="bi bi-eye"></i> View Note
                </button>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-note-btn" data-id="${note.id}" title="Delete Note">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;

    grid.prepend(col);

    // Update count
    const countEl = document.getElementById('savedNotesCount');
    if (countEl) {
        const current = parseInt(countEl.textContent, 10) || 0;
        countEl.textContent = current + 1;
    }

    attachLibraryActionEvents();
}

