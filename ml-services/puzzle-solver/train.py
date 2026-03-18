"""
Puzzle Gap Detection — Model Training Script
=============================================
Downloads human-labeled data from the Laravel API,
trains a CNN regression model to predict gap_x from
before/after puzzle screenshots, exports to ONNX.

Usage:
    python train.py --api-url https://xman4289.com/api/v1/product/tping
    python train.py --data-dir ./training_data   # offline mode
"""

import argparse
import json
import logging
import os
import sys

import cv2
import numpy as np
import requests
from PIL import Image

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger("train")

# Model config
INPUT_W = 256
INPUT_H = 128
MODEL_DIR = "model"
MODEL_PATH = os.path.join(MODEL_DIR, "puzzle_gap_model.onnx")
CHECKPOINT_PATH = os.path.join(MODEL_DIR, "checkpoint.pth")
TRAINING_LOG = os.path.join(MODEL_DIR, "training_log.json")


def download_training_data(api_url, output_dir="training_data"):
    """Download labeled data from Laravel export API."""
    export_url = f"{api_url}/debug-images/export"
    logger.info(f"Downloading training data from {export_url}")

    resp = requests.get(export_url, timeout=30)
    resp.raise_for_status()
    data = resp.json()

    if not data.get("success"):
        logger.error("API returned error")
        return []

    records = data.get("data", [])
    logger.info(f"Got {len(records)} labeled records")

    os.makedirs(output_dir, exist_ok=True)
    samples = []

    for rec in records:
        gap_x = rec.get("actual_gap_x")
        detected_gap_x = rec.get("gap_x")
        track_width = rec.get("track_width") or 0
        image_urls = rec.get("image_urls", [])

        if gap_x is None or not image_urls:
            continue

        # Find before/search_masked images
        before_url = None
        search_url = None
        for url in image_urls:
            basename = url.split("/")[-1].lower()
            if "before" in basename or "search_masked" in basename:
                search_url = url
            if "diff_raw" in basename or "diff_binary" in basename:
                before_url = url  # Use diff as "before" context

        # Use first image if no specific match
        target_url = search_url or image_urls[0]

        # Download image
        try:
            img_resp = requests.get(target_url, timeout=15)
            img_resp.raise_for_status()
            img_path = os.path.join(output_dir, f"sample_{rec['id']}.png")
            with open(img_path, "wb") as f:
                f.write(img_resp.content)

            samples.append({
                "id": rec["id"],
                "image_path": img_path,
                "gap_x": gap_x,
                "detected_gap_x": detected_gap_x,
                "track_width": track_width,
                "success": rec.get("success"),
            })
        except Exception as e:
            logger.warning(f"Failed to download image for record {rec['id']}: {e}")

    # Save manifest
    manifest_path = os.path.join(output_dir, "manifest.json")
    with open(manifest_path, "w") as f:
        json.dump(samples, f, indent=2)

    logger.info(f"Downloaded {len(samples)} training samples to {output_dir}")
    return samples


def load_training_data(data_dir="training_data"):
    """Load previously downloaded training data."""
    manifest_path = os.path.join(data_dir, "manifest.json")
    if not os.path.exists(manifest_path):
        return []
    with open(manifest_path) as f:
        return json.load(f)


def prepare_dataset(samples):
    """Convert samples to numpy arrays for training."""
    images = []
    labels = []

    for s in samples:
        img_path = s["image_path"]
        gap_x = s["gap_x"]

        if not os.path.exists(img_path):
            continue

        img = cv2.imread(img_path)
        if img is None:
            continue

        h, w = img.shape[:2]
        if w == 0:
            continue

        # Normalize gap_x to [0, 1] relative to image width
        gap_x_norm = gap_x / w

        # Resize to standard input size
        img_resized = cv2.resize(img, (INPUT_W, INPUT_H))
        img_rgb = cv2.cvtColor(img_resized, cv2.COLOR_BGR2RGB)
        img_float = img_rgb.astype(np.float32) / 255.0

        # Channel-first for PyTorch: (3, H, W)
        img_chw = np.transpose(img_float, (2, 0, 1))

        images.append(img_chw)
        labels.append([gap_x_norm, w])  # normalized gap + original width

    if not images:
        return None, None

    return np.array(images, dtype=np.float32), np.array(labels, dtype=np.float32)


def build_model():
    """Build a simple CNN regression model."""
    import torch
    import torch.nn as nn

    class PuzzleGapNet(nn.Module):
        """CNN that predicts normalized gap_x from a puzzle image."""

        def __init__(self):
            super().__init__()
            # Simple CNN backbone
            self.features = nn.Sequential(
                nn.Conv2d(3, 32, 3, padding=1),
                nn.BatchNorm2d(32),
                nn.ReLU(),
                nn.MaxPool2d(2),  # 128x64

                nn.Conv2d(32, 64, 3, padding=1),
                nn.BatchNorm2d(64),
                nn.ReLU(),
                nn.MaxPool2d(2),  # 64x32

                nn.Conv2d(64, 128, 3, padding=1),
                nn.BatchNorm2d(128),
                nn.ReLU(),
                nn.MaxPool2d(2),  # 32x16

                nn.Conv2d(128, 128, 3, padding=1),
                nn.BatchNorm2d(128),
                nn.ReLU(),
                nn.AdaptiveAvgPool2d((4, 8)),  # 8x4
            )
            self.regressor = nn.Sequential(
                nn.Flatten(),
                nn.Linear(128 * 4 * 8, 256),
                nn.ReLU(),
                nn.Dropout(0.3),
                nn.Linear(256, 64),
                nn.ReLU(),
                nn.Linear(64, 2),  # [gap_x_norm, confidence]
                nn.Sigmoid(),  # both outputs in [0, 1]
            )

        def forward(self, x):
            x = self.features(x)
            x = self.regressor(x)
            return x

    return PuzzleGapNet()


def train_model(samples, epochs=100, lr=0.001):
    """Train the CNN model on labeled puzzle data."""
    import torch
    import torch.nn as nn
    import torch.optim as optim

    X, Y = prepare_dataset(samples)
    if X is None or len(X) < 3:
        logger.error(f"Not enough training data: {len(X) if X is not None else 0} samples (need 3+)")
        return None

    logger.info(f"Training on {len(X)} samples, image size: {INPUT_W}x{INPUT_H}")

    model = build_model()

    # Split train/val (80/20)
    n = len(X)
    idx = np.random.permutation(n)
    split = max(1, int(n * 0.8))
    train_idx, val_idx = idx[:split], idx[split:]

    X_train = torch.tensor(X[train_idx])
    Y_train = torch.tensor(Y[train_idx, 0:1])  # gap_x_norm only
    X_val = torch.tensor(X[val_idx]) if len(val_idx) > 0 else None
    Y_val = torch.tensor(Y[val_idx, 0:1]) if len(val_idx) > 0 else None

    optimizer = optim.Adam(model.parameters(), lr=lr)
    loss_fn = nn.MSELoss()
    scheduler = optim.lr_scheduler.ReduceLROnPlateau(optimizer, patience=10, factor=0.5)

    best_val_loss = float("inf")
    training_log = []

    for epoch in range(epochs):
        model.train()
        optimizer.zero_grad()
        pred = model(X_train)
        loss = loss_fn(pred[:, 0:1], Y_train)
        loss.backward()
        optimizer.step()

        # Validation
        val_loss = 0.0
        if X_val is not None and len(X_val) > 0:
            model.eval()
            with torch.no_grad():
                val_pred = model(X_val)
                val_loss = loss_fn(val_pred[:, 0:1], Y_val).item()
            scheduler.step(val_loss)

            if val_loss < best_val_loss:
                best_val_loss = val_loss
                os.makedirs(MODEL_DIR, exist_ok=True)
                torch.save(model.state_dict(), CHECKPOINT_PATH)

        if (epoch + 1) % 10 == 0 or epoch == 0:
            # Calculate pixel error on original scale
            avg_w = float(Y[train_idx, 1].mean())
            train_px_err = (loss.item() ** 0.5) * avg_w
            val_px_err = (val_loss ** 0.5) * avg_w if val_loss > 0 else 0

            logger.info(
                f"Epoch {epoch+1}/{epochs} — "
                f"train_loss={loss.item():.6f} ({train_px_err:.1f}px) "
                f"val_loss={val_loss:.6f} ({val_px_err:.1f}px)"
            )
            training_log.append({
                "epoch": epoch + 1,
                "train_loss": round(loss.item(), 6),
                "val_loss": round(val_loss, 6),
                "train_px_error": round(train_px_err, 1),
                "val_px_error": round(val_px_err, 1),
            })

    # Load best checkpoint
    if os.path.exists(CHECKPOINT_PATH):
        model.load_state_dict(torch.load(CHECKPOINT_PATH, weights_only=True))

    # Final evaluation
    model.eval()
    avg_w = float(Y[:, 1].mean())
    with torch.no_grad():
        all_pred = model(torch.tensor(X))
        all_err = torch.abs(all_pred[:, 0] - torch.tensor(Y[:, 0])) * avg_w
        avg_err = all_err.mean().item()
        within_20 = (all_err <= 20).float().mean().item() * 100

    result = {
        "samples": len(X),
        "epochs": epochs,
        "avg_error_px": round(avg_err, 1),
        "accuracy_within_20px": round(within_20, 1),
        "best_val_loss": round(best_val_loss, 6),
        "training_log": training_log,
    }

    # Save training log
    os.makedirs(MODEL_DIR, exist_ok=True)
    with open(TRAINING_LOG, "w") as f:
        json.dump(result, f, indent=2)

    logger.info(f"Training complete: avg_err={avg_err:.1f}px, accuracy={within_20:.1f}%")
    return model, result


def export_onnx(model):
    """Export PyTorch model to ONNX format."""
    import torch

    os.makedirs(MODEL_DIR, exist_ok=True)
    model.eval()

    dummy = torch.randn(1, 3, INPUT_H, INPUT_W)
    torch.onnx.export(
        model, dummy, MODEL_PATH,
        input_names=["input"],
        output_names=["output"],
        dynamic_axes={"input": {0: "batch"}, "output": {0: "batch"}},
        opset_version=11,
    )
    logger.info(f"ONNX model exported to {MODEL_PATH}")
    return MODEL_PATH


def main():
    parser = argparse.ArgumentParser(description="Train puzzle gap detection model")
    parser.add_argument("--api-url", default="https://xman4289.com/api/v1/product/tping")
    parser.add_argument("--data-dir", default="training_data")
    parser.add_argument("--epochs", type=int, default=100)
    parser.add_argument("--lr", type=float, default=0.001)
    parser.add_argument("--skip-download", action="store_true")
    args = parser.parse_args()

    # Step 1: Get data
    if args.skip_download:
        samples = load_training_data(args.data_dir)
    else:
        samples = download_training_data(args.api_url, args.data_dir)

    if len(samples) < 3:
        logger.error(f"Need at least 3 labeled samples, got {len(samples)}")
        sys.exit(1)

    # Step 2: Train
    result = train_model(samples, epochs=args.epochs, lr=args.lr)
    if result is None:
        sys.exit(1)

    model, stats = result

    # Step 3: Export ONNX
    onnx_path = export_onnx(model)
    logger.info(f"Done! Model: {onnx_path}")
    logger.info(f"Stats: {json.dumps(stats, indent=2)}")


if __name__ == "__main__":
    main()
