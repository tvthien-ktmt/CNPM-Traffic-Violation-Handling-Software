import cv2
import numpy as np
import torch
from ultralytics import YOLO
from collections import defaultdict, deque
import time
import os
from datetime import datetime

# ===========================
# KIỂM TRA GPU/CPU
# ===========================
print(f"PyTorch version: {torch.__version__}")
print(f"CUDA available: {torch.cuda.is_available()}")

if torch.cuda.is_available():
    device = 'cuda'
    gpu_name = torch.cuda.get_device_name(0)
    print(f"✅ GPU detected: {gpu_name}")
else:
    device = 'cpu'
    print("⚠️ CUDA không khả dụng - đang sử dụng CPU")

print(f"🚀 Sử dụng thiết bị: {device.upper()}")
print("=" * 80)

# ===========================
# LOAD MODEL VỚI GPU SUPPORT
# ===========================
model = YOLO(
    r"C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\train_vehicle\runs\detect\train2\weights\best.pt"
)

# Chuyển model sang GPU nếu có
model.to(device)

video_path = r"C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\sailan\video\DienBienPhu2.mp4"
cap = cv2.VideoCapture(video_path)

# ===========================
# TẠO THƯ MỤC LƯU ẢNH VI PHẠM
# ===========================
base_violation_dir = "lane_violations"
if not os.path.exists(base_violation_dir):
    os.makedirs(base_violation_dir)

# Tạo các thư mục con
VIOLATION_FOLDERS = {
    'crop': os.path.join(base_violation_dir, 'crop'),  # Ảnh nhỏ: chỉ xe vi phạm (KHÔNG CÓ ZONE/TEXT)
    'full': os.path.join(base_violation_dir, 'full'),  # Ảnh lớn: toàn cảnh chỉ highlight xe vi phạm
}

for folder_name, folder_path in VIOLATION_FOLDERS.items():
    if not os.path.exists(folder_path):
        os.makedirs(folder_path)
        print(f"📁 Đã tạo thư mục: {folder_path}")

print(f"📸 Ảnh vi phạm sẽ được lưu vào: {base_violation_dir}")

# ===========================
# KHAI BÁO LANES
# ===========================
lane3 = np.array([
    [644, 37],
    [99, 782],
    [558, 871],
    [797, 64]
], dtype=np.int32)

lane2 = np.array([
    [798, 67],
    [562, 868],
    [1069, 889],
    [954, 78]
], dtype=np.int32)

lane1 = np.array([
    [956, 78],
    [1073, 888],
    [1474, 856],
    [1096, 98]
], dtype=np.int32)

lanes = [lane1, lane2, lane3]
lane_colors = [(0, 165, 255), (100, 200, 255), (0, 255, 0)]
lane_names = ["LAN 1: XE MAY", "LAN 2: HON HOP", "LAN 3: O TO"]

# ===========================
# TRACKING DATA
# ===========================
vehicle_tracks = defaultdict(lambda: {
    'lane_history': deque(maxlen=30),
    'violation_start_time': None,
    'type': None,
    'violation_logged': False,
    'violation_type': None,
    'current_violation_duration': 0,
    'violation_saved': False,
    'last_violation_frame': None,
    'save_count': 0
})

violations = []
frame_count = 0

# Lấy FPS của video để tính thời gian
fps = cap.get(cv2.CAP_PROP_FPS)
if fps == 0:
    fps = 30

print(f"📹 Video FPS: {fps}")
print(f"⚙️ QUY TẮC VI PHẠM:")
print(f"   - Xe máy (motorcycle) vào Lane 3 (O TO) > 2s = VI PHẠM")
print(f"   - Ô tô (car) vào Lane 1 (XE MAY) > 2s = VI PHẠM")
print(f"📸 Ảnh vi phạm sẽ tự động lưu vào folder: {base_violation_dir}")
print("=" * 80)


# ===========================
# HÀM VẼ LANE
# ===========================
def draw_beautiful_lanes(frame, lanes, colors, names, alpha=0.3):
    overlay = frame.copy()
    for lane, color, name in zip(lanes, colors, names):
        cv2.fillPoly(overlay, [lane], color)
        cv2.polylines(overlay, [lane], isClosed=True, color=color, thickness=4)

        M = cv2.moments(lane)
        if M["m00"] != 0:
            cx = int(M["m10"] / M["m00"])
            cy = int(M["m01"] / M["m00"])
            cv2.putText(overlay, name, (cx - 100, cy + 2),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 0, 0), 8)
            cv2.putText(overlay, name, (cx - 100, cy),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 3)

    cv2.addWeighted(overlay, alpha, frame, 1 - alpha, 0, frame)


# ===========================
# XÁC ĐỊNH LANE
# ===========================
def get_lane_id(x, y):
    point = (x, y)
    for i, lane in enumerate(lanes):
        if cv2.pointPolygonTest(lane, point, False) >= 0:
            return i + 1
    return -1


# ===========================
# KIỂM TRA VI PHẠM VÀ ĐẾM THỜI GIAN
# ===========================
def check_violation_with_timer(track_data, current_lane, vehicle_type, current_time, track_id, frame_num):
    """
    Kiểm tra vi phạm và đếm thời gian.
    Trả về: (is_counting, is_final_violation, violation_msg, duration)
    """
    if current_lane == -1:
        if track_data['violation_start_time'] is not None:
            print(f"🔄 Frame {frame_num}: ID {track_id} rời khỏi làn vi phạm - RESET")
        track_data['violation_start_time'] = None
        track_data['current_violation_duration'] = 0
        return False, False, "", 0

    # BƯỚC 1: Kiểm tra xem có đang vi phạm không
    is_in_violation = False
    violation_msg = ""

    # Kiểm tra tên lớp
    vehicle_type_lower = vehicle_type.lower()

    if vehicle_type_lower in ["motorbike", "motorcycle", "moto", "xe máy"]:
        if current_lane == 3:
            is_in_violation = True
            violation_msg = "XE MAY VAO LAN O TO!"
            if track_data['violation_start_time'] is None:
                print(f"🚨 Frame {frame_num}: ID {track_id} ({vehicle_type}) VÀO LANE 3 - BẮT ĐẦU ĐẾM!")

    elif vehicle_type_lower in ["car", "car ", "oto", "ô tô", "xe hơi"]:
        if current_lane == 1:
            is_in_violation = True
            violation_msg = "O TO VAO LAN XE MAY!"
            if track_data['violation_start_time'] is None:
                print(f"🚨 Frame {frame_num}: ID {track_id} ({vehicle_type}) VÀO LANE 1 - BẮT ĐẦU ĐẾM!")

    # BƯỚC 2: Xử lý đếm thời gian
    if is_in_violation:
        if track_data['violation_start_time'] is None:
            track_data['violation_start_time'] = current_time
            track_data['current_violation_duration'] = 0
            return True, False, violation_msg, 0
        else:
            violation_duration = current_time - track_data['violation_start_time']
            track_data['current_violation_duration'] = violation_duration

            if frame_num % 15 == 0:
                print(f"⏱️  ID {track_id}: Đang vi phạm {violation_duration:.2f}s (cần 2.0s)")

            if violation_duration >= 2.0:
                print(f"❌ Frame {frame_num}: ID {track_id} VI PHẠM ĐỦ 2 GIÂY! ({violation_duration:.2f}s)")
                return False, True, violation_msg, violation_duration
            else:
                return True, False, violation_msg, violation_duration
    else:
        if track_data['violation_start_time'] is not None:
            print(f"✅ Frame {frame_num}: ID {track_id} không còn vi phạm - RESET")
        track_data['violation_start_time'] = None
        track_data['current_violation_duration'] = 0
        return False, False, "", 0


# ===========================
# HÀM LƯU ẢNH VI PHẠM (ĐÃ SỬA - CROP CHỈ CÓ XE, KHÔNG CÓ GÌ KHÁC)
# ===========================
def save_violation_images(original_frame, track_id, vehicle_type, violation_type,
                          box_coords, lane_info, duration, current_time, frame_num):
    """
    Lưu 2 loại ảnh:
    1. Ảnh NHỎ (crop): CHỈ xe vi phạm, KHÔNG có text, KHÔNG có zone, KHÔNG có gì khác
    2. Ảnh LỚN (full): Toàn cảnh chỉ highlight xe vi phạm bằng khung đỏ và text
    """
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S_%f")[:-3]
    x1, y1, x2, y2 = box_coords

    track_data = vehicle_tracks[track_id]
    if 'save_count' not in track_data:
        track_data['save_count'] = 0

    track_data['save_count'] += 1

    # Tạo base filename
    base_filename = f"{vehicle_type}_ID{track_id}_{timestamp}"

    # ===== 1. ẢNH NHỎ: CROP XE VI PHẠM (CHỈ CÓ XE, KHÔNG CÓ GÌ KHÁC) =====
    # Thêm padding nhỏ
    padding = 5  # Rất ít padding để chỉ có xe
    crop_x1 = max(0, x1 - padding)
    crop_y1 = max(0, y1 - padding)
    crop_x2 = min(original_frame.shape[1], x2 + padding)
    crop_y2 = min(original_frame.shape[0], y2 + padding)

    # Cắt ảnh chỉ chứa xe vi phạm - HOÀN TOÀN SẠCH, KHÔNG TEXT, KHÔNG ZONE
    cropped_vehicle = original_frame[crop_y1:crop_y2, crop_x1:crop_x2].copy()

    # Lưu ảnh crop - CHỈ CÓ XE, KHÔNG CÓ GÌ THÊM
    crop_filename = os.path.join(VIOLATION_FOLDERS['crop'], f"{base_filename}_CROP.jpg")
    cv2.imwrite(crop_filename, cropped_vehicle)

    # ===== 2. ẢNH LỚN: TOÀN CẢNH CHỈ HIGHLIGHT XE VI PHẠM =====
    # Tạo ảnh toàn cảnh SẠCH (không có lane, không có bounding box khác)
    full_scene = original_frame.copy()

    # Vẽ khung đỏ DÀY cho xe vi phạm
    cv2.rectangle(full_scene, (x1, y1), (x2, y2), (0, 0, 255), 6)

    # Vẽ mũi tên chỉ vào xe
    center_x = (x1 + x2) // 2
    center_y = (y1 + y2) // 2

    # Vẽ vòng tròn đỏ tại tâm xe
    cv2.circle(full_scene, (center_x, center_y), 10, (0, 0, 255), -1)
    cv2.circle(full_scene, (center_x, center_y), 12, (255, 255, 255), 2)

    # Vẽ text thông tin ở góc dưới trái (không che xe)
    text_info = f"VI PHAM: {violation_type}"
    text_font = cv2.FONT_HERSHEY_SIMPLEX
    text_scale = 0.8
    text_thickness = 2

    # Tính kích thước text
    (text_width, text_height), _ = cv2.getTextSize(text_info, text_font, text_scale, text_thickness)

    # Đặt text ở góc dưới bên trái
    text_x = 20
    text_y = full_scene.shape[0] - 40

    # Vẽ nền đen bán trong suốt cho text
    text_bg = full_scene.copy()
    cv2.rectangle(text_bg,
                  (text_x - 10, text_y - text_height - 10),
                  (text_x + text_width + 10, text_y + 10),
                  (0, 0, 0), -1)

    # Blend overlay
    cv2.addWeighted(text_bg, 0.6, full_scene, 0.4, 0, full_scene)

    # Vẽ text chính
    cv2.putText(full_scene, text_info, (text_x, text_y),
                text_font, text_scale, (255, 255, 255), text_thickness)

    # Thêm thông tin chi tiết
    detail_text = f"ID:{track_id} | {vehicle_type} | Lane:{lane_info}"
    cv2.putText(full_scene, detail_text, (text_x, text_y + 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (200, 200, 200), 1)

    # Thêm timestamp
    time_text = f"Time: {current_time:.1f}s | Frame: {frame_num}"
    cv2.putText(full_scene, time_text, (text_x, text_y + 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (180, 180, 180), 1)

    # Lưu ảnh full
    full_filename = os.path.join(VIOLATION_FOLDERS['full'], f"{base_filename}_FULL.jpg")
    cv2.imwrite(full_filename, full_scene)

    print(f"📸 Đã lưu ảnh vi phạm ID {track_id}:")
    print(f"   🖼️  Ảnh NHỎ (crop): {os.path.basename(crop_filename)} - CHỈ CÓ XE, KHÔNG CÓ GÌ KHÁC")
    print(f"   🖼️  Ảnh LỚN (full): {os.path.basename(full_filename)} - Toàn cảnh có highlight")
    print("   " + "-" * 50)

    return True


# ===========================
# MAIN LOOP
# ===========================
print("\n🎬 BẮT ĐẦU XỬ LÝ VIDEO...")
print("=" * 80)

# Đo thời gian xử lý
start_time = time.time()
processed_frames = 0

while True:
    ret, frame = cap.read()
    if not ret:
        break

    # Resize frame
    frame = cv2.resize(frame, (1920, 1080))
    frame_count += 1
    processed_frames += 1
    current_time = frame_count / fps

    # Tạo bản sao của frame gốc để lưu ảnh vi phạm
    original_frame = frame.copy()

    # Vẽ lanes lên frame hiển thị
    frame_display = frame.copy()
    draw_beautiful_lanes(frame_display, lanes, lane_colors, lane_names, alpha=0.25)

    # Xử lý detection và tracking với GPU
    results = model.track(frame_display, persist=True, tracker="bytetrack.yaml", device=device)[0]

    if results.boxes.id is None:
        # Chỉ hiển thị frame nếu không có detection
        cv2.imshow("Lane Violation Detection", frame_display)
        if cv2.waitKey(1) & 0xFF == 27:
            break
        continue

    # Lấy thông tin detection
    boxes = results.boxes.xyxy.cpu().numpy()
    track_ids = results.boxes.id.cpu().numpy().astype(int)
    classes = results.boxes.cls.cpu().numpy().astype(int)

    # In thông tin debug về classes
    if frame_count % 60 == 0:
        unique_classes = set(classes)
        class_names = [model.names[cls] for cls in unique_classes]
        print(f"📊 Frame {frame_count}: Phát hiện {len(boxes)} xe")

    for box, track_id, cls in zip(boxes, track_ids, classes):
        x1, y1, x2, y2 = map(int, box)
        vehicle_type = model.names[cls]

        # Chuẩn hóa tên loại xe
        if "motor" in vehicle_type.lower() or "moto" in vehicle_type.lower():
            vehicle_type = "motorcycle"
        elif "car" in vehicle_type.lower() or "truck" in vehicle_type.lower() or "bus" in vehicle_type.lower():
            vehicle_type = "car"

        cx = int((x1 + x2) / 2)
        cy = int((y1 + y2) / 2)

        current_lane = get_lane_id(cx, cy)

        track = vehicle_tracks[track_id]
        track['type'] = vehicle_type
        track['lane_history'].append(current_lane)

        # Kiểm tra vi phạm
        is_counting, is_final_violation, violation_msg, duration = check_violation_with_timer(
            track, current_lane, vehicle_type, current_time, track_id, frame_count
        )

        # Xác định màu và text
        color = (0, 255, 0)  # Mặc định: Xanh lá = OK
        status = f"ID:{track_id} {vehicle_type[:3]} L:{current_lane}"

        if is_counting:
            # ĐANG ĐẾM (0-2s) - MÀU CAM
            color = (0, 165, 255)
            status = f"ID:{track_id} Đếm {duration:.1f}s"

        elif is_final_violation:
            # VI PHẠM ĐỦ 2S - MÀU ĐỎ
            color = (0, 0, 255)
            status = f"ID:{track_id} VI PHẠM"

            # Ghi nhận vi phạm (chỉ 1 lần)
            if not track['violation_logged']:
                violations.append({
                    'frame': frame_count,
                    'time': current_time,
                    'track_id': track_id,
                    'type': vehicle_type,
                    'violation': violation_msg,
                    'violation_duration': duration,
                    'lane_history': list(track['lane_history'])[-10:],
                    'box_coords': (x1, y1, x2, y2),
                    'center': (cx, cy)
                })
                track['violation_logged'] = True
                print(f"📝 GHI NHẬN VI PHẠM #{len(violations)}: Track {track_id}")

                # LƯU ẢNH VI PHẠM
                if not track.get('violation_saved', False):
                    # Tạo thông tin lane
                    lane_info = f"Lane {current_lane}"
                    if current_lane == 1:
                        lane_info += " (XE MAY)"
                    elif current_lane == 2:
                        lane_info += " (HON HOP)"
                    elif current_lane == 3:
                        lane_info += " (O TO)"

                    # Lưu ảnh VI PHẠM
                    save_success = save_violation_images(
                        original_frame,  # Sử dụng ảnh gốc, không có lane
                        track_id,
                        vehicle_type,
                        violation_msg,
                        (x1, y1, x2, y2),
                        lane_info,
                        duration,
                        current_time,
                        frame_count
                    )

                    if save_success:
                        track['violation_saved'] = True
                        track['last_violation_frame'] = frame_count

        # Reset flag nếu không còn vi phạm
        if not is_counting and not is_final_violation:
            track['violation_logged'] = False
            track['violation_saved'] = False

        # Vẽ bounding box lên frame hiển thị
        cv2.rectangle(frame_display, (x1, y1), (x2, y2), color, 3)
        cv2.circle(frame_display, (cx, cy), 6, color, -1)

        # Vẽ text với background
        (text_w, text_h), _ = cv2.getTextSize(status, cv2.FONT_HERSHEY_SIMPLEX, 0.6, 2)
        cv2.rectangle(frame_display, (x1, y1 - text_h - 10), (x1 + text_w + 10, y1), color, -1)
        cv2.putText(frame_display, status, (x1 + 5, y1 - 5),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

    # ===========================
    # HIỂN THỊ THỐNG KÊ VÀ FPS
    # ===========================
    # Tính FPS thực tế
    elapsed_time = time.time() - start_time
    current_fps = processed_frames / elapsed_time if elapsed_time > 0 else 0

    stats_bg = np.zeros((150, 500, 3), dtype=np.uint8)
    stats_bg[:] = (40, 40, 40)

    cv2.putText(stats_bg, f"Frame: {frame_count}", (10, 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
    cv2.putText(stats_bg, f"Time: {current_time:.1f}s", (10, 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)
    cv2.putText(stats_bg, f"Tracked: {len(track_ids)}", (10, 90),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
    cv2.putText(stats_bg, f"Violations: {len(violations)}", (10, 120),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)

    # Hiển thị FPS
    cv2.putText(stats_bg, f"FPS: {current_fps:.1f}", (250, 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 0), 2)

    # Hiển thị device
    device_text = f"Device: {device.upper()}"
    cv2.putText(stats_bg, device_text, (250, 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

    # Chú thích màu
    cv2.putText(stats_bg, "GREEN=OK", (250, 90),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)
    cv2.putText(stats_bg, "ORANGE=Counting", (250, 120),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 165, 255), 2)

    frame_display[10:160, 10:510] = stats_bg

    # Hiển thị frame
    cv2.imshow("Lane Violation Detection", frame_display)

    # Điều khiển bằng phím
    key = cv2.waitKey(1) & 0xFF
    if key == 27:  # ESC
        break
    elif key == ord('p'):  # Pause
        print("⏸️  Tạm dừng - Nhấn phím bất kỳ để tiếp tục...")
        cv2.waitKey(0)
    elif key == ord('s'):  # Save current frame
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        debug_filename = os.path.join(base_violation_dir, f"debug_frame_{timestamp}.jpg")
        cv2.imwrite(debug_filename, frame_display)
        print(f"💾 Đã lưu frame debug: {debug_filename}")

# ===========================
# XUẤT BÁO CÁO
# ===========================
cap.release()
cv2.destroyAllWindows()

# Tính tổng thời gian xử lý
total_time = time.time() - start_time
print(f"\n⏱️  Tổng thời gian xử lý: {total_time:.1f}s")
print(f"📊 Tốc độ xử lý trung bình: {processed_frames / total_time:.1f} FPS")

print("\n" + "=" * 80)
print("📊 BÁO CÁO VI PHẠM LÀN ĐƯỜNG")
print("=" * 80)
print(f"📁 Thư mục lưu ảnh: {base_violation_dir}")

# Đếm số ảnh đã lưu
crop_count = len([f for f in os.listdir(VIOLATION_FOLDERS['crop']) if f.endswith(('.jpg', '.png', '.jpeg'))])
full_count = len([f for f in os.listdir(VIOLATION_FOLDERS['full']) if f.endswith(('.jpg', '.png', '.jpeg'))])

print(f"   - crop/ (ảnh NHỎ - CHỈ CÓ XE): {crop_count} ảnh")
print(f"   - full/ (ảnh LỚN - toàn cảnh): {full_count} ảnh")

print("\n" + "=" * 80)
print("📋 CHI TIẾT TỪNG VI PHẠM:")
print("=" * 80)
for i, v in enumerate(violations, 1):
    print(f"\n{i}. Frame {v['frame']} (t={v['time']:.1f}s):")
    print(f"   - Track ID: {v['track_id']}")
    print(f"   - Loại xe: {v['type']}")
    print(f"   - Vi phạm: {v['violation']}")
    print(f"   - Thời gian vi phạm: {v['violation_duration']:.1f} giây")

print(f"\n✅ Tổng số vi phạm (>2s): {len(violations)}")
print("=" * 80)

# Lưu báo cáo
report_filename = os.path.join(base_violation_dir, f"violation_report_{time.strftime('%Y%m%d_%H%M%S')}.txt")
with open(report_filename, 'w', encoding='utf-8') as f:
    f.write("=" * 80 + "\n")
    f.write("BÁO CÁO VI PHẠM LÀN ĐƯỜNG (VI PHẠM > 2 GIÂY)\n")
    f.write("=" * 80 + "\n")
    f.write(f"Video: {video_path}\n")
    f.write(f"Thời gian phân tích: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
    f.write(f"Tổng số frame: {frame_count}\n")
    f.write(f"FPS video: {fps:.2f}\n")
    f.write(f"Tổng thời gian video: {frame_count / fps:.1f} giây\n")
    f.write(f"Tổng thời gian xử lý: {total_time:.1f} giây\n")
    f.write(f"Tốc độ xử lý: {processed_frames / total_time:.1f} FPS\n")
    f.write(f"Tổng số vi phạm: {len(violations)}\n\n")

    f.write(f"THƯ MỤC LƯU ẢNH:\n")
    f.write(f"  - crop/ (ảnh NHỎ - CHỈ CÓ XE): {crop_count} ảnh\n")
    f.write(f"  - full/ (ảnh LỚN - toàn cảnh): {full_count} ảnh\n\n")

    for i, v in enumerate(violations, 1):
        f.write(f"{i}. Frame {v['frame']} (t={v['time']:.1f}s):\n")
        f.write(f"   - Track ID: {v['track_id']}\n")
        f.write(f"   - Loại xe: {v['type']}\n")
        f.write(f"   - Vi phạm: {v['violation']}\n")
        f.write(f"   - Thời gian vi phạm: {v['violation_duration']:.1f} giây\n\n")

    f.write("=" * 80 + "\n")

print(f"\n📄 Báo cáo chi tiết đã được lưu vào file: {report_filename}")

# Hiển thị thông tin tổng kết
print("\n🎉 PHÂN TÍCH HOÀN TẤT!")
print(f"📊 Tổng số vi phạm: {len(violations)}")
print(f"📸 Tổng số ảnh đã lưu: {crop_count} (crop - CHỈ CÓ XE) + {full_count} (full - toàn cảnh)")
print(f"📁 Mở thư mục vi phạm: {os.path.abspath(base_violation_dir)}")
print("\n🎮 ĐIỀU KHIỂN:")
print("   ESC : Thoát")
print("   P   : Tạm dừng")
print("   S   : Lưu frame hiện tại để debug")