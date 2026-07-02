<!-- UKMInvolve Smart Assistant UI -->
<style>
<?php include __DIR__ . '/chatbot.css'; ?>
</style>

<div id="ukm-chatbot-widget">
    <button id="ukm-chatbot-toggle" aria-label="Open Smart Assistant">
    <i class="fas fa-comment-dots"></i>
</button>

<div id="ukm-chatbot-window">
    <div class="ukm-chatbot-header">
        <div class="ukm-chatbot-header-info">
            <div class="ukm-chatbot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <h3 class="ukm-chatbot-title">Smart Assistant</h3>
                <p class="ukm-chatbot-subtitle">Always here to help</p>
            </div>
        </div>
        <button id="ukm-chatbot-close" class="ukm-chatbot-close" aria-label="Close Smart Assistant">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="ukm-chatbot-messages" id="ukm-chatbot-messages">
        <!-- Messages will be injected here via JS -->
    </div>
    
    <div class="ukm-chatbot-input-area">
        <input type="text" id="ukm-chatbot-input" class="ukm-chatbot-input" placeholder="Type a message..." autocomplete="off">
        <button id="ukm-chatbot-send" class="ukm-chatbot-send" aria-label="Send Message">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>
</div> <!-- End ukm-chatbot-widget -->

<script src="chatbot.js?v=2"></script>
