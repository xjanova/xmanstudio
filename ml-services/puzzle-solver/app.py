"""
Puzzle CAPTCHA Gap Detection — ML Inference Service
====================================================
Flask microservice that receives before/after screenshots and returns
the predicted gap X coordinate.

Phase 1: OpenCV template matching (same logic as Android app, but tunable)
Phase 2: Replace with trained ML model (YOLO/CNN) when enough data exists

Usage:
    pip install -r requirements.txt
    python app.py                    # dev mode, port 5050
    gunicorn -w 2 -b 0.0.0.0:5050 app:app  # production

Laravel calls this at http://127.0.0.1:5050/predict
"""

import io
import logging
import os

import cv2
import numpy as np
from flask import Flask, jsonify, request
from PIL import Image

app = Flask(__name__)
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("puzzle-ml")

# Path to trained model (Phase 2 — when available)
MODEL_PATH = os.environ.get("PUZZLE_MODEL_PATH", "model/puzzle_gap_model.onnx")
ml_model = None


def load_ml_model():
    """Try to load trained ONNX/TFLite model if available."""
    global ml_model
    if os.path.exists(MODEL_PATH):
        try:
            ml_model = cv2.dnn.readNetFromONNX(MODEL_PATH)
            logger.info(f"ML model loaded from {MODEL_PATH}")
        except Exception as e:
            logger.warning(f"Failed to load ML model: {e}")
            ml_model = None


def read_image_from_upload(file_storage) -> np.ndarray:
    """Read uploaded file into OpenCV Mat (BGR)."""
    img_bytes = file_storage.read()
    img_pil = Image.open(io.BytesIO(img_bytes)).convert("RGB")
    img_np = np.array(img_pil)
    return cv2.cvtColor(img_np, cv2.COLOR_RGB2BGR)


def detect_gap_opencv(before_bgr, after_bgr, slider_x, slider_y, move_distance, track_width):
    """
    OpenCV template matching — same algorithm as Android detectGapSimple().
    Gap = same image content but brighter → TM_CCOEFF_NORMED handles this.
    """
    h, w = before_bgr.shape[:2]

    # Region of interest: puzzle area above slider
    top = max(0, slider_y - 500)
    bottom = max(top + 10, min(slider_y - 20, h))
    if bottom - top < 50:
        return None, 0.0

    gray_before = cv2.cvtColor(before_bgr[top:bottom, :], cv2.COLOR_BGR2GRAY)
    gray_after = cv2.cvtColor(after_bgr[top:bottom, :], cv2.COLOR_BGR2GRAY)

    # Step 1: Diff to find piece blob
    diff = cv2.absdiff(gray_before, gray_after)
    diff_blur = cv2.GaussianBlur(diff, (7, 7), 0)
    _, binary = cv2.threshold(diff_blur, 25, 255, cv2.THRESH_BINARY)

    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (5, 5))
    binary = cv2.morphologyEx(binary, cv2.MORPH_OPEN, kernel)
    binary = cv2.morphologyEx(binary, cv2.MORPH_CLOSE, kernel)

    # Step 2: Find largest blob
    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    best_rect = None
    best_area = 0.0
    for c in contours:
        area = cv2.contourArea(c)
        x, y, bw, bh = cv2.boundingRect(c)
        if area > best_area and 20 < bw < 300 and 20 < bh < 200:
            best_area = area
            best_rect = (x, y, bw, bh)

    if best_rect is None:
        logger.warning("No diff blob found")
        return None, 0.0

    bx, by, bw, bh = best_rect

    # Step 3: Estimate piece width, crop from RIGHT side
    estimated_piece_w = max(30, min(150, bw - move_distance))
    piece_h = bh

    pl = max(0, bx + bw - estimated_piece_w)
    pr = min(w, bx + bw)
    pt = max(0, by)
    pb = min(gray_after.shape[0], by + piece_h)

    if pr - pl < 20 or pb - pt < 20:
        return None, 0.0

    piece = gray_after[pt:pb, pl:pr]
    piece = cv2.GaussianBlur(piece, (3, 3), 0)

    # Step 4: Search image = BEFORE, zero out blob area
    search = cv2.GaussianBlur(gray_before.copy(), (3, 3), 0)
    zero_l = max(0, bx - 20)
    zero_r = min(w, bx + bw + 20)
    search[:, zero_l:zero_r] = 128  # neutral gray

    # Step 5: Template match
    if search.shape[1] < piece.shape[1] or search.shape[0] < piece.shape[0]:
        return None, 0.0

    result = cv2.matchTemplate(search, piece, cv2.TM_CCOEFF_NORMED)
    _, max_val, _, max_loc = cv2.minMaxLoc(result)

    gap_center_x = max_loc[0] + estimated_piece_w // 2

    logger.info(
        f"OpenCV detection: gap_x={gap_center_x} conf={max_val:.3f} "
        f"blob=({bx},{by},{bw},{bh}) pieceW={estimated_piece_w}"
    )

    if max_val < 0.05:
        return None, max_val

    return gap_center_x, float(max_val)


def detect_gap_ml(before_bgr, after_bgr, slider_x, slider_y, move_distance, track_width):
    """
    ML model inference (Phase 2).
    When trained model is available, use it for better accuracy.
    """
    if ml_model is None:
        return None, 0.0

    try:
        h, w = before_bgr.shape[:2]
        top = max(0, slider_y - 500)
        bottom = max(top + 10, min(slider_y - 20, h))

        # Prepare input: stack before + after as 6-channel input, resize to 416x416
        region_before = cv2.cvtColor(before_bgr[top:bottom, :], cv2.COLOR_BGR2RGB)
        region_after = cv2.cvtColor(after_bgr[top:bottom, :], cv2.COLOR_BGR2RGB)

        # Resize both to standard size
        target_h, target_w = 256, 512
        rb = cv2.resize(region_before, (target_w, target_h))
        ra = cv2.resize(region_after, (target_w, target_h))

        # Stack: 6-channel input (before_RGB + after_RGB)
        stacked = np.concatenate([rb, ra], axis=2).astype(np.float32) / 255.0
        blob = cv2.dnn.blobFromImage(stacked)

        ml_model.setInput(blob)
        output = ml_model.forward()

        # Assume model outputs [gap_x_normalized, confidence]
        gap_x_norm = float(output[0][0])
        confidence = float(output[0][1]) if output.shape[1] > 1 else 0.9

        # Denormalize: gap_x relative to full image width
        gap_x = int(gap_x_norm * w)

        logger.info(f"ML detection: gap_x={gap_x} conf={confidence:.3f}")
        return gap_x, confidence

    except Exception as e:
        logger.error(f"ML inference failed: {e}")
        return None, 0.0


@app.route("/predict", methods=["POST"])
def predict():
    """Main inference endpoint."""
    if "before" not in request.files or "after" not in request.files:
        return jsonify({"error": "Missing before/after images"}), 400

    slider_x = int(request.form.get("slider_x", 0))
    slider_y = int(request.form.get("slider_y", 0))
    move_distance = int(request.form.get("move_distance", 100))
    track_width = int(request.form.get("track_width", 1200))

    before_bgr = read_image_from_upload(request.files["before"])
    after_bgr = read_image_from_upload(request.files["after"])

    # Try ML model first (Phase 2), fallback to OpenCV (Phase 1)
    gap_x, confidence = detect_gap_ml(before_bgr, after_bgr, slider_x, slider_y, move_distance, track_width)

    if gap_x is None:
        gap_x, confidence = detect_gap_opencv(before_bgr, after_bgr, slider_x, slider_y, move_distance, track_width)
        source = "opencv"
    else:
        source = "ml_model"

    if gap_x is None:
        return jsonify({"gap_x": None, "confidence": 0.0, "source": "none"})

    return jsonify({
        "gap_x": gap_x,
        "confidence": confidence,
        "source": source,
    })


@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "status": "ok",
        "ml_model_loaded": ml_model is not None,
        "model_path": MODEL_PATH,
    })


@app.route("/reload-model", methods=["POST"])
def reload_model():
    """Hot-reload ML model after training."""
    load_ml_model()
    return jsonify({
        "success": True,
        "ml_model_loaded": ml_model is not None,
    })


@app.route("/train", methods=["POST"])
def train():
    """Trigger model training from labeled data."""
    import subprocess
    import json as json_mod

    api_url = request.form.get("api_url", "https://xman4289.com/api/v1/product/tping")
    epochs = int(request.form.get("epochs", 100))

    logger.info(f"Training triggered: api_url={api_url}, epochs={epochs}")

    try:
        result = subprocess.run(
            ["python", "train.py", "--api-url", api_url, "--epochs", str(epochs)],
            capture_output=True, text=True, timeout=600, cwd=os.path.dirname(os.path.abspath(__file__))
        )

        success = result.returncode == 0

        # Read training log if available
        log_path = os.path.join("model", "training_log.json")
        training_stats = {}
        if os.path.exists(log_path):
            with open(log_path) as f:
                training_stats = json_mod.load(f)

        if success:
            # Reload the newly trained model
            load_ml_model()

        return jsonify({
            "success": success,
            "ml_model_loaded": ml_model is not None,
            "stats": training_stats,
            "stdout": result.stdout[-2000:] if result.stdout else "",
            "stderr": result.stderr[-2000:] if result.stderr else "",
        })

    except subprocess.TimeoutExpired:
        return jsonify({"success": False, "error": "Training timed out (10min)"}), 504
    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 500


@app.route("/model-info", methods=["GET"])
def model_info():
    """Get current model info and training stats."""
    import json as json_mod

    log_path = os.path.join("model", "training_log.json")
    stats = {}
    if os.path.exists(log_path):
        with open(log_path) as f:
            stats = json_mod.load(f)

    model_exists = os.path.exists(MODEL_PATH)
    model_size = os.path.getsize(MODEL_PATH) if model_exists else 0

    return jsonify({
        "model_exists": model_exists,
        "model_loaded": ml_model is not None,
        "model_path": MODEL_PATH,
        "model_size_kb": round(model_size / 1024, 1),
        "training_stats": stats,
    })


if __name__ == "__main__":
    load_ml_model()
    port = int(os.environ.get("PORT", 5050))
    logger.info(f"Starting Puzzle ML service on port {port}")
    app.run(host="0.0.0.0", port=port, debug=False)
