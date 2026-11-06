<?php
/*
Plugin Name: OLX Smart WebP Uploader
Plugin URI: https://github.com/osclass/
Description: Modern mobile-first drag-and-drop image uploader with in-browser HTML5 Canvas WebP compression and cover photo selection.
Version: 1.2.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_uploader
Plugin update URI:
*/

function olx_uploader_header() {
    $location = osc_get_osclass_location();
    $section = osc_get_osclass_section();

    // Target item add or edit pages
    if ($location == 'item' && in_array($section, array('item_add', 'item_edit', 'post'))) {
        echo '<style>
            /* Hide the default redundant UPPY uploader */
            .control-group.img.upload-photos { display: none !important; }

            /* Align the Photo Uploader with Sigma 30% label / 70% control layout */
            .olx-upload-control-group { clear: both; width: 100%; margin: 15px 0 20px 0; float: left; }
            .olx-upload-label { float: left; width: 30%; padding-top: 16px; padding-right: 15px; text-align: right; font-weight: bold; box-sizing: border-box; }
            .olx-upload-controls { float: left; width: 70%; box-sizing: border-box; }

            .olx-upload-container { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 22px 16px; text-align: center; transition: all 0.2s ease; cursor: pointer; }
            .olx-upload-container:hover, .olx-upload-container.dragover { border-color: #2563eb; background: #eff6ff; }
            .olx-upload-icon { width: 36px; height: 36px; color: #64748b; margin: 0 auto 6px; display: block; }
            .olx-upload-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
            .olx-upload-subtitle { font-size: 12px; color: #64748b; margin: 0; }
            .olx-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(95px, 1fr)); gap: 10px; margin-top: 12px; }
            .olx-preview-item { position: relative; width: 100%; aspect-ratio: 1; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; }
            .olx-preview-img { width: 100%; height: 100%; object-fit: cover; }
            .olx-preview-badge { position: absolute; top: 4px; left: 4px; background: #2563eb; color: #fff; font-size: 9px; font-weight: bold; padding: 1px 5px; border-radius: 3px; text-transform: uppercase; }
            .olx-preview-remove { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,0.65); color: #fff; border: none; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 11px; font-weight: bold; }
            .olx-preview-remove:hover { background: #ef4444; }
            .olx-compression-indicator { position: absolute; bottom: 4px; right: 4px; background: rgba(22, 163, 74, 0.9); color: #fff; font-size: 9px; font-weight: bold; padding: 1px 4px; border-radius: 3px; }

            /* Fix Description Textarea sizing and alignment */
            .control-group.descr .controls textarea {
                width: 100% !important;
                max-width: 100% !important;
                height: 180px !important;
                box-sizing: border-box !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 4px !important;
                padding: 10px !important;
                font-family: inherit !important;
                font-size: 14px !important;
                line-height: 1.5 !important;
                resize: vertical !important;
            }

            /* Fix Location header and Location boxes alignment */
            .box.location { clear: both; width: 100%; float: left; }
            .box.location h2 { padding-left: 30%; margin: 25px 0 15px 0; }
        </style>';
    }
}

function olx_uploader_body() {
    $max_images = osc_max_images_per_item() ?: 10;
    ?>
    <div class="olx-upload-control-group">
        <label class="olx-upload-label"><?php _e('Photos', 'sigma'); ?></label>
        <div class="olx-upload-controls">
            <div class="olx-upload-container" id="olx-dropzone">
                <svg class="olx-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                <div class="olx-upload-title">Drag & drop photos here or click to browse</div>
                <p class="olx-upload-subtitle">Smart WebP compression enabled &bull; Up to <?php echo $max_images; ?> photos</p>
                <input type="file" id="olx-file-input" multiple accept="image/*" style="display: none;" />
            </div>

            <div class="olx-preview-grid" id="olx-preview-grid"></div>
            <div id="olx-hidden-inputs"></div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var dropzone = document.getElementById("olx-dropzone");
        var fileInput = document.getElementById("olx-file-input");
        var previewGrid = document.getElementById("olx-preview-grid");
        var hiddenInputs = document.getElementById("olx-hidden-inputs");
        var maxImages = <?php echo $max_images; ?>;
        var uploadedCount = 0;

        if (!dropzone || !fileInput) return;

        dropzone.addEventListener("click", function() { fileInput.click(); });

        ["dragenter", "dragover"].forEach(function(evt) {
            dropzone.addEventListener(evt, function(e) { e.preventDefault(); dropzone.classList.add("dragover"); });
        });
        ["dragleave", "drop"].forEach(function(evt) {
            dropzone.addEventListener(evt, function(e) { e.preventDefault(); dropzone.classList.remove("dragover"); });
        });

        dropzone.addEventListener("drop", function(e) {
            if (e.dataTransfer && e.dataTransfer.files) {
                handleFiles(e.dataTransfer.files);
            }
        });

        fileInput.addEventListener("change", function() {
            if (fileInput.files) {
                handleFiles(fileInput.files);
            }
        });

        function compressToWebP(file, callback) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = new Image();
                img.onload = function() {
                    var maxDim = 1920;
                    var width = img.width;
                    var height = img.height;

                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    var canvas = document.createElement("canvas");
                    canvas.width = width;
                    canvas.height = height;
                    var ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob(function(blob) {
                        callback(blob, file.size, blob.size);
                    }, "image/webp", 0.82);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function handleFiles(files) {
            Array.from(files).forEach(function(file) {
                if (!file.type.match(/^image\//)) return;
                if (uploadedCount >= maxImages) {
                    alert("Maximum " + maxImages + " images allowed.");
                    return;
                }

                uploadedCount++;
                var currentIndex = uploadedCount;

                compressToWebP(file, function(webpBlob, origSize, newSize) {
                    var previewItem = document.createElement("div");
                    previewItem.className = "olx-preview-item";

                    var img = document.createElement("img");
                    img.className = "olx-preview-img";
                    img.src = URL.createObjectURL(webpBlob);
                    previewItem.appendChild(img);

                    if (currentIndex === 1) {
                        var badge = document.createElement("span");
                        badge.className = "olx-preview-badge";
                        badge.textContent = "Cover";
                        previewItem.appendChild(badge);
                    }

                    var removeBtn = document.createElement("button");
                    removeBtn.type = "button";
                    removeBtn.className = "olx-preview-remove";
                    removeBtn.innerHTML = "&times;";
                    removeBtn.onclick = function() {
                        previewItem.remove();
                        if (containerInput) containerInput.remove();
                        uploadedCount--;
                    };
                    previewItem.appendChild(removeBtn);

                    var savings = Math.round(((origSize - newSize) / origSize) * 100);
                    if (savings > 0) {
                        var info = document.createElement("div");
                        info.className = "olx-compression-indicator";
                        info.textContent = "-" + savings + "%";
                        previewItem.appendChild(info);
                    }

                    previewGrid.appendChild(previewItem);

                    var containerInput = document.createElement("input");
                    containerInput.type = "file";
                    containerInput.name = "photos[]";
                    containerInput.style.display = "none";

                    var dt = new DataTransfer();
                    var webpFile = new File([webpBlob], file.name.replace(/\.[^/.]+$/, "") + ".webp", { type: "image/webp" });
                    dt.items.add(webpFile);
                    containerInput.files = dt.files;

                    hiddenInputs.appendChild(containerInput);
                });
            });
        }
    });
    </script>
    <?php
}

// Hook registrations
osc_add_hook('header', 'olx_uploader_header');
osc_add_hook('item_publish_images', 'olx_uploader_body');
