/**
 * Banner Image Cropper - Facebook-style drag to reposition
 */
class BannerCropper {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            aspectRatio: options.aspectRatio || 16/9, // Default banner aspect ratio
            minWidth: options.minWidth || 800,
            minHeight: options.minHeight || 400,
            ...options
        };

        this.image = null;
        this.isDragging = false;
        this.startX = 0;
        this.startY = 0;
        this.imageX = 0;
        this.imageY = 0;
        this.imageScale = 1;

        this.init();
    }

    init() {
        this.createUI();
        this.attachEvents();
    }

    createUI() {
        this.container.innerHTML = `
            <div class="banner-cropper-wrapper" style="position: relative; overflow: hidden; background: #f3f4f6; border: 2px dashed #d1d5db; border-radius: 8px;">
                <div class="cropper-canvas" style="position: relative; width: 100%; cursor: move; display: flex; align-items: center; justify-content: center; min-height: 400px;">
                    <div class="cropper-placeholder" style="text-align: center; color: #9ca3af; padding: 40px;">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px; margin: 0 auto 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p>อัปโหลดรูปภาพแล้วลากเพื่อปรับตำแหน่ง</p>
                        <p style="font-size: 12px; margin-top: 8px;">คล้ายกับการอัปโหลดแบนเนอร์ใน Facebook</p>
                    </div>
                    <img class="cropper-image" style="position: absolute; top: 0; left: 0; display: none; user-select: none; -webkit-user-drag: none;" draggable="false">
                </div>

                <div class="cropper-controls" style="padding: 16px; background: white; border-top: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 200px;">
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">ขนาดแบนเนอร์</label>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" onclick="bannerCropper.setSize(1200, 630)" style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; font-size: 13px; cursor: pointer;">Facebook (1200×630)</button>
                                <button type="button" onclick="bannerCropper.setSize(1500, 500)" style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; font-size: 13px; cursor: pointer;">Twitter (1500×500)</button>
                                <button type="button" onclick="bannerCropper.setSize(1920, 400)" style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; font-size: 13px; cursor: pointer;">Wide (1920×400)</button>
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px;">ซูม</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button type="button" onclick="bannerCropper.zoom(-0.1)" style="width: 32px; height: 32px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; font-size: 18px;">−</button>
                                <span id="zoom-level" style="font-size: 13px; color: #6b7280; min-width: 50px; text-align: center;">100%</span>
                                <button type="button" onclick="bannerCropper.zoom(0.1)" style="width: 32px; height: 32px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; font-size: 18px;">+</button>
                            </div>
                        </div>

                        <div>
                            <button type="button" onclick="bannerCropper.reset()" style="padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; font-size: 14px;">รีเซ็ต</button>
                        </div>
                    </div>

                    <div style="margin-top: 12px; padding: 12px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 6px; font-size: 13px; color: #92400e;">
                        💡 <strong>คำแนะนำ:</strong> ลากรูปภาพเพื่อปรับตำแหน่งที่ต้องการแสดง (เหมือนแบนเนอร์ Facebook)
                    </div>
                </div>
            </div>

            <input type="hidden" name="crop_data" id="crop-data-input">
            <input type="hidden" name="display_width" id="display-width-input">
            <input type="hidden" name="display_height" id="display-height-input">
        `;

        this.canvas = this.container.querySelector('.cropper-canvas');
        this.imageEl = this.container.querySelector('.cropper-image');
        this.placeholder = this.container.querySelector('.cropper-placeholder');
        this.cropDataInput = document.getElementById('crop-data-input');
        this.displayWidthInput = document.getElementById('display-width-input');
        this.displayHeightInput = document.getElementById('display-height-input');
    }

    attachEvents() {
        // Mouse events
        this.canvas.addEventListener('mousedown', this.startDrag.bind(this));
        document.addEventListener('mousemove', this.drag.bind(this));
        document.addEventListener('mouseup', this.stopDrag.bind(this));

        // Touch events
        this.canvas.addEventListener('touchstart', this.startDrag.bind(this));
        document.addEventListener('touchmove', this.drag.bind(this));
        document.addEventListener('touchend', this.stopDrag.bind(this));
    }

    loadImage(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                this.image = img;
                this.imageEl.src = e.target.result;
                this.placeholder.style.display = 'none';
                this.imageEl.style.display = 'block';
                this.fitImage();
                this.updateCropData();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    fitImage() {
        const canvasWidth = this.canvas.offsetWidth;
        const canvasHeight = Math.round(canvasWidth / this.options.aspectRatio);

        // Set canvas height
        this.canvas.style.height = canvasHeight + 'px';

        // Calculate scale to cover the canvas
        const scaleX = canvasWidth / this.image.width;
        const scaleY = canvasHeight / this.image.height;
        this.imageScale = Math.max(scaleX, scaleY);

        const newWidth = this.image.width * this.imageScale;
        const newHeight = this.image.height * this.imageScale;

        // Center the image
        this.imageX = (canvasWidth - newWidth) / 2;
        this.imageY = (canvasHeight - newHeight) / 2;

        this.updateImagePosition();
    }

    updateImagePosition() {
        const newWidth = this.image.width * this.imageScale;
        const newHeight = this.image.height * this.imageScale;

        this.imageEl.style.width = newWidth + 'px';
        this.imageEl.style.height = newHeight + 'px';
        this.imageEl.style.left = this.imageX + 'px';
        this.imageEl.style.top = this.imageY + 'px';

        document.getElementById('zoom-level').textContent = Math.round(this.imageScale * 100) + '%';
    }

    startDrag(e) {
        if (!this.image) return;

        e.preventDefault();
        this.isDragging = true;

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        this.startX = clientX - this.imageX;
        this.startY = clientY - this.imageY;

        this.canvas.style.cursor = 'grabbing';
    }

    drag(e) {
        if (!this.isDragging || !this.image) return;

        e.preventDefault();

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const newX = clientX - this.startX;
        const newY = clientY - this.startY;

        const canvasWidth = this.canvas.offsetWidth;
        const canvasHeight = this.canvas.offsetHeight;
        const imageWidth = this.image.width * this.imageScale;
        const imageHeight = this.image.height * this.imageScale;

        // Constrain dragging within bounds
        this.imageX = Math.min(0, Math.max(newX, canvasWidth - imageWidth));
        this.imageY = Math.min(0, Math.max(newY, canvasHeight - imageHeight));

        this.updateImagePosition();
        this.updateCropData();
    }

    stopDrag() {
        if (this.isDragging) {
            this.isDragging = false;
            this.canvas.style.cursor = 'move';
        }
    }

    zoom(delta) {
        if (!this.image) return;

        const oldScale = this.imageScale;
        this.imageScale = Math.max(0.5, Math.min(3, this.imageScale + delta));

        const canvasWidth = this.canvas.offsetWidth;
        const canvasHeight = this.canvas.offsetHeight;

        // Adjust position to keep image centered when zooming
        const scaleDiff = this.imageScale / oldScale;
        this.imageX = (this.imageX - canvasWidth / 2) * scaleDiff + canvasWidth / 2;
        this.imageY = (this.imageY - canvasHeight / 2) * scaleDiff + canvasHeight / 2;

        // Constrain position
        const imageWidth = this.image.width * this.imageScale;
        const imageHeight = this.image.height * this.imageScale;
        this.imageX = Math.min(0, Math.max(this.imageX, canvasWidth - imageWidth));
        this.imageY = Math.min(0, Math.max(this.imageY, canvasHeight - imageHeight));

        this.updateImagePosition();
        this.updateCropData();
    }

    setSize(width, height) {
        this.options.aspectRatio = width / height;
        if (this.image) {
            this.fitImage();
            this.updateCropData();
        }

        // Update display size inputs
        this.displayWidthInput.value = width;
        this.displayHeightInput.value = height;
    }

    reset() {
        if (this.image) {
            this.fitImage();
            this.updateCropData();
        }
    }

    updateCropData() {
        if (!this.image) return;

        const canvasWidth = this.canvas.offsetWidth;
        const canvasHeight = this.canvas.offsetHeight;

        const cropData = {
            x: -this.imageX / this.imageScale,
            y: -this.imageY / this.imageScale,
            width: canvasWidth / this.imageScale,
            height: canvasHeight / this.imageScale,
            scale: this.imageScale,
            displayWidth: canvasWidth,
            displayHeight: canvasHeight
        };

        this.cropDataInput.value = JSON.stringify(cropData);

        // Set default display size if not set
        if (!this.displayWidthInput.value) {
            this.displayWidthInput.value = 1200;
            this.displayHeightInput.value = 630;
        }
    }

    loadExistingCrop(imageUrl, cropData) {
        const img = new Image();
        img.onload = () => {
            this.image = img;
            this.imageEl.src = imageUrl;
            this.placeholder.style.display = 'none';
            this.imageEl.style.display = 'block';

            if (cropData) {
                this.imageScale = cropData.scale || 1;
                this.imageX = -cropData.x * this.imageScale;
                this.imageY = -cropData.y * this.imageScale;

                const canvasHeight = Math.round(this.canvas.offsetWidth / this.options.aspectRatio);
                this.canvas.style.height = canvasHeight + 'px';

                this.updateImagePosition();
            } else {
                this.fitImage();
            }

            this.updateCropData();
        };
        img.src = imageUrl;
    }
}

// Global instance
let bannerCropper = null;
