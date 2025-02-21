document.addEventListener("DOMContentLoaded", function() {
    let chatContainer = document.getElementById("openai-chatbot-container");
    if (!chatContainer) return;

    document.getElementById("send-btn").addEventListener("click", async function() {
        let userInput = document.getElementById("chat-input").value;
        let outputDiv = document.getElementById("chat-output");

        if (!userInput.trim()) {
            outputDiv.innerHTML += "<p><strong>Error:</strong> Please enter a message.</p>";
            return;
        }

        outputDiv.innerHTML += `<p><strong>You:</strong> ${userInput}</p>`;

        let response = await fetch(M.cfg.wwwroot + "/blocks/cusrom_openai_chatbot/chat.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: userInput })
        });

        let result;
        try {
            result = await response.json();
        } catch (e) {
            outputDiv.innerHTML += `<p><strong>Error:</strong> Invalid response from server.</p>`;
            return;
        }

        if (result.error) {
            outputDiv.innerHTML += `<p><strong>Error:</strong> ${result.error}</p>`;
        } else {
            outputDiv.innerHTML += `<p><strong>AI:</strong> ${result.response}</p>`;
        }

        document.getElementById("chat-input").value = "";
    });
});
