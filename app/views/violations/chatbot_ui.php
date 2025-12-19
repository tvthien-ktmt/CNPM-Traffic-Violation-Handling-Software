<style>
#chatbot-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 360px;
    height: 520px;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.25);
    display: none;
    flex-direction: column;
    background: white;
    z-index: 9999;
    overflow: hidden;
    animation: slideUp 0.25s ease;
}

/* Animation mở chatbot */
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}

.chatbot-header {
    background: linear-gradient(135deg, #02bfed, #007bff);
    color: white;
    padding: 14px 16px;
    font-weight: bold;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f7f9fb;
}

/* Bong bóng tin nhắn BOT */
.bot-msg {
    background: #ffffff;
    padding: 12px;
    border-radius: 12px;
    max-width: 80%;
    align-self: flex-start;
    border: 1px solid #e4e6eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.07);
}

/* Bong bóng tin nhắn USER */
.user-msg {
    background: #02bfed;
    color: white;
    padding: 12px;
    border-radius: 12px;
    max-width: 80%;
    align-self: flex-end;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.chatbot-input {
    padding: 12px;
    border-top: 1px solid #ddd;
    background: #fff;
    display: flex;
    gap: 8px;
}

.chatbot-input input {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

/* Nút gửi */
.chatbot-input button {
    padding: 10px 14px;
    background: #02bfed;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
}

.chatbot-input button:hover {
    background: #009dc9;
}

/* Nút mở chatbot */
#toggle-chatbot {
    transition: 0.2s ease;
}

#toggle-chatbot:hover {
    transform: scale(1.08);
    box-shadow: 0 3px 12px rgba(0,0,0,0.4);
}

</style>

<div class="chatbot-widget">
    <div id="chatbot-container">
        <div class="chatbot-header" style="background:#02bfed;color:white;padding:12px;position:relative;">
            <h4 style="margin:0;">Chatbot Tra Cứu Mức Phạt</h4>
            <button id="close-chatbot"
                style="position:absolute;top:10px;right:12px;background:none;border:none;color:white;font-size:20px;cursor:pointer;">×</button>
        </div>

        <div class="chatbot-messages"
            style="flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;">
            <div style="background:#f1f1f1;padding:10px;border-radius:10px;align-self:flex-start;max-width:80%;">
                Xin chào! Tôi có thể giúp gì cho bạn về mức phạt giao thông?
            </div>
        </div>

        <div class="chatbot-input" style="padding:10px;display:flex;gap:8px;border-top:1px solid #ddd;">
            <input type="text" id="chatbot-question"
                placeholder="Nhập câu hỏi..."
                style="flex:1;padding:8px;border:1px solid #ddd;border-radius:5px;">
            <button id="send-question"
                style="padding:8px 12px;background:#02bfed;color:white;border:none;border-radius:5px;cursor:pointer;">
                Gửi
            </button>
        </div>
    </div>

    <button id="toggle-chatbot"
        style="position:fixed;bottom:20px;right:20px;background:#02bfed;color:white;border:none;border-radius:50%;width:60px;height:60px;font-size:24px;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,0.2);">
        💬
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('chatbot-container');
    const toggle = document.getElementById('toggle-chatbot');
    const closeBtn = document.getElementById('close-chatbot');
    const messages = document.querySelector('.chatbot-messages');
    const sendBtn = document.getElementById('send-question');
    const input = document.getElementById('chatbot-question');

    toggle.onclick = () => box.style.display = 'flex';
    closeBtn.onclick = () => box.style.display = 'none';

    function addMsg(text, user = false) {
    const div = document.createElement('div');
    div.className = user ? "user-msg" : "bot-msg";
    div.style.padding = "10px";
    div.style.borderRadius = "10px";
    div.style.maxWidth = "80%";
    div.style.wordBreak = "break-word";

    if (user) {
        div.style.background = "#02bfed";
        div.style.color = "white";
        div.style.alignSelf = "flex-end";
    } else {
        div.style.background = "#f1f1f1";
        div.style.color = "black";
        div.style.alignSelf = "flex-start";
    }

    // ✅ hiển thị HTML + xuống dòng
    div.innerHTML = text.replace(/\n/g, "<br>");

    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}


    sendBtn.onclick = () => {
        const q = input.value.trim();
        if (!q) return;

        addMsg(q, true);
        input.value = "";

        const loading = document.createElement('div');
        loading.textContent = "Đang tìm kiếm...";
        loading.style.background = "#f1f1f1";
        loading.style.padding = "10px";
        loading.style.borderRadius = "10px";
        messages.appendChild(loading);

        fetch("http://localhost:8000/chatbot", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ question: q })
        })
        .then(res => res.json())
        .then(data => {
            messages.removeChild(loading);
            addMsg(data.answer || "Không thể trả lời câu này.");
        })
        .catch(err => {
            messages.removeChild(loading);
            addMsg("Lỗi server. Thử lại.", false);
        });
    };

    input.addEventListener("keypress", e => {
        if (e.key === "Enter") sendBtn.click();
    });
});
</script>
