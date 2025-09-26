
document.addEventListener("DOMContentLoaded", function () {
    const chatbotIcon = document.getElementById("chatbot-icon");
    const chatContainer = document.getElementById("chat-container");
    const closeButton = document.querySelector(".close-chat");
    const sendButton = document.getElementById("send-button");
    const chatInput = document.getElementById("chat-input");
    const chatHistory = document.getElementById("chat-history");
    const typingIndicator = document.querySelector(".typing-indicator");

    // Toggle chatbot visibility
    chatbotIcon.addEventListener("click", function () {
        chatContainer.classList.remove("hidden");
        chatbotIcon.style.display = "none";
        chatInput.focus();
        // Remove notification badge if present
        chatbotIcon.classList.remove("has-notification");
    });

    closeButton.addEventListener("click", function () {
        chatContainer.classList.add("hidden");
        chatbotIcon.style.display = "flex";
    });

    // Send message functionality
    sendButton.addEventListener("click", sendMessage);
    chatInput.addEventListener("keypress", function (e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Quick action buttons
    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("quick-action-btn")) {
            const message = e.target.getAttribute("data-message");
            chatInput.value = message;
            sendMessage();
        }
    });

    function sendMessage() {
        const userMessage = chatInput.value.trim();
        if (userMessage) {
            appendMessage("user", userMessage);
            chatInput.value = "";
            
            // Show typing indicator
            showTypingIndicator();
            
            // Simulate bot response delay
            setTimeout(() => {
                hideTypingIndicator();
                getBotResponse(userMessage);
            }, 1000 + Math.random() * 2000); // 1-3 seconds delay
        }
    }

    function appendMessage(sender, message) {
        const messageElement = document.createElement("div");
        messageElement.classList.add("message", sender);
        messageElement.textContent = message;
        chatHistory.appendChild(messageElement);
        scrollToBottom();
    }

    function showTypingIndicator() {
        typingIndicator.style.display = "block";
        scrollToBottom();
    }

    function hideTypingIndicator() {
        typingIndicator.style.display = "none";
    }

    function scrollToBottom() {
        const chatBox = document.getElementById("chat-box");
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Enhanced bot response system with Gold Nail specific responses
    function getBotResponse(userMessage) {
        const message = userMessage.toLowerCase();
        let response = "";

        // Gold Nail specific responses
        if (message.includes("price") || message.includes("rate") || message.includes("cost")) {
            response = "Our current gold prices vary based on karat and market rates. 24K gold is currently around ₱3,900 per gram. For the most accurate pricing, please visit our store or use our gold calculator on the website. Would you like to schedule an appointment for evaluation?";
        } else if (message.includes("sell") || message.includes("selling")) {
            response = "We'd be happy to help you sell your gold! Our process is simple: 1) Schedule an appointment, 2) Professional evaluation (2-5 minutes), 3) Receive our fair offer, 4) Get paid instantly. We buy all types of gold jewelry, coins, and scrap gold. Interested in scheduling?";
        } else if (message.includes("buy") || message.includes("buying") || message.includes("purchase")) {
            response = "We offer beautiful gold accessories including necklaces, rings, bracelets, and earrings. All our pieces come with purity certification. You can also purchase gold bars and coins at market rates. What type of gold item interests you?";
        } else if (message.includes("hour") || message.includes("time") || message.includes("open") || message.includes("close")) {
            response = "We're open daily from 7:00 AM to 8:00 PM. We welcome both appointments and walk-ins during business hours. Would you like to schedule an appointment for better service?";
        } else if (message.includes("location") || message.includes("address") || message.includes("where")) {
            response = "You can find us at 4740 La Villa III Unit 104 Solchuaga Street, Brgy Tejeros, Makati City. We're easily accessible and offer secure parking. Need directions or want to schedule a visit?";
        } else if (message.includes("appointment") || message.includes("schedule") || message.includes("book")) {
            response = "I'd be happy to help you schedule an appointment! You can book online through our website or call us at (02) 8362-5478. What service are you interested in - selling gold, buying gold, or gold services?";
        } else if (message.includes("contact") || message.includes("phone") || message.includes("call")) {
            response = "You can reach us at: Landline: (02) 8362-5478, Mobile: +639490561676. We're also available through this chat during business hours. How else can I assist you?";
        } else if (message.includes("karat") || message.includes("purity") || message.includes("quality")) {
            response = "We work with all gold purities: 10K (41.7%), 14K (58.3%), 18K (75%), 21K (87.5%), 22K (91.6%), and 24K (99.9%). Higher karat means higher purity and value. What karat is your gold?";
        } else if (message.includes("safe") || message.includes("secure") || message.includes("trust")) {
            response = "Absolutely! Gold Nail has been trusted since 2010. We use professional equipment for accurate evaluation, offer transparent pricing, and ensure secure transactions. Your safety and satisfaction are our top priorities.";
        } else if (message.includes("hello") || message.includes("hi") || message.includes("hey")) {
            response = "Hello! Welcome to Gold Nail! I'm here to help you with any questions about our gold buying/selling services, current prices, or appointments. What can I assist you with today?";
        } else if (message.includes("thank")) {
            response = "You're very welcome! If you have any other questions about Gold Nail's services, feel free to ask. We're here to help make your gold transaction experience smooth and profitable!";
        } else if (message.includes("bye") || message.includes("goodbye")) {
            response = "Thank you for chatting with Gold Nail! Have a great day, and remember we're here whenever you need assistance with gold services. Feel free to visit us or call anytime!";
        } else {
            // Default responses
            const defaultResponses = [
                "That's a great question! For specific details about our services, I'd recommend speaking with one of our gold experts. You can call us at (02) 8362-5478 or visit our store.",
                "I'd be happy to help you with that! For the most accurate information, please contact our store directly or schedule an appointment. Is there anything else about Gold Nail's services I can help with?",
                "Thanks for your question! Our team at Gold Nail would be better equipped to give you detailed information. Please visit us at Makati City or call (02) 8362-5478.",
                "I want to make sure you get the best information possible. For that specific question, our gold experts can provide more detailed assistance. Would you like our contact information?"
            ];
            response = defaultResponses[Math.floor(Math.random() * defaultResponses.length)];
        }

        appendMessage("bot", response);
    }

    // Auto-show notification after some time (optional)
    setTimeout(() => {
        if (chatContainer.classList.contains("hidden")) {
            chatbotIcon.classList.add("has-notification");
        }
    }, 30000); // Show notification after 30 seconds
});
