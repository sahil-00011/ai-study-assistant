/**
 * AI Academic Chatbot Frontend Engine
 * AI Study Assistant
 */

let activeConversationId = typeof INITIAL_CONVERSATION_ID !== 'undefined' ? INITIAL_CONVERSATION_ID : 0;
let isAiResponding = false;

document.addEventListener('DOMContentLoaded', () => {
    const chatInput = document.getElementById('chatInput');
    const chatForm = document.getElementById('chatForm');
    const sendBtn = document.getElementById('sendBtn');
    const newChatBtn = document.getElementById('newChatBtn');
    const charCounter = document.getElementById('charCounter');
    const formatSelect = document.getElementById('answerFormatSelect');

    // Auto-grow textarea & character counter
    if (chatInput) {
        chatInput.addEventListener('input', () => {
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 150) + 'px';
            if (charCounter) {
                charCounter.textContent = `${chatInput.value.length} / 4000`;
            }
        });

        // Submit on Enter without Shift
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    // New Chat Button
    if (newChatBtn) {
        newChatBtn.addEventListener('click', () => {
            startNewChat();
        });
    }

    // Quick Prompt Buttons
    document.querySelectorAll('.quick-prompt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.getAttribute('data-prompt');
            if (chatInput && prompt) {
                chatInput.value = prompt;
                chatInput.focus();
                chatInput.dispatchEvent(new Event('input'));
            }
        });
    });

    // Handle Form Submit
    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (isAiResponding) return;

            const text = chatInput.value.trim();
            if (!text) return;

            const format = formatSelect ? formatSelect.value : 'standard';

            // Remove welcome screen if visible
            const welcome = document.getElementById('chatWelcomeState');
            if (welcome) welcome.remove();

            // Append user bubble
            appendMessage('user', text);
            chatInput.value = '';
            chatInput.style.height = 'auto';
            if (charCounter) charCounter.textContent = '0 / 4000';

            // Show pulsating loading bubble
            const loadingBubbleId = appendLoadingBubble();
            isAiResponding = true;
            if (sendBtn) sendBtn.disabled = true;

            try {
                const res = await fetch('api/chat.php?action=send_message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversation_id: activeConversationId,
                        message: text,
                        format: format
                    })
                });

                const data = await res.json();
                removeLoadingBubble(loadingBubbleId);

                if (!res.ok || !data.success) {
                    const errorMsg = data.error || 'Failed to reach AI Tutor. Please verify API configuration.';
                    appendErrorMessage(errorMsg);
                    showToast(errorMsg, 'error');
                } else {
                    activeConversationId = data.conversation_id;
                    if (data.title) {
                        const titleEl = document.getElementById('currentChatTitle');
                        if (titleEl) titleEl.textContent = data.title;
                        updateConversationsList(data.conversation_id, data.title);
                    }
                    appendMessage('assistant', data.reply);
                }
            } catch (err) {
                removeLoadingBubble(loadingBubbleId);
                appendErrorMessage('Network communication error: ' + err.message);
                showToast('Failed to communicate with server.', 'error');
            } finally {
                isAiResponding = false;
                if (sendBtn) sendBtn.disabled = false;
                if (chatInput) chatInput.focus();
            }
        });
    }

    // Mobile Sidebar Drawer Controls
    const toggleSidebarBtn = document.getElementById('toggleChatHistoryBtn');
    const closeSidebarBtn = document.getElementById('closeChatHistoryBtn');
    const historySidebar = document.getElementById('chatHistorySidebar');
    const historyBackdrop = document.getElementById('chatHistoryBackdrop');

    const closeMobileHistory = () => {
        if (historySidebar) historySidebar.classList.remove('show-sidebar');
        if (historyBackdrop) historyBackdrop.classList.remove('show-backdrop');
    };

    if (toggleSidebarBtn && historySidebar) {
        toggleSidebarBtn.addEventListener('click', () => {
            historySidebar.classList.toggle('show-sidebar');
            if (historyBackdrop) historyBackdrop.classList.toggle('show-backdrop');
        });
    }

    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', closeMobileHistory);
    }
    if (historyBackdrop) {
        historyBackdrop.addEventListener('click', closeMobileHistory);
    }

    // Scroll-to-bottom floating button
    const chatMessagesEl = document.getElementById('chatMessages');
    const scrollBottomBtn = document.getElementById('scrollToBottomBtn');

    if (chatMessagesEl && scrollBottomBtn) {
        chatMessagesEl.addEventListener('scroll', () => {
            const distanceFromBottom = chatMessagesEl.scrollHeight - chatMessagesEl.scrollTop - chatMessagesEl.clientHeight;
            if (distanceFromBottom > 120) {
                scrollBottomBtn.classList.add('visible');
            } else {
                scrollBottomBtn.classList.remove('visible');
            }
        }, { passive: true });

        scrollBottomBtn.addEventListener('click', () => {
            scrollToBottom(true);
        });
    }

    // Attach click events to conversation items
    setupConversationItemEvents();

    // If an initial conversation was loaded, fetch its messages
    if (activeConversationId > 0) {
        loadConversation(activeConversationId);
    }
});

/**
 * Switch to a new empty chat state
 */
function startNewChat() {
    activeConversationId = 0;
    const titleEl = document.getElementById('currentChatTitle');
    if (titleEl) titleEl.textContent = 'Academic AI Tutor';

    document.querySelectorAll('.chat-history-item').forEach(el => el.classList.remove('active'));

    const messagesContainer = document.getElementById('chatMessages');
    if (messagesContainer) {
        messagesContainer.innerHTML = `
            <div id="chatWelcomeState" class="text-center my-auto py-4">
                <div class="avatar-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem; background: var(--primary-light); color: var(--primary-color);">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h5 class="fw-bold mb-2">How can I help your studies today?</h5>
                <p class="text-muted small mx-auto mb-4" style="max-width: 500px;">
                    I am your personalized AI tutor. Ask questions, request programming solutions, or ask for structured exam answers.
                </p>
                <div class="row g-2 justify-content-center mx-auto" style="max-width: 700px;">
                    <div class="col-sm-6">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start p-2 quick-prompt-btn" data-prompt="Explain Normalization in DBMS with 1NF, 2NF, and 3NF examples.">
                            <i class="bi bi-database me-1 text-primary"></i> <strong>DBMS:</strong> Explain 1NF, 2NF, 3NF &rarr;
                        </button>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-outline-secondary btn-sm w-100 text-start p-2 quick-prompt-btn" data-prompt="How does Virtual Memory and Paging work in modern Operating Systems?">
                            <i class="bi bi-cpu me-1 text-primary"></i> <strong>OS:</strong> Virtual Memory & Paging &rarr;
                        </button>
                    </div>
                </div>
            </div>
        `;
        // reattach quick prompt events
        messagesContainer.querySelectorAll('.quick-prompt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const prompt = btn.getAttribute('data-prompt');
                const chatInput = document.getElementById('chatInput');
                if (chatInput && prompt) {
                    chatInput.value = prompt;
                    chatInput.focus();
                    chatInput.dispatchEvent(new Event('input'));
                }
            });
        });
    }

    const chatInput = document.getElementById('chatInput');
    if (chatInput) chatInput.focus();
}

/**
 * Load conversation history from API
 */
async function loadConversation(convId) {
    const welcome = document.getElementById('chatWelcomeState');
    if (welcome) welcome.remove();

    const messagesContainer = document.getElementById('chatMessages');
    messagesContainer.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading conversation history...</div>';

    try {
        const res = await fetch(`api/chat.php?action=get_messages&conversation_id=${convId}`);
        const data = await res.json();

        if (data.success) {
            activeConversationId = convId;
            const titleEl = document.getElementById('currentChatTitle');
            if (titleEl && data.conversation) titleEl.textContent = data.conversation.title;

            // Highlight sidebar item
            document.querySelectorAll('.chat-history-item').forEach(el => {
                el.classList.toggle('active', el.getAttribute('data-id') == convId);
            });

            messagesContainer.innerHTML = '';
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    appendMessage(msg.sender, msg.message, false);
                });
                scrollToBottom();
            } else {
                startNewChat();
            }
        } else {
            showToast(data.error || 'Failed to load conversation', 'error');
            startNewChat();
        }
    } catch (e) {
        showToast('Error loading messages: ' + e.message, 'error');
        startNewChat();
    }
}

/**
 * Setup click events on conversation sidebar items
 */
function setupConversationItemEvents() {
    document.querySelectorAll('.chat-history-item').forEach(item => {
        const id = item.getAttribute('data-id');
        const titleBtn = item.querySelector('.conv-title-btn');
        const delBtn = item.querySelector('.delete-conv-btn');

        if (titleBtn) {
            titleBtn.onclick = () => {
                loadConversation(id);
                const historySidebar = document.getElementById('chatHistorySidebar');
                const historyBackdrop = document.getElementById('chatHistoryBackdrop');
                if (historySidebar) historySidebar.classList.remove('show-sidebar');
                if (historyBackdrop) historyBackdrop.classList.remove('show-backdrop');
            };
        }

        if (delBtn) {
            delBtn.onclick = async (e) => {
                e.stopPropagation();
                if (!confirm('Are you sure you want to delete this conversation?')) return;

                try {
                    const res = await fetch('api/chat.php?action=delete_conversation', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ conversation_id: id })
                    });
                    const d = await res.json();
                    if (d.success) {
                        item.remove();
                        showToast('Conversation deleted.', 'info');
                        if (activeConversationId == id) {
                            startNewChat();
                        }
                    } else {
                        showToast(d.error || 'Failed to delete', 'error');
                    }
                } catch (err) {
                    showToast('Failed to delete: ' + err.message, 'error');
                }
            };
        }
    });
}

/**
 * Update conversations list after new thread created
 */
function updateConversationsList(convId, title) {
    const list = document.getElementById('conversationsList');
    if (!list) return;

    // Remove empty placeholder if any
    const placeholder = list.querySelector('.empty-conv-placeholder');
    if (placeholder) placeholder.remove();

    let existing = list.querySelector(`.chat-history-item[data-id="${convId}"]`);
    if (existing) {
        existing.classList.add('active');
        return;
    }

    const item = document.createElement('div');
    item.className = 'chat-history-item active';
    item.setAttribute('data-id', convId);
    item.innerHTML = `
        <div class="d-flex align-items-center gap-2 text-truncate conv-title-btn" style="cursor: pointer; flex: 1;">
            <i class="bi bi-chat-text text-primary"></i>
            <span class="text-truncate">${escapeHtml(title)}</span>
        </div>
        <button type="button" class="btn btn-link text-muted p-0 delete-conv-btn" data-id="${convId}" title="Delete Conversation" style="font-size: 0.85rem;">
            <i class="bi bi-trash"></i>
        </button>
    `;

    document.querySelectorAll('.chat-history-item').forEach(el => el.classList.remove('active'));
    list.prepend(item);
    setupConversationItemEvents();
}

/**
 * Append message bubble to chat window
 */
function appendMessage(sender, text, shouldScroll = true) {
    const container = document.getElementById('chatMessages');
    if (!container) return;

    const bubble = document.createElement('div');
    bubble.className = `chat-bubble ${sender}`;

    if (sender === 'user') {
        bubble.innerHTML = `<div>${escapeHtml(text).replace(/\n/g, '<br>')}</div>`;
    } else {
        const formatted = formatAiMarkdown(text);
        bubble.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-light-subtle">
                <span class="fw-bold small text-primary d-flex align-items-center gap-1">
                    <i class="bi bi-mortarboard-fill"></i> AI Tutor
                </span>
                <button type="button" class="btn btn-sm btn-link text-muted p-0 copy-msg-btn text-decoration-none" title="Copy response">
                    <i class="bi bi-clipboard"></i> <span class="small" style="font-size:0.75rem;">Copy</span>
                </button>
            </div>
            <div class="ai-content-body">${formatted}</div>
        `;

        const copyBtn = bubble.querySelector('.copy-msg-btn');
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(text).then(() => {
                copyBtn.innerHTML = '<i class="bi bi-check2 text-success"></i> <span class="small text-success" style="font-size:0.75rem;">Copied!</span>';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> <span class="small" style="font-size:0.75rem;">Copy</span>';
                }, 2000);
            });
        });
    }

    container.appendChild(bubble);
    if (shouldScroll) scrollToBottom();
}

/**
 * Append temporary pulsing loading bubble
 */
function appendLoadingBubble() {
    const container = document.getElementById('chatMessages');
    const id = 'loading_' + Date.now();
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble assistant';
    bubble.id = id;
    bubble.innerHTML = `
        <div class="d-flex align-items-center gap-2 text-muted small py-1">
            <span class="spinner-grow spinner-grow-sm text-primary" role="status"></span>
            <span>AI Tutor is thinking and structuring explanation...</span>
        </div>
    `;
    container.appendChild(bubble);
    scrollToBottom();
    return id;
}

function removeLoadingBubble(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function appendErrorMessage(msg) {
    const container = document.getElementById('chatMessages');
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble assistant border border-danger-subtle bg-danger-subtle text-danger';
    bubble.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${escapeHtml(msg)}`;
    container.appendChild(bubble);
    scrollToBottom();
}

function scrollToBottom(smooth = true) {
    const container = document.getElementById('chatMessages');
    if (container) {
        requestAnimationFrame(() => {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: smooth ? 'smooth' : 'auto'
            });
        });
    }
}

/**
 * Basic safe markdown formatter for AI responses
 */
function formatAiMarkdown(text) {
    let raw = escapeHtml(text);

    // Code blocks ```code```
    raw = raw.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, (match, lang, code) => {
        return `<pre><code class="language-${lang}">${code}</code></pre>`;
    });

    // Inline code `code`
    raw = raw.replace(/`([^`]+)`/g, '<code class="bg-body-secondary px-1 py-0 rounded text-danger">$1</code>');

    // Bold **text**
    raw = raw.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

    // Headers ###, ##, #
    raw = raw.replace(/^### (.*$)/gim, '<h6 class="fw-bold mt-3 mb-1 text-primary">$1</h6>');
    raw = raw.replace(/^## (.*$)/gim, '<h5 class="fw-bold mt-3 mb-2">$1</h5>');
    raw = raw.replace(/^# (.*$)/gim, '<h4 class="fw-bold mt-3 mb-2">$1</h4>');

    // Bullet points
    raw = raw.replace(/^\* (.*$)/gim, '<li class="ms-3 mb-1">$1</li>');
    raw = raw.replace(/^- (.*$)/gim, '<li class="ms-3 mb-1">$1</li>');

    // Numbered lists
    raw = raw.replace(/^(\d+)\. (.*$)/gim, '<li class="ms-3 mb-1"><strong>$1.</strong> $2</li>');

    // Line breaks
    raw = raw.replace(/\n\n/g, '<p class="mb-2"></p>');
    raw = raw.replace(/\n/g, '<br>');

    return raw;
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

