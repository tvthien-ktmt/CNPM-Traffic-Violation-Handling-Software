import cv2
import numpy as np
import torch
from ultralytics import YOLO
from collections import defaultdict, deque
import time
import os
from datetime import datetime

# ===========================
# CHECK GPU/CPU
# ===========================
print(f"PyTorch version: {torch.__version__}")
print(f"CUDA available: {torch.cuda.is_available()}")

if torch.cuda.is_available():
    device = 'cuda'
    gpu_name = torch.cuda.get_device_name(0)
    print(f"GPU detected: {gpu_name}")
else:
    device = 'cpu'
    print("CUDA not available - using CPU")

print(f"Using device: {device.upper()}")
print("=" * 80)

# ===========================
# LOAD MODEL WITH GPU SUPPORT
# ===========================
model = YOLO(
    r"C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\train_vehicle\runs\detect\train2\weights\best.pt"
)

model.to(device)

video_path = r"C:\Users\LUU VAN THANH HUY\PycharmProjects\PythonProject4\sailan\video\DienBienPhu2.mp4"
cap = cv2.VideoCapture(video_path)

# Get video properties
fps = cap.get(cv2.CAP_PROP_FPS)
if fps == 0:
    fps = 30
frame_width = 1920
frame_height = 1080
total_frames = int(cap.get(cv2.CAP_PROP_FRAME_COUNT))

# ===========================
# CREATE OUTPUT FOLDERS
# ===========================
base_violation_dir = "lane_violations"
output_dir = "output_videos"

if not os.path.exists(base_violation_dir):
    os.makedirs(base_violation_dir)
if not os.path.exists(output_dir):
    os.makedirs(output_dir)

VIOLATION_FOLDERS = {
    'crop': os.path.join(base_violation_dir, 'crop'),
    'full': os.path.join(base_violation_dir, 'full'),
}

for folder_name, folder_path in VIOLATION_FOLDERS.items():
    if not os.path.exists(folder_path):
        os.makedirs(folder_path)
        print(f" Created folder: {folder_path}")

# ===========================
# SETUP VIDEO WRITER
# ===========================
timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
output_video_path = os.path.join(output_dir, f"lane_violation_{timestamp}.mp4")
fourcc = cv2.VideoWriter_fourcc(*'mp4v')
video_writer = cv2.VideoWriter(output_video_path, fourcc, fps, (frame_width, frame_height))

print(f"📹 Output video: {output_video_path}")
print(f"📸 Violation images will be saved to: {base_violation_dir}")

# ===========================
# DECLARE LANES
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

print(f"📹 Video FPS: {fps}")
print(f"⚙️ VIOLATION RULES:")
print(f"   - Motorcycle in Lane 3 (CAR) > 2s = VIOLATION")
print(f"   - Car in Lane 1 (MOTORCYCLE) > 2s = VIOLATION")
print("=" * 80)


# ===========================
# DRAW LANES (NO TEXT)
# ===========================
def draw_beautiful_lanes(frame, lanes, colors, alpha=0.3):
    """Draw lanes without any text labels"""
    overlay = frame.copy()
    for lane, color in zip(lanes, colors):
        cv2.fillPoly(overlay, [lane], color)
        cv2.polylines(overlay, [lane], isClosed=True, color=color, thickness=4)

    cv2.addWeighted(overlay, alpha, frame, 1 - alpha, 0, frame)


# ===========================
# GET LANE ID
# ===========================
def get_lane_id(x, y):
    point = (x, y)
    for i, lane in enumerate(lanes):
        if cv2.pointPolygonTest(lane, point, False) >= 0:
            return i + 1
    return -1


# ===========================
# CHECK VIOLATION WITH TIMER
# ===========================
def check_violation_with_timer(track_data, current_lane, vehicle_type, current_time, track_id, frame_num):
    if current_lane == -1:
        if track_data['violation_start_time'] is not None:
            print(f" Frame {frame_num}: ID {track_id} left violation lane - RESET")
        track_data['violation_start_time'] = None
        track_data['current_violation_duration'] = 0
        return False, False, "", 0

    is_in_violation = False
    violation_msg = ""

    vehicle_type_lower = vehicle_type.lower()

    if vehicle_type_lower in ["motorbike", "motorcycle", "moto"]:
        if current_lane == 3:
            is_in_violation = True
            violation_msg = "MOTORCYCLE IN CAR LANE"
            if track_data['violation_start_time'] is None:
                print(f" Frame {frame_num}: ID {track_id} ({vehicle_type}) ENTERED LANE 3 - START COUNTING!")

    elif vehicle_type_lower in ["car", "truck", "bus"]:
        if current_lane == 1:
            is_in_violation = True
            violation_msg = "CAR IN MOTORCYCLE LANE"
            if track_data['violation_start_time'] is None:
                print(f" Frame {frame_num}: ID {track_id} ({vehicle_type}) ENTERED LANE 1 - START COUNTING!")

    if is_in_violation:
        if track_data['violation_start_time'] is None:
            track_data['violation_start_time'] = current_time
            track_data['current_violation_duration'] = 0
            return True, False, violation_msg, 0
        else:
            violation_duration = current_time - track_data['violation_start_time']
            track_data['current_violation_duration'] = violation_duration

            if frame_num % 15 == 0:
                print(f"  ID {track_id}: Violating {violation_duration:.2f}s (need 2.0s)")

            if violation_duration >= 2.0:
                print(f" Frame {frame_num}: ID {track_id} VIOLATION CONFIRMED! ({violation_duration:.2f}s)")
                return False, True, violation_msg, violation_duration
            else:
                return True, False, violation_msg, violation_duration
    else:
        if track_data['violation_start_time'] is not None:
            print(f" Frame {frame_num}: ID {track_id} no longer violating - RESET")
        track_data['violation_start_time'] = None
        track_data['current_violation_duration'] = 0
        return False, False, "", 0


# ===========================
# SAVE VIOLATION IMAGES
# ===========================
def save_violation_images(original_frame, track_id, vehicle_type, violation_type,
                          box_coords, lane_info, duration, current_time, frame_num):
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S_%f")[:-3]
    x1, y1, x2, y2 = box_coords

    track_data = vehicle_tracks[track_id]
    if 'save_count' not in track_data:
        track_data['save_count'] = 0

    track_data['save_count'] += 1

    base_filename = f"{vehicle_type}_ID{track_id}_{timestamp}"

    # ===== 1. SMALL IMAGE: CROP VEHICLE ONLY =====
    padding = 5
    crop_x1 = max(0, x1 - padding)
    crop_y1 = max(0, y1 - padding)
    crop_x2 = min(original_frame.shape[1], x2 + padding)
    crop_y2 = min(original_frame.shape[0], y2 + padding)

    cropped_vehicle = original_frame[crop_y1:crop_y2, crop_x1:crop_x2].copy()

    crop_filename = os.path.join(VIOLATION_FOLDERS['crop'], f"{base_filename}_CROP.jpg")
    cv2.imwrite(crop_filename, cropped_vehicle)

    # ===== 2. LARGE IMAGE: FULL SCENE WITH HIGHLIGHT =====
    full_scene = original_frame.copy()

    cv2.rectangle(full_scene, (x1, y1), (x2, y2), (0, 0, 255), 6)

    center_x = (x1 + x2) // 2
    center_y = (y1 + y2) // 2

    cv2.circle(full_scene, (center_x, center_y), 10, (0, 0, 255), -1)
    cv2.circle(full_scene, (center_x, center_y), 12, (255, 255, 255), 2)

    text_info = f"VIOLATION: {violation_type}"
    text_font = cv2.FONT_HERSHEY_SIMPLEX
    text_scale = 0.8
    text_thickness = 2

    (text_width, text_height), _ = cv2.getTextSize(text_info, text_font, text_scale, text_thickness)

    text_x = 20
    text_y = full_scene.shape[0] - 40

    text_bg = full_scene.copy()
    cv2.rectangle(text_bg,
                  (text_x - 10, text_y - text_height - 10),
                  (text_x + text_width + 10, text_y + 10),
                  (0, 0, 0), -1)

    cv2.addWeighted(text_bg, 0.6, full_scene, 0.4, 0, full_scene)

    cv2.putText(full_scene, text_info, (text_x, text_y),
                text_font, text_scale, (255, 255, 255), text_thickness)

    detail_text = f"ID:{track_id} | {vehicle_type} | {lane_info}"
    cv2.putText(full_scene, detail_text, (text_x, text_y + 30),
                cv2.FONT_HERSHEY_SIMPLEX, 0.6, (200, 200, 200), 1)

    time_text = f"Time: {current_time:.1f}s | Frame: {frame_num}"
    cv2.putText(full_scene, time_text, (text_x, text_y + 60),
                cv2.FONT_HERSHEY_SIMPLEX, 0.5, (180, 180, 180), 1)

    full_filename = os.path.join(VIOLATION_FOLDERS['full'], f"{base_filename}_FULL.jpg")
    cv2.imwrite(full_scene, full_filename)

    print(f" Saved violation images ID {track_id}:")
    print(f"     Small (crop): {os.path.basename(crop_filename)}")
    print(f"   ️  Large (full): {os.path.basename(full_filename)}")

    return True


# ===========================
# MAIN LOOP
# ===========================
print("\n STARTING VIDEO PROCESSING...")
print("=" * 80)

start_time = time.time()
processed_frames = 0

while True:
    ret, frame = cap.read()
    if not ret:
        break

    frame = cv2.resize(frame, (frame_width, frame_height))
    frame_count += 1
    processed_frames += 1
    current_time = frame_count / fps

    original_frame = frame.copy()

    frame_display = frame.copy()

    # Draw lanes WITHOUT any text labels
    draw_beautiful_lanes(frame_display, lanes, lane_colors, alpha=0.25)

    results = model.track(frame_display, persist=True, tracker="bytetrack.yaml", device=device)[0]

    tracked_count = 0

    if results.boxes.id is not None:
        boxes = results.boxes.xyxy.cpu().numpy()
        track_ids = results.boxes.id.cpu().numpy().astype(int)
        classes = results.boxes.cls.cpu().numpy().astype(int)

        tracked_count = len(track_ids)

        for box, track_id, cls in zip(boxes, track_ids, classes):
            x1, y1, x2, y2 = map(int, box)
            vehicle_type = model.names[cls]

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

            is_counting, is_final_violation, violation_msg, duration = check_violation_with_timer(
                track, current_lane, vehicle_type, current_time, track_id, frame_count
            )

            # Determine color based on status
            color = (0, 255, 0)  # Green = OK

            if is_counting:
                color = (0, 165, 255)  # Orange = Counting

            elif is_final_violation:
                color = (0, 0, 255)  # Red = Violation

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
                    print(f" VIOLATION LOGGED #{len(violations)}: Track {track_id}")

                    if not track.get('violation_saved', False):
                        lane_info = f"Lane {current_lane}"
                        if current_lane == 1:
                            lane_info += " (MOTORCYCLE)"
                        elif current_lane == 2:
                            lane_info += " (MIXED)"
                        elif current_lane == 3:
                            lane_info += " (CAR)"

                        save_success = save_violation_images(
                            original_frame,
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

            if not is_counting and not is_final_violation:
                track['violation_logged'] = False
                track['violation_saved'] = False

            # Draw bounding box and center point ONLY - NO TEXT on video
            cv2.rectangle(frame_display, (x1, y1), (x2, y2), color, 3)
            cv2.circle(frame_display, (cx, cy), 6, color, -1)

    # Calculate FPS for console only
    elapsed_time = time.time() - start_time
    current_fps = processed_frames / elapsed_time if elapsed_time > 0 else 0

    # NO INFO PANEL - Just write clean frame to video
    video_writer.write(frame_display)

    # Progress update
    if frame_count % 100 == 0:
        progress = (frame_count / total_frames * 100) if total_frames > 0 else 0
        print(
            f"[PROGRESS] {frame_count}/{total_frames} frames ({progress:.1f}%) - Violations: {len(violations)} - FPS: {current_fps:.1f}")

    cv2.imshow("Lane Violation Detection", frame_display)

    key = cv2.waitKey(1) & 0xFF
    if key == 27:  # ESC
        print("[INFO] Stopped by user")
        break
    elif key == ord('p'):
        print("  Paused - Press any key to continue...")
        cv2.waitKey(0)

# ===========================
# CLEANUP AND REPORT
# ===========================
cap.release()
video_writer.release()
cv2.destroyAllWindows()

total_time = time.time() - start_time
print(f"\n  Total processing time: {total_time:.1f}s")
print(f" Average processing speed: {processed_frames / total_time:.1f} FPS")

crop_count = len([f for f in os.listdir(VIOLATION_FOLDERS['crop']) if f.endswith(('.jpg', '.png', '.jpeg'))])
full_count = len([f for f in os.listdir(VIOLATION_FOLDERS['full']) if f.endswith(('.jpg', '.png', '.jpeg'))])

print("\n" + "=" * 80)
print("LANE VIOLATION REPORT")
print("=" * 80)
print(f" Image folder: {base_violation_dir}")
print(f"   - crop/ (small - vehicle only): {crop_count} images")
print(f"   - full/ (large - full scene): {full_count} images")
print(f"Output video: {output_video_path}")

print("\n" + "=" * 80)
print("VIOLATION DETAILS:")
print("=" * 80)
for i, v in enumerate(violations, 1):
    print(f"\n{i}. Frame {v['frame']} (t={v['time']:.1f}s):")
    print(f"   - Track ID: {v['track_id']}")
    print(f"   - Vehicle: {v['type']}")
    print(f"   - Violation: {v['violation']}")
    print(f"   - Duration: {v['violation_duration']:.1f} seconds")

print(f"\n Total violations (>2s): {len(violations)}")
print("=" * 80)

# Save report
report_filename = os.path.join(base_violation_dir, f"violation_report_{time.strftime('%Y%m%d_%H%M%S')}.txt")
with open(report_filename, 'w', encoding='utf-8') as f:
    f.write("=" * 80 + "\n")
    f.write("LANE VIOLATION REPORT (VIOLATIONS > 2 SECONDS)\n")
    f.write("=" * 80 + "\n")
    f.write(f"Video: {video_path}\n")
    f.write(f"Analysis time: {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
    f.write(f"Total frames: {frame_count}\n")
    f.write(f"Video FPS: {fps:.2f}\n")
    f.write(f"Video duration: {frame_count / fps:.1f} seconds\n")
    f.write(f"Processing time: {total_time:.1f} seconds\n")
    f.write(f"Processing speed: {processed_frames / total_time:.1f} FPS\n")
    f.write(f"Total violations: {len(violations)}\n")
    f.write(f"Output video: {output_video_path}\n\n")

    f.write(f"IMAGE FOLDERS:\n")
    f.write(f"  - crop/ (small - vehicle only): {crop_count} images\n")
    f.write(f"  - full/ (large - full scene): {full_count} images\n\n")

    for i, v in enumerate(violations, 1):
        f.write(f"{i}. Frame {v['frame']} (t={v['time']:.1f}s):\n")
        f.write(f"   - Track ID: {v['track_id']}\n")
        f.write(f"   - Vehicle: {v['type']}\n")
        f.write(f"   - Violation: {v['violation']}\n")
        f.write(f"   - Duration: {v['violation_duration']:.1f} seconds\n\n")

    f.write("=" * 80 + "\n")

print(f"\n Detailed report saved to: {report_filename}")
print("\n ANALYSIS COMPLETE!")
print(f" Total violations: {len(violations)}")
print(f"Total images saved: {crop_count} (crop) + {full_count} (full)")
print(f" Video output: {os.path.abspath(output_video_path)}")
print(f" Open violation folder: {os.path.abspath(base_violation_dir)}")
print("\n CONTROLS:")
print("   ESC : Exit")
print("   P   : Pause")
