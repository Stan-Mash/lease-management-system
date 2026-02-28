"""
Remove 'TO LET', phone number, and LIFESTYLE/CAPITAL watermark from I LOVE NBO building photo.
Inpainting only (no cloning). Uses a narrow vertical strip so the fill blends at the edges.
Run: python scripts/remove_watermark_and_text.py
"""
import sys
from pathlib import Path

try:
    import cv2
    import numpy as np
except ImportError:
    print("Need opencv-python and numpy. Run: pip install opencv-python numpy")
    sys.exit(1)

CURSOR_ASSETS = Path(r"C:\Users\kiman\.cursor\projects\c-Users-kiman-Projects-chabrin-lease-system\assets")
SRC = CURSOR_ASSETS / "c__Users_kiman_AppData_Roaming_Cursor_User_workspaceStorage_2b2436cef1844faa92e02ea1195c25ec_images_image-7480bfbb-b2b0-45e5-8b8b-8e03a6f7dfde.png"
PROJECT_ROOT = Path(__file__).resolve().parent.parent
OUT = PROJECT_ROOT / "assets" / "nairobi_gtc_ilovenbo_clean.png"


def main():
    if not SRC.exists():
        print(f"Image not found: {SRC}")
        sys.exit(1)

    img = cv2.imread(str(SRC))
    if img is None:
        print("Failed to load image.")
        sys.exit(1)

    h, w = img.shape[:2]
    mask = np.zeros((h, w), dtype=np.uint8)

    # Vertical strip: TO LET + full phone number (must extend down to cover "0777")
    strip_w = int(0.10 * w)
    cx = 0.50 * w
    x1 = int(cx - strip_w // 2)
    x2 = x1 + strip_w
    y1 = int(0.21 * h)
    y2 = int(0.78 * h)  # extend down so "0777" is inside mask
    x1 = max(0, x1)
    x2 = min(w, x2)
    mask[y1:y2, x1:x2] = 255

    # Watermark bottom-right
    mask[int(0.86 * h) :, int(0.82 * w) :] = 255

    # Large radius so edges blend into building (less hard gray block)
    result = cv2.inpaint(img, mask, inpaintRadius=21, flags=cv2.INPAINT_TELEA)

    OUT.parent.mkdir(parents=True, exist_ok=True)
    cv2.imwrite(str(OUT), result)
    print(f"Saved cleaned image to: {OUT}")


if __name__ == "__main__":
    main()
