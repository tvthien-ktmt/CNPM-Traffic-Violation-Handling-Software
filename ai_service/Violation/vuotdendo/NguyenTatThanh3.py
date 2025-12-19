import cv2
import numpy as np
from ultralytics import YOLO
from datetime import datetime
import os
from collections import defaultdict, deque
import time

# =====================
# LOAD MODELS
# =====================
traffic_light_model = YOLO(
    r'D:\xampp\htdocs\traffic\ai_service\yolo\traffic_light.pt')
vehicle_model = YOLO(
    r'D:\xampp\htdocs\traffic\ai_service\yolo\vehicle.pt')

# =====================
# VIDEO PATH
# =====================
video_path = r'D:\xampp\htdocs\traffic\ai_service\Violation\vuotdendo\NguyenTatThanh2.mp4'
cap = cv2.VideoCapture(video_path)

# TẠO 2 FOLDER RIÊNG
if not os.path.exists('crop'):
    os.makedirs('crop')
if not os.path.exists('full'):
    os.makedirs('full')

# =====================
# ZONES
# =====================
zones = {
    'stop_line': {'p1': (546, 665), 'p2': (1582, 654)},
    'left_turn_lane': np.array([[531, 685], [350, 1044], [719, 1048], [771, 687]]),
    'straight_lane_1': np.array([[774, 688], [723, 1048], [1164, 1047], [1099, 685]]),
    'straight_lane_2': np.array([[1101, 687], [1167, 1048], [1614, 1040], [1417, 683]]),
    'right_turn_lane': np.array([[1421, 683], [1618, 1040], [1874, 1042], [1589, 692]])
}

# =====================
# TRACKING
# =====================
vehicle_tracks = defaultdict(lambda: {
    'positions': deque(maxlen=30),
    'violated': False,
    'lane': None,
    'direction': None,
    'turn_direction': None,
    'frames_stopped_in_right_lane': 0,
    'was_below_line_when_red': False,
    'first_detected_y': None,
    'first_detected_time': None,
    'violation_checked': False,
    'min_y_seen': 9999,
    'max_y_seen': 0,
    'last_y': None,
    'speed_y': 0,
    'consecutive_above_line': 0,
    'consecutive_below_line': 0,
    'red_light_crossing_start_y': None,
    'crossing_red_light': False
})

violation_logged = set()
current_light_state = 'UNKNOWN'
previous_light_state = 'UNKNOWN'
red_light_frame_count = 0
red_light_start_time = None

VEHICLE_CLASS_MAP = {0: 'Ôtô', 1: 'Xe máy'}
LIGHT_CLASS_MAP = {0: 'GREEN', 1: 'RED', 2: 'YELLOW'}

#  THAM SỐ
RED_LIGHT_BUFFER_FRAMES = 0
MIN_CROSSING_SPEED_Y = 10
SPEED_SMOOTHING = 0.3
STOP_LINE_MARGIN = 20
SHOW_ZONES = True
ZONE_TRANSPARENCY = 0.3

DEBUG = True
FRAME_SKIP = 1
frame_counter = 0


def detect_traffic_light(frame):
    global current_light_state, previous_light_state, red_light_frame_count, red_light_start_time
    previous_light_state = current_light_state
    results = traffic_light_model(frame, verbose=False)

    if len(results) > 0 and results[0].boxes is not None and len(results[0].boxes) > 0:
        boxes = results[0].boxes.data.cpu().numpy()
        best_detection = max(boxes, key=lambda x: x[4])
        class_id = int(best_detection[5])
        confidence = best_detection[4]

        if confidence > 0.5:
            detected_light = LIGHT_CLASS_MAP.get(class_id, 'UNKNOWN')
            current_light_state = detected_light

            if current_light_state == 'RED':
                red_light_frame_count += 1
                if red_light_start_time is None:
                    red_light_start_time = time.time()
            else:
                red_light_frame_count = 0
                red_light_start_time = None

            if previous_light_state != 'RED' and current_light_state == 'RED':
                red_light_frame_count = 0
                red_light_start_time = time.time()
                print(f"\n{'=' * 60}")
                print(f" ĐÈN CHUYỂN ĐỎ - BẮT ĐẦU GIÁM SÁT!")
                print(f"{'=' * 60}\n")

            return detected_light, confidence

    return current_light_state, 0.0


def point_in_polygon(point, polygon):
    return cv2.pointPolygonTest(polygon, point, False) >= 0


def get_vehicle_center(box):
    x1, y1, x2, y2 = box
    return (int((x1 + x2) / 2), int(y2))


def get_vehicle_lane(vehicle_center):
    if point_in_polygon(vehicle_center, zones['left_turn_lane']):
        return 'left_turn'
    elif point_in_polygon(vehicle_center, zones['straight_lane_1']):
        return 'straight_1'
    elif point_in_polygon(vehicle_center, zones['straight_lane_2']):
        return 'straight_2'
    elif point_in_polygon(vehicle_center, zones['right_turn_lane']):
        return 'right_turn'
    return 'unknown'


def is_moving_towards_camera(positions):
    if len(positions) < 3:
        return None
    y_change = positions[0][1] - positions[-1][1]
    if y_change > 5:
        return True
    elif y_change < -5:
        return False
    return None


def detect_turn_direction(positions, current_lane):
    if len(positions) < 5:
        return None

    x_change = positions[-1][0] - positions[0][0]
    y_change = positions[0][1] - positions[-1][1]

    if abs(x_change) > 30 and abs(x_change) > abs(y_change) * 1.5:
        return 'right' if x_change > 0 else 'left'
    elif abs(y_change) > 20 and abs(y_change) > abs(x_change) * 1.2:
        return 'straight'

    return None


def detect_violations(tracked_objects, frame_time):
    violations = []
    stop_line_y = zones['stop_line']['p1'][1]
    stop_line_upper = stop_line_y - STOP_LINE_MARGIN
    stop_line_lower = stop_line_y + STOP_LINE_MARGIN

    if current_light_state != 'RED':
        for track_id in list(vehicle_tracks.keys()):
            vehicle_tracks[track_id]['crossing_red_light'] = False
            vehicle_tracks[track_id]['red_light_crossing_start_y'] = None
        return violations

    if red_light_frame_count <= RED_LIGHT_BUFFER_FRAMES:
        if DEBUG:
            print(f" Buffer: {red_light_frame_count}/{RED_LIGHT_BUFFER_FRAMES}")
        return violations

    for obj in tracked_objects:
        box = obj[:4]
        track_id = int(obj[4])
        conf = obj[5]
        class_id = int(obj[6])

        if conf < 0.3:
            continue

        vehicle_type = VEHICLE_CLASS_MAP.get(class_id, 'UNKNOWN')
        if vehicle_type == 'UNKNOWN':
            continue

        vehicle_center = get_vehicle_center(box)
        vehicle_y = vehicle_center[1]

        track_info = vehicle_tracks[track_id]

        if track_info['last_y'] is not None:
            speed = abs(vehicle_y - track_info['last_y'])
            track_info['speed_y'] = track_info['speed_y'] * (1 - SPEED_SMOOTHING) + speed * SPEED_SMOOTHING

        track_info['last_y'] = vehicle_y
        track_info['positions'].append(vehicle_center)

        if vehicle_y < track_info['min_y_seen']:
            track_info['min_y_seen'] = vehicle_y
        if vehicle_y > track_info['max_y_seen']:
            track_info['max_y_seen'] = vehicle_y

        current_lane = get_vehicle_lane(vehicle_center)
        track_info['lane'] = current_lane

        moving_dir = is_moving_towards_camera(track_info['positions'])
        track_info['direction'] = moving_dir

        if moving_dir == False or track_info['violated']:
            continue

        if track_info['first_detected_time'] is None:
            track_info['first_detected_time'] = frame_time
            track_info['first_detected_y'] = vehicle_y

        turn_dir = detect_turn_direction(track_info['positions'], current_lane)
        if turn_dir:
            track_info['turn_direction'] = turn_dir

        is_above_line = vehicle_y < stop_line_upper
        is_below_line = vehicle_y > stop_line_lower

        if is_above_line:
            track_info['consecutive_above_line'] += 1
            track_info['consecutive_below_line'] = 0
        elif is_below_line:
            track_info['consecutive_below_line'] += 1
            track_info['consecutive_above_line'] = 0
        else:
            track_info['consecutive_above_line'] = 0
            track_info['consecutive_below_line'] = 0

        # =====================================================
        # 🔧 CORRECTION PRINCIPALE: Autoriser virage droite depuis voie droite
        # =====================================================

        # Si le véhicule est dans la voie de droite ET tourne à droite
        # -> PAS de violation même au feu rouge (virage à droite autorisé)
        if current_lane == 'right_turn' and turn_dir == 'right':
            if DEBUG and not track_info.get('right_turn_allowed_logged'):
                print(f"✓ ID={track_id} AUTORISÉ: Virage droite depuis voie droite au feu rouge")
                track_info['right_turn_allowed_logged'] = True
            # On ne vérifie PAS de violation pour ce cas
            continue

        # =====================================================
        # Détection des violations RÉELLES
        # =====================================================

        if (is_below_line and
                track_info['consecutive_below_line'] >= 1 and
                not track_info['crossing_red_light']):
            track_info['crossing_red_light'] = True
            track_info['red_light_crossing_start_y'] = vehicle_y
            if DEBUG:
                print(f" ID={track_id} BẮT ĐẦU VƯỢT ĐÈN ĐỎ tại Y={vehicle_y}")

        if (track_info['crossing_red_light'] and
                is_above_line and
                track_info['consecutive_above_line'] >= 1):

            # VIOLATION 1: Aller tout droit dans la voie de droite
            if current_lane == 'right_turn' and turn_dir == 'straight':
                violation_type = 'ĐI_THẲNG_Ở_LÀN_RẼ_PHẢI'

            # VIOLATION 2: Tourner à droite depuis une voie qui n'est PAS la voie de droite
            elif current_lane in ['straight_1', 'straight_2', 'left_turn'] and turn_dir == 'right':
                violation_type = 'RẼ_PHẢI_SAI_LÀN'

            # VIOLATION 3: Tourner à gauche depuis la voie de gauche au feu rouge
            elif current_lane == 'left_turn' and turn_dir == 'left':
                violation_type = 'VƯỢT_ĐÈN_ĐỎ_RẼ_TRÁI'

            # VIOLATION 4: Aller tout droit au feu rouge (depuis voies centrales/gauche)
            elif current_lane in ['straight_1', 'straight_2', 'left_turn'] and turn_dir == 'straight':
                violation_type = 'VƯỢT_ĐÈN_ĐỎ_ĐI_THẲNG'
            else:
                # Autres cas non spécifiés
                continue

            track_info['violated'] = True
            violations.append({
                'type': violation_type,
                'vehicle_type': vehicle_type,
                'conf': conf, 'box': box, 'center': vehicle_center,
                'id': track_id, 'lane': current_lane,
                'speed': track_info['speed_y']
            })
            violation_logged.add(track_id)

            track_info['crossing_red_light'] = False
            track_info['red_light_crossing_start_y'] = None

            if DEBUG:
                print(f"\n{'=' * 60}")
                print(f"⚡⚡⚡ VI PHẠM VƯỢT ĐÈN ĐỎ ⚡⚡⚡")
                print(f"ID: {track_id} | Loại: {vehicle_type}")
                print(f"Vi phạm: {violation_type}")
                print(f"Làn: {current_lane} | Hướng: {turn_dir}")
                print(f"Tốc độ Y: {track_info['speed_y']:.1f} px/frame")
                print(f"{'=' * 60}\n")

        # VIOLATION: S'arrêter dans la voie de droite (blocage)
        if current_lane == 'right_turn':
            # Si aller tout droit depuis voie droite
            if turn_dir == 'straight' and is_above_line:
                track_info['violated'] = True
                violations.append({
                    'type': 'ĐI_THẲNG_Ở_LÀN_RẼ_PHẢI',
                    'vehicle_type': vehicle_type,
                    'conf': conf, 'box': box, 'center': vehicle_center,
                    'id': track_id, 'lane': current_lane
                })
                violation_logged.add(track_id)

            # S'arrêter trop longtemps dans la voie de droite (blocage)
            track_info['frames_stopped_in_right_lane'] += 1
            if (track_info['frames_stopped_in_right_lane'] >= 15 and
                    track_info['speed_y'] < 2 and
                    turn_dir != 'right'):  # 🔧 AJOUT: Sauf si tourne à droite
                track_info['violated'] = True
                violations.append({
                    'type': 'ĐẬU_SAI_VỊ_TRÍ',
                    'vehicle_type': vehicle_type,
                    'conf': conf, 'box': box, 'center': vehicle_center,
                    'id': track_id, 'lane': current_lane
                })
                violation_logged.add(track_id)
        else:
            track_info['frames_stopped_in_right_lane'] = 0

        # VIOLATION: Tourner à droite depuis mauvaise voie
        if (current_lane in ['straight_1', 'straight_2', 'left_turn'] and
                turn_dir == 'right' and
                is_above_line):
            track_info['violated'] = True
            violations.append({
                'type': 'RẼ_PHẢI_SAI_LÀN',
                'vehicle_type': vehicle_type,
                'conf': conf, 'box': box, 'center': vehicle_center,
                'id': track_id, 'lane': current_lane
            })
            violation_logged.add(track_id)

    return violations


def save_violation_images(frame_clean, violation):
    """Lưu 2 ảnh vào 2 folder riêng: crop/ và full/"""
    print(f"\n🔥 BẮT ĐẦU LƯU ẢNH VI PHẠM ID={violation['id']}!")

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S_%f")
    x1, y1, x2, y2 = [int(v) for v in violation['box']]

    # ===== ẢNH 1: CROP XE VI PHẠM - HOÀN TOÀN SẠCH =====
    padding = 15
    crop_x1 = max(0, x1 - padding)
    crop_y1 = max(0, y1 - padding)
    crop_x2 = min(frame_clean.shape[1], x2 + padding)
    crop_y2 = min(frame_clean.shape[0], y2 + padding)

    cropped_vehicle = frame_clean[crop_y1:crop_y2, crop_x1:crop_x2].copy()

    crop_filename = f"crop/{violation['type']}_ID{violation['id']}_{timestamp}.jpg"
    cv2.imwrite(crop_filename, cropped_vehicle)
    print(f"    Đã lưu CROP: {crop_filename}")

    # ===== ẢNH 2: TOÀN CẢNH - CHỈ CÓ KHUNG ĐỎ =====
    full_scene = frame_clean.copy()

    cv2.rectangle(full_scene, (x1, y1), (x2, y2), (0, 0, 255), 6)

    label = f"VI PHAM ID:{violation['id']}"
    label_size = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.7, 2)[0]

    label_x = x1
    label_y = y1 - 10
    if label_y < 30:
        label_y = y2 + 25

    cv2.rectangle(full_scene,
                  (label_x, label_y - label_size[1] - 5),
                  (label_x + label_size[0] + 10, label_y + 5),
                  (0, 0, 255), -1)
    cv2.putText(full_scene, label, (label_x + 5, label_y),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)

    full_filename = f"full/{violation['type']}_ID{violation['id']}_{timestamp}.jpg"
    cv2.imwrite(full_filename, full_scene)
    print(f"   Đã lưu FULL: {full_filename}\n")


def draw_zones(frame):
    if not SHOW_ZONES:
        return frame
    overlay = frame.copy()

    p1, p2 = zones['stop_line']['p1'], zones['stop_line']['p2']
    cv2.line(overlay, p1, p2, (255, 0, 0), 6)
    cv2.putText(overlay, f"VACH DUNG Y={p1[1]}", (p1[0], p1[1] - 15),
                cv2.FONT_HERSHEY_SIMPLEX, 0.9, (255, 0, 0), 3)

    margin = STOP_LINE_MARGIN
    cv2.line(overlay, (p1[0], p1[1] - margin), (p2[0], p2[1] - margin),
             (255, 255, 0), 2, cv2.LINE_AA)
    cv2.line(overlay, (p1[0], p1[1] + margin), (p2[0], p2[1] + margin),
             (255, 255, 0), 2, cv2.LINE_AA)

    cv2.fillPoly(overlay, [zones['left_turn_lane']], (0, 0, 255))
    cv2.polylines(overlay, [zones['left_turn_lane']], True, (255, 255, 255), 2)

    cv2.fillPoly(overlay, [zones['straight_lane_1']], (0, 255, 255))
    cv2.fillPoly(overlay, [zones['straight_lane_2']], (0, 255, 255))
    cv2.polylines(overlay, [zones['straight_lane_1']], True, (255, 255, 255), 2)
    cv2.polylines(overlay, [zones['straight_lane_2']], True, (255, 255, 255), 2)

    cv2.fillPoly(overlay, [zones['right_turn_lane']], (0, 255, 0))
    cv2.polylines(overlay, [zones['right_turn_lane']], True, (255, 255, 255), 2)

    return cv2.addWeighted(frame, 1 - ZONE_TRANSPARENCY, overlay, ZONE_TRANSPARENCY, 0)


def draw_clean_ui(frame, light_state, frame_count, fps):
    h, w = frame.shape[:2]

    panel_height = 80
    panel = np.zeros((panel_height, w, 3), dtype=np.uint8)
    panel[:] = (30, 30, 30)

    light_color = {'RED': (0, 0, 255), 'GREEN': (0, 255, 0), 'YELLOW': (0, 255, 255)}.get(light_state, (128, 128, 128))
    cv2.circle(panel, (50, 40), 25, light_color, -1)
    cv2.circle(panel, (50, 40), 27, (255, 255, 255), 2)
    cv2.putText(panel, light_state, (90, 50), cv2.FONT_HERSHEY_SIMPLEX, 0.8, light_color, 2)

    info_x = 300
    cv2.putText(panel, f"Frame: {frame_count}", (info_x, 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
    cv2.putText(panel, f"FPS: {fps:.1f}", (info_x, 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 255), 2)

    cv2.putText(panel, f"Vi pham: {len(violation_logged)}", (info_x + 200, 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 100, 100), 2)
    cv2.putText(panel, f"Vach dung: Y={zones['stop_line']['p1'][1]}", (info_x + 200, 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

    if current_light_state == 'RED' and red_light_frame_count <= RED_LIGHT_BUFFER_FRAMES:
        cv2.putText(panel, f"⚠ BUFFER: {red_light_frame_count}/{RED_LIGHT_BUFFER_FRAMES}",
                    (info_x + 450, 45), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 165, 255), 2)

    frame[0:panel_height, :] = panel

    return frame


# =====================
# MAIN LOOP
# =====================
print("=" * 80)
print(" HỆ THỐNG PHÁT HIỆN VI PHẠM GIAO THÔNG - VERSION CORRIGÉE")
print("=" * 80)
print(" ✓ Virage à droite autorisé depuis voie de droite au feu rouge")
print(" LƯU ẢNH VÀO 2 FOLDER:")
print("    crop/ - Ảnh xe crop sạch")
print("    full/ - Ảnh toàn cảnh có khung đỏ")
print("=" * 80)

start_time = time.time()
frame_count = 0

while cap.isOpened():
    ret, frame = cap.read()
    if not ret:
        break

    frame_counter += 1
    if frame_counter % FRAME_SKIP != 0:
        continue

    frame_count += 1
    frame = cv2.resize(frame, (1920, 1080))
    frame_time = time.time()

    frame_clean = frame.copy()

    light_state, light_conf = detect_traffic_light(frame_clean)

    results = vehicle_model.track(frame_clean, persist=True,
                                  tracker="bytetrack.yaml",
                                  conf=0.3, iou=0.4,
                                  verbose=False,
                                  classes=[0, 1])

    frame_display = draw_zones(frame.copy())

    if len(results) > 0 and results[0].boxes is not None and results[0].boxes.id is not None:
        boxes = results[0].boxes.xyxy.cpu().numpy()
        track_ids = results[0].boxes.id.cpu().numpy().astype(int)
        confs = results[0].boxes.conf.cpu().numpy()
        class_ids = results[0].boxes.cls.cpu().numpy().astype(int)

        tracked_objects = [np.concatenate([boxes[i], [track_ids[i]], [confs[i]], [class_ids[i]]])
                           for i in range(len(boxes))]

        violations = detect_violations(tracked_objects, frame_time)

        for obj in tracked_objects:
            x1, y1, x2, y2 = [int(v) for v in obj[:4]]
            track_id = int(obj[4])
            vehicle_center = get_vehicle_center(obj[:4])

            track_info = vehicle_tracks[track_id]

            if track_info.get('violated'):
                color = (0, 0, 255)
            elif track_info.get('crossing_red_light', False) and current_light_state == 'RED':
                color = (0, 165, 255)
            else:
                color = (0, 255, 0)

            cv2.rectangle(frame_display, (x1, y1), (x2, y2), color, 2)
            cv2.putText(frame_display, f"ID:{track_id}", (x1, y1 - 5),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)
            cv2.circle(frame_display, vehicle_center, 3, (255, 0, 255), -1)

        for v in violations:
            save_violation_images(frame_clean, v)

    elapsed_time = time.time() - start_time
    fps = frame_count / elapsed_time if elapsed_time > 0 else 0
    frame_display = draw_clean_ui(frame_display, light_state, frame_count, fps)

    cv2.imshow('He Thong Phat Hien Vi Pham Giao Thong', frame_display)

    key = cv2.waitKey(1) & 0xFF
    if key == 27:
        break
    elif key == ord('z'):
        SHOW_ZONES = not SHOW_ZONES
        print(f" Hiển thị zones: {'BẬT' if SHOW_ZONES else 'TẮT'}")
    elif key == ord('p'):
        print(" Tạm dừng - Nhấn phím bất kỳ để tiếp tục...")
        cv2.waitKey(0)

cap.release()
cv2.destroyAllWindows()

print(f"\n{'=' * 80}")
print(" TỔNG KẾT:")
print(f"{'=' * 80}")
print(f"    Tổng frame xử lý: {frame_count}")
print(f"    Tổng vi phạm: {len(violation_logged)}")
print(f"    FPS trung bình: {fps:.1f}")
print(f"    Thời gian chạy: {elapsed_time:.1f}s")
print(f"    Ảnh crop: ./crop/")
print(f"    Ảnh full: ./full/")
print(f"{'=' * 80}")
print("\n PHÍM TẮT:")
print("   ESC - Thoát")
print("   Z   - Bật/tắt hiển thị zones")
print("   P   - Tạm dừng")