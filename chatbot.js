document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('ukm-chatbot-toggle');
    const windowEl = document.getElementById('ukm-chatbot-window');
    const closeBtn = document.getElementById('ukm-chatbot-close');
    const messagesEl = document.getElementById('ukm-chatbot-messages');
    const inputEl = document.getElementById('ukm-chatbot-input');
    const sendBtn = document.getElementById('ukm-chatbot-send');

    let isInitialized = false;

    // Detect context (page and id)
    const urlParams = new URLSearchParams(window.location.search);
    const context = {
        page: window.location.pathname.split('/').pop() || 'index.php',
        id: urlParams.get('id')
    };

    function formatTime() {
        const now = new Date();
        return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function appendMessage(html, sender = 'bot') {
        const bubble = document.createElement('div');
        bubble.className = `ukm-chat-bubble ${sender}`;
        
        const content = document.createElement('div');
        content.innerHTML = html;
        bubble.appendChild(content);

        const time = document.createElement('span');
        time.className = 'ukm-chat-timestamp';
        time.innerText = formatTime();
        bubble.appendChild(time);

        messagesEl.appendChild(bubble);
        scrollToBottom();
        
        saveHistory();
    }

    function appendTyping() {
        const bubble = document.createElement('div');
        bubble.className = `ukm-chat-bubble bot ukm-typing-wrap`;
        bubble.id = 'ukm-typing';
        
        bubble.innerHTML = `
            <div class="ukm-typing-indicator">
                <div class="ukm-typing-dot"></div>
                <div class="ukm-typing-dot"></div>
                <div class="ukm-typing-dot"></div>
            </div>
        `;
        messagesEl.appendChild(bubble);
        scrollToBottom();
    }

    function removeTyping() {
        const typing = document.getElementById('ukm-typing');
        if (typing) typing.remove();
    }

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function renderQuickActions(actions) {
        if (!actions || actions.length === 0) return '';
        let html = '<div class="ukm-chatbot-quick-actions">';
        actions.forEach(action => {
            html += `<button class="ukm-quick-action-btn" onclick="window.ukmChatbotSend('${action}')">${action}</button>`;
        });
        html += '</div>';
        return html;
    }

    window.ukmChatbotSend = function(text) {
        if (!text.trim()) return;
        
        // Remove old quick actions to clean up UI
        const oldActions = messagesEl.querySelectorAll('.ukm-chatbot-quick-actions');
        oldActions.forEach(el => el.remove());

        appendMessage(text, 'user');
        inputEl.value = '';
        
        appendTyping();

        fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'message', message: text, context: context })
        })
        .then(res => res.json())
        .then(data => {
            removeTyping();
            if (data.status === 'success') {
                appendMessage(data.data.html, 'bot');
            } else {
                appendMessage("Sorry, I encountered an error. Please try again.", 'bot');
            }
        })
        .catch(err => {
            console.error(err);
            removeTyping();
            appendMessage("Network error. Could not connect to assistant.", 'bot');
        });
    };

    function initChatbot() {
        if (isInitialized) return;
        isInitialized = true;

        // Restore history if exists in this session
        const history = sessionStorage.getItem('ukm_chatbot_history');
        if (history) {
            messagesEl.innerHTML = history;
            scrollToBottom();
            return;
        }

        appendTyping();

        fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'init', context: context })
        })
        .then(res => res.json())
        .then(data => {
            removeTyping();
            if (data.status === 'success') {
                let html = data.data.greeting;
                html += renderQuickActions(data.data.quickActions);
                appendMessage(html, 'bot');
            }
        })
        .catch(err => {
            console.error(err);
            removeTyping();
            appendMessage("Welcome to UKMInvolve! How can I help you?", 'bot');
        });
    }

    function saveHistory() {
        sessionStorage.setItem('ukm_chatbot_history', messagesEl.innerHTML);
    }

    // Event Listeners
    toggleBtn.addEventListener('click', () => {
        windowEl.classList.toggle('active');
        if (windowEl.classList.contains('active')) {
            initChatbot();
            setTimeout(() => inputEl.focus(), 300);
        }
    });

    closeBtn.addEventListener('click', () => {
        windowEl.classList.remove('active');
    });

    sendBtn.addEventListener('click', () => {
        window.ukmChatbotSend(inputEl.value);
    });

    inputEl.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            window.ukmChatbotSend(inputEl.value);
        }
    });
});
