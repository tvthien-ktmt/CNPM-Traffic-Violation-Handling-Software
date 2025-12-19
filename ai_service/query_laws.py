from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer, util
import json
import re
import torch
import os

# =========================================
# 0. Tạo FastAPI App + CORS
# =========================================
app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],     # Cho phép PHP gọi API
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# =========================================
# 1. Load dữ liệu JSON
# =========================================
json_path = os.path.join(
    os.path.dirname(__file__),
    "embeddings/data/traffic_laws.json"
)

with open(json_path, "r", encoding="utf-8") as f:
    data = json.load(f)

# =========================================
# 2. Load model once
# =========================================
encoder = SentenceTransformer("keepitreal/vietnamese-sbert")

# =========================================
# 3. Chuẩn bị embedding
# =========================================
violations = []
for v in data["tat_ca_vi_pham"]:
    violations.append({
        "text": v["mo_ta"],
        "info": v
    })

violation_embeddings = encoder.encode(
    [v["text"] for v in violations],
    convert_to_tensor=True
)

# =========================================
# 4. Xử lý query
# =========================================
def preprocess_query(q):
    q = q.lower().strip()
    q = re.sub(r"[^\w\s]", "", q)
    q = re.sub(r"\s+", " ", q)
    return q


def detect_vehicle_type(query):
    query = query.lower()

    if "xe máy chuyên dụng" in query:
        return "xe_may_chuyen_dung"

    if ("mô tô" in query) or ("xe máy" in query) or ("xe gắn máy" in query):
        return "xe_moto_xe_may"

    if ("ô tô" in query) or ("oto" in query) or ("xe hơi" in query):
        return "xe_oto"

    if "xe đạp" in query:
        return "xe_dap"

    if "đi bộ" in query:
        return "nguoi_di_bo"

    return None


def find_violation(query):
    query = preprocess_query(query)

    if len(query.split()) < 2:
        return None

    vehicle = detect_vehicle_type(query)

    if vehicle:
        filtered = [
            i for i, v in enumerate(violations)
            if v["info"].get("loai_phuong_tien") == vehicle
        ]
    else:
        filtered = list(range(len(violations)))

    if not filtered:
        return None

    query_emb = encoder.encode(query, convert_to_tensor=True)
    scores = util.cos_sim(query_emb, violation_embeddings[filtered])[0]

    best = scores.argmax().item()
    best_score = scores[best].item()

    # Threshold
    if best_score < 0.45:
        return None

    return violations[filtered[best]]["info"]

VEHICLE_DISPLAY = {
    "xe_moto_xe_may": "Xe mô tô, xe gắn máy",
    "xe_oto": "Ô tô",
    "xe_dap": "Xe đạp",
    "nguoi_di_bo": "Người đi bộ",
    "xe_may_chuyen_dung": "Xe máy chuyên dụng"
}


def format_answer(law):
    vehicle_raw = law["loai_phuong_tien"]
    vehicle = VEHICLE_DISPLAY.get(vehicle_raw, vehicle_raw)

    return (
        f"<b>Loại phương tiện:</b> {vehicle}\n"
        f"<b>Hành vi vi phạm:</b> {law['ten_vi_pham']}\n"
        f"<b>Điều khoản:</b> {law['dieu_khoan']}\n"
        f"<b>Mức phạt:</b> {law['muc_phat']}\n"
        f"<b>Trừ điểm GPLX:</b> {law['tru_diem']}\n"
        f"<b>Mô tả:</b> {law['mo_ta']}"
    )




class Query(BaseModel):
    question: str


@app.post("/chatbot")
async def chatbot_api(q: Query):
    law = find_violation(q.question)

    if not law:
        return {
            "answer":
            "Tôi chưa hiểu câu hỏi của bạn.\n"
            "Hãy mô tả rõ hơn hành vi vi phạm giao thông."
        }

    return {"answer": format_answer(law)}
