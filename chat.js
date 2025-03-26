document.addEventListener("DOMContentLoaded", function () {
    const chatInput = document.getElementById("chat-input");
    const sendButton = document.getElementById("send-btn");
    const chatMessages = document.getElementById("chat-messages");
    const courseName = document.getElementById("course-name");
    const courseId = document.getElementById("course-id");
    const userId = document.getElementById("user-id");

    function appendMessage(content, type) {
        let messageDiv = document.createElement("div");
        messageDiv.classList.add("chat-message", type);
        messageDiv.textContent = content;
        chatMessages.appendChild(messageDiv);

        // if (tokenInfo) {
        //     let tokenDiv = document.createElement("div");
        //     tokenDiv.classList.add("token-info");
        //     tokenDiv.textContent = tokenInfo;
        //     chatMessages.appendChild(tokenDiv);
        // }

        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingIndicator() {
        let typingDiv = document.createElement("div");
        typingDiv.classList.add("chat-message", "typing-indicator");
        typingDiv.innerHTML = "<span></span><span></span><span></span>";
        chatMessages.appendChild(typingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return typingDiv;
    }

    function sendMessage() {
        let userMessage = chatInput.value.trim();
        let userCourseId = courseId.value.trim();
        let userCourseName = courseName.value.trim();
        let courseUserId = userId.value.trim();
        if (userMessage === "") return;

        appendMessage(userMessage, "user-message");
        chatInput.value = "";

        let typingIndicator = showTypingIndicator();

        fetch(M.cfg.wwwroot + "/blocks/custom_openai_chatbot/chat.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ 
                message: userMessage,
                courseId: userCourseId,
                courseName: userCourseName,
                userId: courseUserId
            }),
        })
        .then(response => response.json())
        .then(data => {
            chatMessages.removeChild(typingIndicator);
            if (data.response) {
                // let tokenInfo = `Tokens Used: Req(${data.token_request}) | Resp(${data.token_response})`;
                appendMessage(data.response, "bot-message");
            } else {
                appendMessage(data.error || "Error: Unable to get response", "bot-message");
            }
        })
        .catch(() => {
            chatMessages.removeChild(typingIndicator);
            appendMessage("Error: Unable to connect to AI", "bot-message");
        });
    }

    sendButton.addEventListener("click", sendMessage);
    chatInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") sendMessage();
    });
});
