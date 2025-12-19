import cv2
import numpy as np
from ultralytics import YOLO
from datetime import datetime, timedelta
import os
from collections import defaultdict, deque
import time

# =====================
# LOAD MODELS
# =====================
traffic_light_model = YOLO(
    r'C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\traffic_red_light_violation_detection-main\models\traffic_light\traffic_light.pt')
vehicle_model = YOLO(
    r'C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\train_vehicle\runs\detect\train2\weights\best.pt')

# =====================
# VIDEO PATH
# =====================
video_path = r'C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\ vuotdendo\video\NguyenTatThanh2.mp4'
cap = cv2.VideoCapture(video_path)

# Lấy thông số video gốc
original_width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
original_height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
original_fps = cap.get(cv2.CAP_PROP_FPS)

print(f"Video gốc: {original_width}x{original_height}, FPS: {original_fps}")

# TẠO THƯ MỤC
if not os.path.exists('crop'):
    os.makedirs('crop')
if not os.path.exists('full'):
    os.makedirs('full')
if not os.path.exists('output'):
    os.makedirs('output')

# =====================
# THÔNG SỐ VIDEO OUTPUT
# =====================
output_width = 1920
output_height = 1080
output_fps = original_fps if original_fps > 0 else 30.0

# Tạo tên file output với timestamp
timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
output_filename = f"output/violation_detection_{timestamp}.mp4"

# Tạo VideoWriter với codec H.264
fourcc = cv2.VideoWriter_fourcc(*'mp4v')
output_video = cv2.VideoWriter(
    output_filename,
    fourcc,
    output_fps,
    (output_width, output_height)
)

print(f"Video output: {output_filename}")
print(f"Kích thước: {output_width}x{output_height}, FPS: {output_fps}")

# =====================
# ZONES VÀ QUY TẮC GIAO THÔNG
# =====================
zones = {
    'stop_line': {'p1': (546, 655), 'p2': (1582, 654)},
    'left_turn_lane': np.array([[531, 685], [350, 1044], [719, 1048], [771, 687]]),  # Rẽ trái + Đi thẳng (đèn xanh)
    'straight_lane_1': np.array([[774, 688], [723, 1048], [1164, 1047], [1099, 685]]),  # CHỈ đi thẳng (đèn xanh)
    'straight_lane_2': np.array([[1101, 687], [1167, 1048], [1614, 1040], [1417, 683]]),
    # Đi thẳng + Rẽ phải (đèn xanh)
    'right_turn_lane': np.array([[1421, 683], [1618, 1040], [1874, 1042], [1589, 692]])  # Rẽ phải mọi lúc
}

# QUY TẮC GIAO THÔNG CHO TỪNG LÀN
LANE_RULES = {
    'left_turn_lane': {
        'XANH': ['left', 'straight'],  # Được rẽ trái và đi thẳng
        'DO': [],  # Không được đi
        'VANG': []  # Không được đi
    },
    'straight_lane_1': {
        'XANH': ['straight'],  # CHỈ được đi thẳng
        'DO': [],  # Không được đi
        'VANG': []  # Không được đi
    },
    'straight_lane_2': {
        'XANH': ['straight', 'right'],  # Được đi thẳng và rẽ phải
        'DO': [],  # Không được đi
        'VANG': []  # Không được đi
    },
    'right_turn_lane': {
        'XANH': ['right'],  # Được rẽ phải
        'DO': ['right'],  # CHỈ được rẽ phải (KHÔNG được đi thẳng)
        'VANG': ['right']  # CHỈ được rẽ phải
    }
}

# =====================
# TRACKING
# =====================
vehicle_tracks = defaultdict(lambda: {
    'positions': deque(maxlen=90),
    'violated': False,
    'lane_history': [],
    'current_lane': None,
    'original_lane': None,
    'turn_direction': None,
    'crossing_status': 'waiting',
    'crossing_start_time': None,
    'crossing_start_y': None,
    'crossing_end_time': None,
    'crossing_end_y': None,
    'last_detection_time': None,
    'speed_y': 0,
    'last_y': None,
    'min_y_seen': 9999,
    'max_y_seen': 0,
    'frames_in_right_lane': 0,
    'was_in_right_lane': False,
    'right_turn_expected': False,
    'light_state_when_crossing': None,
    # THÊM CÁC BIẾN CHO DỪNG SAI VẠCH
    'stopped_frames': 0,
    'is_stopped': False,
    'stopped_in_wrong_zone': False,
    'stop_start_time': None,
})

violation_logged = set()
current_light_state = 'UNKNOWN'
previous_light_state = 'UNKNOWN'
red_light_frame_count = 0
red_light_start_time = None

VEHICLE_CLASS_MAP = {0: 'Oto', 1: 'Xe may'}
LIGHT_CLASS_MAP = {0: 'XANH', 1: 'DO', 2: 'VANG'}

# THAM SỐ
RED_LIGHT_BUFFER_FRAMES = 0
STOP_LINE_MARGIN = 20
CROSSING_EVALUATION_TIME = 3.5
MIN_CROSSING_DISTANCE = 50
SHOW_ZONES = True
ZONE_TRANSPARENCY = 0.3

# THAM SỐ CHO DỪNG SAI VẠCH
STOPPED_SPEED_THRESHOLD = 3.5
STOPPED_FRAMES_THRESHOLD = 30
STOP_ZONE_BUFFER = 150

DEBUG = True
FRAME_SKIP = 1
frame_counter = 0

# Biến đếm tổng số frame
total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))
print(f"Tổng số frame trong video: {total_frames}")


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

            if current_light_state == 'DO':
                red_light_frame_count += 1
                if red_light_start_time is None:
                    red_light_start_time = time.time()
            else:
                red_light_frame_count = 0
                red_light_start_time = None

            if previous_light_state != 'DO' and current_light_state == 'DO':
                red_light_frame_count = 0
                red_light_start_time = time.time()
                if DEBUG:
                    print(f"\n{'=' * 60}")
                    print(f" DEN CHUYEN DO - BAT DAU GIAM SAT!")
                    print(f"{'=' * 60}\n")
            elif previous_light_state != 'XANH' and current_light_state == 'XANH':
                if DEBUG:
                    print(f"\n{'=' * 60}")
                    print(f" DEN CHUYEN XANH - GIAM SAT TIEP!")
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
        return 'left_turn_lane'
    elif point_in_polygon(vehicle_center, zones['straight_lane_1']):
        return 'straight_lane_1'
    elif point_in_polygon(vehicle_center, zones['straight_lane_2']):
        return 'straight_lane_2'
    elif point_in_polygon(vehicle_center, zones['right_turn_lane']):
        return 'right_turn_lane'
    return 'unknown'


def analyze_turn_direction(track_info, current_time):
    """Phân tích hướng đi dựa trên lịch sử di chuyển"""
    positions = list(track_info['positions'])

    if len(positions) < 20:
        return None

    old_positions = positions[:min(12, len(positions) // 2)]
    new_positions = positions[-min(12, len(positions) // 2):]

    if not old_positions or not new_positions:
        return None

    avg_old_x = sum(p[0] for p in old_positions) / len(old_positions)
    avg_old_y = sum(p[1] for p in old_positions) / len(old_positions)
    avg_new_x = sum(p[0] for p in new_positions) / len(new_positions)
    avg_new_y = sum(p[1] for p in new_positions) / len(new_positions)

    x_change = avg_new_x - avg_old_x
    y_change = avg_old_y - avg_new_y

    if abs(x_change) > 5 and abs(x_change) > abs(y_change) * 1.05:
        return 'right' if x_change > 0 else 'left'
    elif abs(y_change) > 25 and abs(y_change) > abs(x_change) * 1.2:
        return 'straight'

    return None


def check_violation_with_rules(original_lane, turn_direction, light_state):
    """
    Kiểm tra vi phạm dựa trên quy tắc giao thông
    Returns: (is_violation, violation_type)
    """
    if original_lane not in LANE_RULES or turn_direction is None:
        return False, None

    # Lấy danh sách hướng được phép đi với trạng thái đèn hiện tại
    allowed_directions = LANE_RULES[original_lane].get(light_state, [])

    # Nếu hướng đi không nằm trong danh sách được phép -> vi phạm
    if turn_direction not in allowed_directions:
        # Xác định loại vi phạm cụ thể
        violation_type = generate_violation_type(original_lane, turn_direction, light_state)
        return True, violation_type

    return False, None


def generate_violation_type(lane, direction, light_state):
    """Tạo mã vi phạm chi tiết dựa trên làn, hướng đi và đèn"""
    lane_name_map = {
        'left_turn_lane': 'LAN_RE_TRAI',
        'straight_lane_1': 'LAN_DI_THANG_1',
        'straight_lane_2': 'LAN_DI_THANG_2',
        'right_turn_lane': 'LAN_RE_PHAI'
    }

    direction_map = {
        'left': 'RE_TRAI',
        'straight': 'DI_THANG',
        'right': 'RE_PHAI'
    }

    lane_display = lane_name_map.get(lane, lane.upper())
    direction_display = direction_map.get(direction, direction.upper())

    return f"DEN_{light_state}_{direction_display}_SAI_O_{lane_display}"


def detect_violations(tracked_objects, frame_time):
    violations = []
    stop_line_y = zones['stop_line']['p1'][1]
    stop_line_upper = stop_line_y - STOP_LINE_MARGIN
    stop_line_lower = stop_line_y + STOP_LINE_MARGIN

    if current_light_state != previous_light_state:
        for track_id in list(vehicle_tracks.keys()):
            if vehicle_tracks[track_id]['crossing_status'] == 'crossing':
                vehicle_tracks[track_id]['crossing_status'] = 'waiting'
                vehicle_tracks[track_id]['crossing_start_time'] = None
                vehicle_tracks[track_id]['crossing_start_y'] = None

    if current_light_state == 'DO' and red_light_frame_count <= RED_LIGHT_BUFFER_FRAMES:
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

        # Cập nhật vị trí và tốc độ
        if track_info['last_y'] is not None:
            speed = abs(vehicle_y - track_info['last_y'])
            track_info['speed_y'] = track_info['speed_y'] * 0.7 + speed * 0.3

        track_info['last_y'] = vehicle_y
        track_info['last_detection_time'] = frame_time
        track_info['positions'].append(vehicle_center)

        if vehicle_y < track_info['min_y_seen']:
            track_info['min_y_seen'] = vehicle_y
        if vehicle_y > track_info['max_y_seen']:
            track_info['max_y_seen'] = vehicle_y

        current_lane = get_vehicle_lane(vehicle_center)
        track_info['current_lane'] = current_lane

        if current_lane != 'unknown' and (
                not track_info['lane_history'] or track_info['lane_history'][-1] != current_lane):
            track_info['lane_history'].append(current_lane)
            if len(track_info['lane_history']) > 5:
                track_info['lane_history'].pop(0)

        if track_info['original_lane'] is None and current_lane != 'unknown':
            track_info['original_lane'] = current_lane
            if DEBUG:
                print(f"ID={track_id} vao {current_lane}")

        is_above_line = vehicle_y < stop_line_upper
        is_below_line = vehicle_y > stop_line_lower
        is_crossing_line = not is_above_line and not is_below_line

        # =====================================================
        # PHÁT HIỆN XE DỪNG SAI VẠCH TRONG RIGHT_TURN_LANE
        # =====================================================
        if current_lane == 'right_turn_lane' and current_light_state == 'DO':
            if track_info['speed_y'] < STOPPED_SPEED_THRESHOLD:
                if not track_info['is_stopped']:
                    track_info['is_stopped'] = True
                    track_info['stopped_frames'] = 1
                    track_info['stop_start_time'] = frame_time
                else:
                    track_info['stopped_frames'] += 1

                stop_zone_limit = stop_line_y + STOP_ZONE_BUFFER
                is_in_prohibited_zone = vehicle_y > stop_line_upper and vehicle_y < stop_zone_limit

                if (track_info['stopped_frames'] >= STOPPED_FRAMES_THRESHOLD and
                        is_in_prohibited_zone and
                        not track_info['stopped_in_wrong_zone'] and
                        not track_info['violated']):

                    track_info['violated'] = True
                    track_info['stopped_in_wrong_zone'] = True

                    stop_duration = frame_time - track_info['stop_start_time']

                    violations.append({
                        'type': 'DUNG_SAI_VACH_TRONG_LAN_RE_PHAI',
                        'vehicle_type': vehicle_type,
                        'conf': conf,
                        'box': box,
                        'center': vehicle_center,
                        'id': track_id,
                        'lane': current_lane,
                        'turn_direction': 'stopped',
                        'speed': track_info['speed_y'],
                        'light_state': current_light_state,
                        'stop_duration': stop_duration
                    })
                    violation_logged.add(track_id)

                    if DEBUG:
                        print(f"\n{'=' * 60}")
                        print(f" VI PHAM: DUNG SAI VACH!")
                        print(f"ID: {track_id} | Loai xe: {vehicle_type}")
                        print(f"Vi tri Y: {vehicle_y} | Vach dung: {stop_line_y}")
                        print(f"Thoi gian dung: {stop_duration:.1f}s")
                        print(f"{'=' * 60}\n")
            else:
                track_info['is_stopped'] = False
                track_info['stopped_frames'] = 0
                track_info['stop_start_time'] = None

        # Kiểm tra vượt vạch
        if track_info['crossing_status'] == 'waiting' and is_below_line:
            track_info['crossing_status'] = 'crossing'
            track_info['crossing_start_time'] = frame_time
            track_info['crossing_start_y'] = vehicle_y
            track_info['crossing_start_lane'] = current_lane
            track_info['light_state_when_crossing'] = current_light_state

            if DEBUG:
                print(
                    f" ID={track_id} BAT DAU VUOT VACH tai Y={vehicle_y}, Lane={current_lane}, Den={current_light_state}")

        elif track_info['crossing_status'] == 'crossing' and is_above_line:
            track_info['crossing_status'] = 'crossed'
            track_info['crossing_end_time'] = frame_time
            track_info['crossing_end_y'] = vehicle_y

            if DEBUG:
                print(
                    f" ID={track_id} DA VUOT VACH, bat dau danh gia {CROSSING_EVALUATION_TIME}s..., Den khi vuot: {track_info['light_state_when_crossing']}")

        # =====================================================
        # ĐÁNH GIÁ VI PHẠM DỰA TRÊN QUY TẮC GIAO THÔNG
        # =====================================================
        if (track_info['crossing_status'] == 'crossed' and
                not track_info['violated'] and
                track_info['original_lane'] is not None):

            time_since_crossing = frame_time - track_info['crossing_end_time']

            if time_since_crossing >= CROSSING_EVALUATION_TIME:
                turn_direction = analyze_turn_direction(track_info, frame_time)

                if turn_direction is not None:
                    original_lane = track_info['original_lane']
                    light_state_when_crossed = track_info['light_state_when_crossing']

                    # Kiểm tra vi phạm dựa trên quy tắc
                    is_violation, violation_type = check_violation_with_rules(
                        original_lane, turn_direction, light_state_when_crossed
                    )

                    if is_violation:
                        track_info['violated'] = True
                        track_info['turn_direction'] = turn_direction

                        violations.append({
                            'type': violation_type,
                            'vehicle_type': vehicle_type,
                            'conf': conf,
                            'box': box,
                            'center': vehicle_center,
                            'id': track_id,
                            'lane': original_lane,
                            'turn_direction': turn_direction,
                            'speed': track_info['speed_y'],
                            'light_state': light_state_when_crossed
                        })
                        violation_logged.add(track_id)

                        if DEBUG:
                            print(f"\n{'=' * 60}")
                            print(f"⚡ VI PHAM PHAT HIEN!")
                            print(f"ID: {track_id} | Loai xe: {vehicle_type}")
                            print(f"Lane goc: {original_lane}")
                            print(f"Huong di thuc te: {turn_direction}")
                            print(f"Den khi vuot: {light_state_when_crossed}")
                            print(f"Loai vi pham: {violation_type}")
                            print(f"{'=' * 60}\n")

                    track_info['crossing_status'] = 'waiting'
                    track_info['crossing_start_time'] = None
                    track_info['crossing_end_time'] = None
                    track_info['light_state_when_crossing'] = None

    return violations


def save_violation_images(frame_clean, violation):
    """Lưu 2 ảnh vào 2 folder riêng: crop/ và full/"""
    print(f"\nLUU ANH VI PHAM ID={violation['id']}!")

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S_%f")
    x1, y1, x2, y2 = [int(v) for v in violation['box']]

    padding = 15
    crop_x1 = max(0, x1 - padding)
    crop_y1 = max(0, y1 - padding)
    crop_x2 = min(frame_clean.shape[1], x2 + padding)
    crop_y2 = min(frame_clean.shape[0], y2 + padding)

    cropped_vehicle = frame_clean[crop_y1:crop_y2, crop_x1:crop_x2].copy()

    crop_filename = f"crop/{violation['type']}_ID{violation['id']}_{timestamp}.jpg"
    cv2.imwrite(crop_filename, cropped_vehicle)
    print(f"   ✓ Da luu CROP: {crop_filename}")

    full_scene = frame_clean.copy()
    cv2.rectangle(full_scene, (x1, y1), (x2, y2), (0, 0, 255), 6)

    label = f"{violation['type']} ID:{violation['id']} Den:{violation.get('light_state', 'UNKNOWN')}"
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
    print(f"   ✓ Da luu FULL: {full_filename}\n")


def draw_zones(frame):
    if not SHOW_ZONES:
        return frame
    overlay = frame.copy()

    p1, p2 = zones['stop_line']['p1'], zones['stop_line']['p2']
    cv2.line(overlay, p1, p2, (255, 0, 255), 6)
    cv2.putText(overlay, f"VACH DUNG Y={p1[1]}", (p1[0], p1[1] - 15),
                cv2.FONT_HERSHEY_SIMPLEX, 0.9, (255, 0, 255), 3)

    margin = STOP_LINE_MARGIN
    cv2.line(overlay, (p1[0], p1[1] - margin), (p2[0], p2[1] - margin),
             (255, 255, 0), 2, cv2.LINE_AA)
    cv2.line(overlay, (p1[0], p1[1] + margin), (p2[0], p2[1] + margin),
             (255, 255, 0), 2, cv2.LINE_AA)

    # Vẽ vùng cấm dừng trong làn phải
    stop_zone_limit = p1[1] + STOP_ZONE_BUFFER
    cv2.line(overlay, (zones['right_turn_lane'][0][0], stop_zone_limit),
             (zones['right_turn_lane'][3][0], stop_zone_limit),
             (0, 0, 255), 3, cv2.LINE_AA)
    cv2.putText(overlay, "CAM DUNG",
                (zones['right_turn_lane'][0][0] + 50, stop_zone_limit - 10),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)

    colors = {
        'left_turn_lane': (0, 150, 255),  # Cam (Rẽ trái + Đi thẳng)
        'straight_lane_1': (0, 255, 255),  # Vàng (CHỈ đi thẳng)
        'straight_lane_2': (255, 150, 0),  # Xanh da trời (Đi thẳng + Rẽ phải)
        'right_turn_lane': (0, 255, 0)  # Xanh lá (Rẽ phải)
    }

    lane_labels = {
        'left_turn_lane': "RE TRAI + DI THANG",
        'straight_lane_1': "CHI DI THANG",
        'straight_lane_2': "DI THANG + RE PHAI",
        'right_turn_lane': "RE PHAI"
    }

    for zone_name, color in colors.items():
        cv2.fillPoly(overlay, [zones[zone_name]], color)
        cv2.polylines(overlay, [zones[zone_name]], True, (255, 255, 255), 2)

        centroid = np.mean(zones[zone_name], axis=0).astype(int)
        zone_label_vn = lane_labels[zone_name]

        cv2.putText(overlay, zone_label_vn, (centroid[0] - 100, centroid[1]),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)

    return cv2.addWeighted(frame, 1 - ZONE_TRANSPARENCY, overlay, ZONE_TRANSPARENCY, 0)


def draw_vehicle_info(frame, track_id, track_info, box):
    """Vẽ thông tin chi tiết của xe"""
    x1, y1, x2, y2 = [int(v) for v in box]
    vehicle_center = get_vehicle_center(box)

    if track_info['violated']:
        color = (0, 0, 255)
    elif track_info['is_stopped'] and track_info['current_lane'] == 'right_turn_lane':
        color = (0, 140, 255)
    elif track_info['crossing_status'] == 'crossing':
        color = (0, 165, 255)
    elif track_info['crossing_status'] == 'crossed':
        color = (255, 255, 0)
    else:
        color = (0, 255, 0)

    cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)
    cv2.circle(frame, vehicle_center, 3, (255, 0, 255), -1)

    info_lines = []
    info_lines.append(f"ID:{track_id}")

    if track_info['original_lane']:
        lane_labels = {
            'left_turn_lane': "RE TRAI+THANG",
            'straight_lane_1': "CHI THANG",
            'straight_lane_2': "THANG+RE PHAI",
            'right_turn_lane': "RE PHAI"
        }
        lane_display = lane_labels.get(track_info['original_lane'], track_info['original_lane'].upper())
        info_lines.append(f"Lane: {lane_display}")

    if track_info['is_stopped']:
        info_lines.append(f"DUNG: {track_info['stopped_frames']}f")

    if track_info['turn_direction']:
        direction_map = {
            'left': 'RE TRAI',
            'right': 'RE PHAI',
            'straight': 'DI THANG'
        }
        direction_display = direction_map.get(track_info['turn_direction'], track_info['turn_direction'])
        info_lines.append(f"Huong: {direction_display}")

    if track_info['crossing_status'] == 'crossed' and track_info['crossing_end_time']:
        time_left = CROSSING_EVALUATION_TIME - (time.time() - track_info['crossing_end_time'])
        if time_left > 0:
            info_lines.append(f"DG: {time_left:.1f}s")

    if track_info['light_state_when_crossing']:
        info_lines.append(f"Den: {track_info['light_state_when_crossing']}")

    # Hiển thị thông tin
    y_offset = y1 - 5
    for i, line in enumerate(info_lines):
        text_size = cv2.getTextSize(line, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)[0]

        cv2.rectangle(frame,
                      (x1, y_offset - text_size[1] - 5),
                      (x1 + text_size[0] + 10, y_offset),
                      color, -1)

        cv2.putText(frame, line, (x1 + 5, y_offset - 5),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.5, (255, 255, 255), 1)

        y_offset -= text_size[1] + 5


def draw_clean_ui(frame, light_state, frame_count, fps):
    h, w = frame.shape[:2]

    # Panel trên cùng
    panel_height = 110
    panel = np.zeros((panel_height, w, 3), dtype=np.uint8)
    panel[:] = (30, 30, 30)

    # Hiển thị trạng thái đèn
    light_color = {'DO': (0, 0, 255), 'XANH': (0, 255, 0), 'VANG': (0, 255, 255)}.get(light_state, (128, 128, 128))
    cv2.circle(panel, (50, 55), 25, light_color, -1)
    cv2.circle(panel, (50, 55), 27, (255, 255, 255), 2)
    cv2.putText(panel, light_state, (90, 60), cv2.FONT_HERSHEY_SIMPLEX, 0.9, light_color, 2)

    # Thông tin hệ thống
    info_x = 250
    cv2.putText(panel, f"Frame: {frame_count}/{total_frames}", (info_x, 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 255, 255), 2)
    cv2.putText(panel, f"FPS: {fps:.1f}", (info_x, 55),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 255), 2)
    cv2.putText(panel, f"Vi pham: {len(violation_logged)}", (info_x, 80),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (255, 100, 100), 2)

    # Thông tin đèn
    if current_light_state == 'DO':
        red_duration = time.time() - red_light_start_time if red_light_start_time else 0
        cv2.putText(panel, f"Den do: {red_duration:.1f}s", (info_x + 250, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
        if red_light_frame_count <= RED_LIGHT_BUFFER_FRAMES:
            cv2.putText(panel, f"BUFFER: {red_light_frame_count}/{RED_LIGHT_BUFFER_FRAMES}",
                        (info_x + 250, 55), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 165, 255), 2)
    elif current_light_state == 'XANH':
        cv2.putText(panel, f"DEN XANH - GIAM SAT", (info_x + 250, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

    frame[0:panel_height, :] = panel

    return frame


# =====================
# MAIN LOOP
# =====================
print("=" * 80)
print(" HE THONG PHAT HIEN VI PHAM GIAO THONG - CAP NHAT QUY TAC LAN DUONG")
print("=" * 80)
print(" ✓ Quy tac giao thong:")
print("   1. LAN RE TRAI (left_turn_lane):")
print("      - Den XANH: Re trai + Di thang")
print("      - Den DO/VANG: Khong duoc di")
print("")
print("   2. LAN DI THANG 1 (straight_lane_1):")
print("      - Den XANH: CHI duoc di thang")
print("      - Den DO/VANG: Khong duoc di")
print("")
print("   3. LAN DI THANG 2 (straight_lane_2):")
print("      - Den XANH: Di thang + Re phai")
print("      - Den DO/VANG: Khong duoc di")
print("")
print("   4. LAN RE PHAI (right_turn_lane):")
print("      - Moi luc: Re phai")
print("      - Den DO: CHI re phai (KHONG duoc di thang)")
print("")
print(" ✓ Phat hien dung sai vach trong lan re phai (den do)")
print("=" * 80)

start_time = time.time()
frame_count = 0
processed_frame_count = 0

while cap.isOpened():
    ret, frame = cap.read()
    if not ret:
        break

    frame_counter += 1
    if frame_counter % FRAME_SKIP != 0:
        continue

    frame_count += 1
    processed_frame_count += 1

    frame = cv2.resize(frame, (output_width, output_height))
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
            draw_vehicle_info(frame_display, int(obj[4]), vehicle_tracks[int(obj[4])], obj[:4])

        for v in violations:
            save_violation_images(frame_clean, v)

    elapsed_time = time.time() - start_time
    fps = processed_frame_count / elapsed_time if elapsed_time > 0 else 0
    frame_display = draw_clean_ui(frame_display, light_state, processed_frame_count, fps)

    # Ghi frame vào video output
    output_video.write(frame_display)

    cv2.imshow('He Thong Phat Hien Vi Pham Giao Thong', frame_display)

    key = cv2.waitKey(1) & 0xFF
    if key == 27:  # ESC để thoát
        break

# Giải phóng tài nguyên
cap.release()
output_video.release()
cv2.destroyAllWindows()

print(f"\n{'=' * 80}")
print(" TONG KET:")
print(f"{'=' * 80}")
print(f"    Tong frame trong video: {total_frames}")
print(f"    Tong frame xu ly: {processed_frame_count}")
print(f"    Tong vi pham: {len(violation_logged)}")
print(f"    FPS trung binh: {fps:.1f}")
print(f"    Thoi gian chay: {elapsed_time:.1f}s")
print(f"    Anh crop: ./crop/")
print(f"    Anh full: ./full/")
print(f"    Video output: {output_filename}")
print(f"{'=' * 80}")
