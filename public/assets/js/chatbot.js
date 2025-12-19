class TrafficLawChatbot {
  constructor() {
    this.apiUrl = "/api/chatbot"; // PHP endpoint
    this.aiApiUrl = "http://localhost:5000/api/chatbot"; // Flask endpoint trực tiếp (fallback)
    this.isOpen = false;
    this.init();
  }

  init() {
    this.createWidget();
    this.bindEvents();
    this.loadSuggestions();
  }

  createWidget() {
    // Tạo widget HTML (như trong file chatbot_ui.php của bạn)
    const widgetHtml = `
        <div class="chatbot-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
            <div id="chatbot-container" style="display: none; width: 400px; height: 600px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); display: flex; flex-direction: column; overflow: hidden; font-family: 'Arial', sans-serif;">
                <div class="chatbot-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 style="margin: 0; font-size: 18px; font-weight: bold;">🚨 Chatbot Tra Cứu Vi Phạm</h4>
                        <small style="opacity: 0.8;">Hỏi đáp mức phạt giao thông</small>
                    </div>
                    <button id="close-chatbot" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0;">×</button>
                </div>
                
                <div class="chatbot-messages" style="flex: 1; overflow-y: auto; padding: 20px; background: #f8f9fa; display: flex; flex-direction: column; gap: 12px;">
                    <div class="welcome-message" style="background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); align-self: flex-start; max-width: 85%;">
                        <strong>Xin chào! 👋</strong><br>
                        Tôi là chatbot hỗ trợ tra cứu mức phạt vi phạm giao thông theo Nghị định 168/2024.
                    </div>
                    <div id="suggestions-container" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px;"></div>
                </div>
                
                <div class="chatbot-input" style="padding: 15px; border-top: 1px solid #e0e0e0; background: white;">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="chatbot-question" 
                               placeholder="Ví dụ: Xe máy không đội mũ phạt bao nhiêu?" 
                               style="flex: 1; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px; outline: none; transition: border 0.3s;"
                               onfocus="this.style.borderColor='#667eea'"
                               onblur="this.style.borderColor='#e0e0e0'">
                        <button id="send-question" 
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 50%; width: 45px; height: 45px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: transform 0.2s;"
                                onmouseover="this.style.transform='scale(1.1)'"
                                onmouseout="this.style.transform='scale(1)'">➤</button>
                    </div>
                    <div style="margin-top: 10px; text-align: center; color: #666; font-size: 12px;">
                        💡 <i>Hỏi bằng tiếng Việt thông thường</i>
                    </div>
                </div>
            </div>
            <button id="toggle-chatbot" 
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 24px; cursor: pointer; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s; display: flex; align-items: center; justify-content: center;"
                    onmouseover="this.style.transform='scale(1.1)'; this.style.boxShadow='0 6px 20px rgba(102, 126, 234, 0.6)'"
                    onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 15px rgba(102, 126, 234, 0.4)'">💬</button>
        </div>
        `;

    document.body.insertAdjacentHTML("beforeend", widgetHtml);
  }

  bindEvents() {
    const toggleBtn = document.getElementById("toggle-chatbot");
    const closeBtn = document.getElementById("close-chatbot");
    const sendBtn = document.getElementById("send-question");
    const questionInput = document.getElementById("chatbot-question");
    const messagesContainer = document.querySelector(".chatbot-messages");

    toggleBtn.addEventListener("click", () => this.toggleChatbot());
    closeBtn.addEventListener("click", () => this.closeChatbot());

    sendBtn.addEventListener("click", () => this.sendQuestion());
    questionInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") this.sendQuestion();
    });
  }

  toggleChatbot() {
    const container = document.getElementById("chatbot-container");
    this.isOpen = !this.isOpen;
    container.style.display = this.isOpen ? "flex" : "none";

    if (this.isOpen) {
      setTimeout(() => {
        container.scrollIntoView({ behavior: "smooth", block: "end" });
      }, 100);
    }
  }

  closeChatbot() {
    const container = document.getElementById("chatbot-container");
    container.style.display = "none";
    this.isOpen = false;
  }

  addMessage(text, isUser = false, type = "text") {
    const messagesContainer = document.querySelector(".chatbot-messages");
    const messageDiv = document.createElement("div");

    if (type === "violation") {
      // Format thông tin vi phạm
      messageDiv.innerHTML = this.formatViolationMessage(text);
      messageDiv.style.background = "white";
      messageDiv.style.padding = "15px";
      messageDiv.style.borderRadius = "10px";
      messageDiv.style.boxShadow = "0 2px 10px rgba(0,0,0,0.1)";
      messageDiv.style.alignSelf = "flex-start";
      messageDiv.style.maxWidth = "90%";
      messageDiv.style.borderLeft = "4px solid #667eea";
    } else if (isUser) {
      messageDiv.textContent = text;
      messageDiv.style.background =
        "linear-gradient(135deg, #667eea 0%, #764ba2 100%)";
      messageDiv.style.color = "white";
      messageDiv.style.padding = "12px 18px";
      messageDiv.style.borderRadius = "18px 18px 0 18px";
      messageDiv.style.alignSelf = "flex-end";
      messageDiv.style.maxWidth = "75%";
      messageDiv.style.wordBreak = "break-word";
    } else {
      messageDiv.textContent = text;
      messageDiv.style.background = "#f1f3f5";
      messageDiv.style.color = "#212529";
      messageDiv.style.padding = "12px 18px";
      messageDiv.style.borderRadius = "18px 18px 18px 0";
      messageDiv.style.alignSelf = "flex-start";
      messageDiv.style.maxWidth = "75%";
      messageDiv.style.wordBreak = "break-word";
    }

    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  formatViolationMessage(violationData) {
    const main = violationData.answer?.main_violation;

    if (!main) {
      return `<div style="color: #666;">
                <strong>Không tìm thấy thông tin phù hợp</strong><br>
                Hãy thử hỏi cụ thể hơn, ví dụ:<br>
                • Xe máy không đội mũ bảo hiểm<br>
                • Ô tô vượt đèn đỏ<br>
                • Chở quá số người trên xe máy
            </div>`;
    }

    return `
        <div style="font-family: Arial, sans-serif;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <div style="background: #667eea; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">🚗</div>
                <div>
                    <strong style="color: #333; font-size: 16px;">${
                      main.violation_name || "Không rõ"
                    }</strong><br>
                    <small style="color: #666;">${
                      main.vehicle_type || ""
                    }</small>
                </div>
            </div>
            
            <div style="margin-bottom: 10px;">
                <strong style="color: #dc3545; font-size: 18px;">💰 ${
                  main.penalty || "Không rõ"
                }</strong>
            </div>
            
            <div style="background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 10px; font-size: 14px;">
                📝 ${main.description || ""}
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <div style="background: #e8f4fd; padding: 8px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 12px; color: #666;">Điều khoản</div>
                    <div style="font-weight: bold; color: #0056b3;">${
                      main.regulation || "Không rõ"
                    }</div>
                </div>
                <div style="background: #fff3cd; padding: 8px; border-radius: 6px; text-align: center;">
                    <div style="font-size: 12px; color: #666;">Trừ điểm GPLX</div>
                    <div style="font-weight: bold; color: #856404;">${
                      main.point_deduction || "0"
                    } điểm</div>
                </div>
            </div>
            
            ${this.formatRelatedViolations(
              violationData.answer?.related_violations
            )}
            
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ddd; font-size: 12px; color: #888; text-align: center;">
                Độ tin cậy: ${(main.similarity * 100).toFixed(
                  1
                )}% • Nguồn: Nghị định 168/2024
            </div>
        </div>
        `;
  }

  formatRelatedViolations(related) {
    if (!related || related.length === 0) return "";

    let html = `<div style="margin-top: 15px;">
            <div style="font-size: 14px; color: #666; margin-bottom: 8px;">📌 Các vi phạm liên quan:</div>`;

    related.forEach((violation) => {
      html += `
            <div style="background: #f8f9fa; padding: 8px 12px; margin-bottom: 5px; border-radius: 6px; border-left: 3px solid #28a745; font-size: 13px;">
                <div><strong>${violation.name}</strong></div>
                <div style="color: #dc3545; font-size: 12px;">${violation.penalty}</div>
                <div style="color: #666; font-size: 11px;">${violation.regulation}</div>
            </div>
            `;
    });

    html += "</div>";
    return html;
  }

  async sendQuestion() {
    const questionInput = document.getElementById("chatbot-question");
    const question = questionInput.value.trim();

    if (!question) return;

    // Hiển thị câu hỏi của user
    this.addMessage(question, true);
    questionInput.value = "";

    // Hiển thị trạng thái đang xử lý
    const loadingDiv = document.createElement("div");
    loadingDiv.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: white; border-radius: 10px; align-self: flex-start; max-width: 200px;">
            <div class="spinner" style="width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <span style="color: #666;">Đang tìm kiếm...</span>
        </div>
        `;
    document.querySelector(".chatbot-messages").appendChild(loadingDiv);

    try {
      // Gọi API qua PHP controller
      const formData = new FormData();
      formData.append("question", question);

      const response = await fetch("/api/chatbot", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      // Xóa loading
      loadingDiv.remove();

      if (data.status === "success" && data.answer) {
        this.addMessage(data.answer, false, "violation");
      } else {
        this.addMessage(
          "Xin lỗi, tôi không thể tìm thấy thông tin. Vui lòng thử lại với câu hỏi khác.",
          false
        );
      }
    } catch (error) {
      console.error("Chatbot error:", error);
      loadingDiv.remove();
      this.addMessage(
        "⚠ Không thể kết nối đến hệ thống. Vui lòng thử lại sau.",
        false
      );
    }
  }

  async loadSuggestions() {
    try {
      const response = await fetch("/api/chatbot/suggestions");
      const data = await response.json();

      if (data.status === "success" && data.suggestions) {
        this.displaySuggestions(data.suggestions);
      }
    } catch (error) {
      console.error("Failed to load suggestions:", error);
    }
  }

  displaySuggestions(suggestions) {
    const container = document.getElementById("suggestions-container");
    if (!container) return;

    container.innerHTML = "";

    suggestions.slice(0, 4).forEach((suggestion) => {
      const button = document.createElement("button");
      button.textContent = suggestion;
      button.style.cssText = `
                background: white;
                border: 1px solid #ddd;
                border-radius: 20px;
                padding: 8px 15px;
                font-size: 12px;
                cursor: pointer;
                color: #333;
                transition: all 0.3s;
                white-space: nowrap;
            `;

      button.onmouseover = () => {
        button.style.background = "#667eea";
        button.style.color = "white";
        button.style.borderColor = "#667eea";
      };

      button.onmouseout = () => {
        button.style.background = "white";
        button.style.color = "#333";
        button.style.borderColor = "#ddd";
      };

      button.onclick = () => {
        document.getElementById("chatbot-question").value = suggestion;
        this.sendQuestion();
      };

      container.appendChild(button);
    });
  }
}

// Khởi tạo chatbot khi trang tải xong
document.addEventListener("DOMContentLoaded", () => {
  window.chatbot = new TrafficLawChatbot();
});

// Thêm CSS animation
const style = document.createElement("style");
style.textContent = `
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
`;
document.head.appendChild(style);
