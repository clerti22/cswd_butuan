<?php

declare(strict_types=1);

require_once __DIR__ . '/scan_copy_helpers.php';

$token = (string) ($_GET['token'] ?? '');
$payload = verify_scan_copy_token($token);
$controlNumber = $payload ? normalize_scan_value((string) ($payload['client_control_number'] ?? '')) : '';
$program = $payload ? normalize_scan_value((string) ($payload['program'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Copy Upload</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f3f4f6; margin:0; padding:16px; color:#111827; }
        .card { max-width:520px; margin:0 auto; background:#fff; border-radius:12px; padding:18px; box-shadow:0 6px 18px rgba(0,0,0,.08); }
        .muted { color:#6b7280; font-size:.9rem; }
        .preview { width:100%; max-height:380px; object-fit:contain; border:1px solid #d1d5db; border-radius:10px; margin-top:12px; display:none; }
        .actions { display:flex; gap:10px; margin-top:14px; flex-wrap:wrap; }
        button, .btn-file { border:none; border-radius:8px; padding:10px 14px; font-size:.95rem; cursor:pointer; }
        .btn-primary { background:#1d4ed8; color:#fff; }
        .btn-secondary { background:#4b5563; color:#fff; }
        .btn-file { background:#059669; color:#fff; display:inline-block; }
        .btn-file input { display:none; }
        .message { margin-top:12px; font-size:.92rem; }
    </style>
</head>

<body>
    <div class="card">
        <h2>Upload Scan Copy</h2>
        <?php if (!$payload): ?>
            <p class="message">This upload session is invalid or already expired. Please generate a new QR code.</p>
        <?php else: ?>
            <p class="muted">Client: <strong><?php echo htmlspecialchars($controlNumber, ENT_QUOTES, 'UTF-8'); ?></strong> &middot; Program: <strong><?php echo htmlspecialchars($program, ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <p class="muted">Capture or select an image, review preview, then confirm upload.</p>
            <label class="btn-file">
                Capture / Choose Picture
                <input id="scanImageInput" type="file" accept="image/*" capture="environment">
            </label>
            <img id="previewImage" class="preview" alt="Captured scan preview">
            <div class="actions">
                <button id="retakeButton" class="btn-secondary" type="button" disabled>Retake Picture</button>
                <button id="confirmButton" class="btn-primary" type="button" disabled>Confirm Upload</button>
            </div>
            <p id="statusMessage" class="message" aria-live="polite"></p>
        <?php endif; ?>
    </div>

    <?php if ($payload): ?>
        <script>
            const token = <?php echo json_encode($token, JSON_UNESCAPED_SLASHES); ?>;
            const input = document.getElementById('scanImageInput');
            const preview = document.getElementById('previewImage');
            const retakeButton = document.getElementById('retakeButton');
            const confirmButton = document.getElementById('confirmButton');
            const statusMessage = document.getElementById('statusMessage');
            let selectedFile = null;

            function setStatus(text, isError = false) {
                statusMessage.textContent = text || '';
                statusMessage.style.color = isError ? '#b91c1c' : '#065f46';
            }

            input.addEventListener('change', () => {
                const [file] = input.files || [];
                selectedFile = file || null;

                if (!selectedFile) {
                    preview.style.display = 'none';
                    retakeButton.disabled = true;
                    confirmButton.disabled = true;
                    return;
                }

                const reader = new FileReader();
                reader.onload = (event) => {
                    preview.src = String(event.target?.result || '');
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(selectedFile);
                retakeButton.disabled = false;
                confirmButton.disabled = false;
                setStatus('');
            });

            retakeButton.addEventListener('click', () => {
                input.value = '';
                selectedFile = null;
                preview.removeAttribute('src');
                preview.style.display = 'none';
                retakeButton.disabled = true;
                confirmButton.disabled = true;
                setStatus('Please capture a new image.');
            });

            confirmButton.addEventListener('click', async () => {
                if (!selectedFile) {
                    setStatus('Please capture a picture first.', true);
                    return;
                }

                confirmButton.disabled = true;
                retakeButton.disabled = true;
                setStatus('Uploading scan copy...');

                try {
                    const formData = new FormData();
                    formData.append('token', token);
                    formData.append('scan_image', selectedFile);

                    const response = await fetch('upload_scan_copy.php', {
                        method: 'POST',
                        body: formData,
                    });

                    const payload = await response.json();
                    if (!response.ok || !payload.ok) {
                        throw new Error(payload.message || 'Upload failed.');
                    }

                    setStatus('Upload complete. You may now close this page.');
                } catch (error) {
                    setStatus(error.message || 'Unable to upload right now.', true);
                    confirmButton.disabled = false;
                    retakeButton.disabled = false;
                }
            });
        </script>
    <?php endif; ?>
</body>

</html>
