<style>
    #chatbot-container {
    position: absolute; /* hoặc relative trong widget */
    bottom: 0;
    right: 0;
    width: 350px;
    max-width: 90vw;
    height: 500px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
}
</style>

<div class="chatbot-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
    <div id="chatbot-container" style="display: none; width: 350px; max-width: 90vw; height: 500px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; flex-direction: column; overflow: hidden;">
        <div class="chatbot-header" style="background: #02bfed; color: white; padding: 12px 15px; position: relative;">
            <h4 style="margin: 0; font-size: 16px;">Chatbot Tra Cứu Mức Phạt</h4>
            <button id="close-chatbot" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); background: none; border: none; color: white; font-size: 20px; cursor: pointer;">×</button>
        </div>
        <div class="chatbot-messages" style="flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
            <div style="background: #f1f1f1; padding: 10px; border-radius: 10px; align-self: flex-start; max-width: 80%; word-break: break-word;">
                Xin chào! Tôi có thể giúp gì cho bạn về mức phạt giao thông?
            </div>
        </div>
        <div class="chatbot-input" style="padding: 10px; border-top: 1px solid #ddd; display: flex; gap: 8px;">
            <input type="text" id="chatbot-question" placeholder="Nhập câu hỏi về mức phạt..." style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            <button id="send-question" style="padding: 8px 12px; background: #02bfed; color: white; border: none; border-radius: 5px; cursor: pointer;">Gửi</button>
        </div>
    </div>
    <button id="toggle-chatbot" style="background: #02bfed; color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 24px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">💬</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-chatbot');
    const closeBtn = document.getElementById('close-chatbot');
    const chatbotContainer = document.getElementById('chatbot-container');
    const sendBtn = document.getElementById('send-question');
    const questionInput = document.getElementById('chatbot-question');
    const messagesContainer = document.querySelector('.chatbot-messages');

    toggleBtn.addEventListener('click', function() {
        chatbotContainer.style.display = chatbotContainer.style.display === 'none' ? 'flex' : 'none';
    });

    closeBtn.addEventListener('click', function() {
        chatbotContainer.style.display = 'none';
    });

    function addMessage(message, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.style.padding = '10px';
        messageDiv.style.borderRadius = '10px';
        messageDiv.style.maxWidth = '80%';
        messageDiv.style.wordBreak = 'break-word';

        if (isUser) {
            messageDiv.style.background = '#02bfed';
            messageDiv.style.color = 'white';
            messageDiv.style.alignSelf = 'flex-end';
        } else {
            messageDiv.style.background = '#f1f1f1';
            messageDiv.style.color = 'black';
            messageDiv.style.alignSelf = 'flex-start';
        }

        messageDiv.textContent = message;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    sendBtn.addEventListener('click', function() {
        const question = questionInput.value.trim();
        if (question) {
            addMessage(question, true);
            questionInput.value = '';

            const loadingDiv = document.createElement('div');
            loadingDiv.style.padding = '10px';
            loadingDiv.style.borderRadius = '10px';
            loadingDiv.style.background = '#f1f1f1';
            loadingDiv.style.alignSelf = 'flex-start';
            loadingDiv.textContent = 'Đang tìm kiếm thông tin...';
            messagesContainer.appendChild(loadingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            fetch('/api/chatbot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'question=' + encodeURIComponent(question)
            })
            .then(res => res.json())
            .then(data => {
                messagesContainer.removeChild(loadingDiv);
                addMessage(data.answer || 'Xin lỗi, tôi không thể trả lời câu hỏi này.');
            })
            .catch(err => {
                messagesContainer.removeChild(loadingDiv);
                addMessage('Xin lỗi, có lỗi xảy ra. Vui lòng thử lại.');
                console.error(err);
            });
        }
    });

    questionInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendBtn.click();
    });
});
</script>
