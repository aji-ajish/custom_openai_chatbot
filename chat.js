document.addEventListener("DOMContentLoaded", function () {
    const chatInput = document.getElementById("chat-input");
    const sendButton = document.getElementById("send-btn");
    const chatMessages = document.getElementById("chat-messages");
    const courseName = document.getElementById("course-name");

    function appendMessage(content, type) {
        let messageDiv = document.createElement("div");
        messageDiv.classList.add("chat-message", type);
        messageDiv.textContent = content;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll
    }

    function sendMessage() {
        let userMessage = chatInput.value.trim();
        let userCourseName = courseName.value.trim();
        if (userMessage === "") return;

        appendMessage(userMessage, "user-message");
        chatInput.value = "";

        fetch(M.cfg.wwwroot + "/blocks/custom_openai_chatbot/chat.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ 
                message: userMessage,
                courseName: userCourseName 
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.response) {
                    appendMessage(data.response, "bot-message");
                } else {
                    appendMessage(data.error || "Error: Unable to get response", "bot-message");
                }
            })
            .catch(() => {
                appendMessage("Error: Unable to connect to AI", "bot-message");
            });
    }

    sendButton.addEventListener("click", sendMessage);
    chatInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") sendMessage();
    });
});
