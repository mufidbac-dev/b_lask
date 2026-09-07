"""Receipt OCR worker: preprocessing is intentionally outside the Laravel request."""
import json
import sys
from pathlib import Path

import cv2
import numpy as np
import pytesseract


def preprocess(path: str) -> np.ndarray:
    image = cv2.imread(path)
    if image is None:
        raise ValueError("Unable to read receipt image")
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    denoised = cv2.fastNlMeansDenoising(gray, None, 10, 7, 21)
    threshold = cv2.adaptiveThreshold(
        denoised, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY, 31, 11
    )
    coords = np.column_stack(np.where(threshold < 255))
    if len(coords):
        angle = cv2.minAreaRect(coords)[-1]
        angle = -(90 + angle) if angle < -45 else -angle
        height, width = threshold.shape[:2]
        matrix = cv2.getRotationMatrix2D((width // 2, height // 2), angle, 1.0)
        threshold = cv2.warpAffine(
            threshold, matrix, (width, height),
            flags=cv2.INTER_CUBIC, borderMode=cv2.BORDER_REPLICATE
        )
    return threshold


def main() -> None:
    image = preprocess(sys.argv[1])
    config = "--psm 6"
    text = pytesseract.image_to_string(image, lang="ind+eng", config=config)
    data = pytesseract.image_to_data(image, lang="ind+eng", config=config, output_type=pytesseract.Output.DICT)
    confidences = [
        float(value) for value, word in zip(data["conf"], data["text"])
        if word.strip() and float(value) >= 0
    ]
    print(json.dumps({"text": text, "confidences": confidences}, ensure_ascii=False))


if __name__ == "__main__":
    main()
