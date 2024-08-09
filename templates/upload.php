<!doctype html>
<html lang="en">
<head>
    <title>Spirit Transcriber</title>
    <!-- <style>
        #spinner {
            border: 16px solid #f3f3f3;
            border-top: 16px solid #3498db;
            border-radius: 50%;
            width: 120px;
            height: 120px;
            animation: spin 2s linear infinite;
            display: none;
            margin: auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        #status {
            font-size: 20px;
            text-align: center;
            margin-top: 20px;
        }
        #transcript-link {
            font-size: 20px;
            text-align: center;
            margin-top: 20px;
            display: none;
        }
    </style> -->
</head>
<body>
    <h1>Transcribe a file</h1>
    <h4>Select file with "Choose File".<br />They can be movies or audio files</h4>
    <form id="upload-form" enctype="multipart/form-data">
        <input type="file" name="file" id="file-input" required>
        <br/><br/>
        <button type="submit" id="submit-button" disabled>Click To Upload!</button>
    </form>
    <div id="spinner"></div>
    <div id="status"></div>
    <div id="transcript-link"><a id="transcript-url" href="#" target="_blank">Download Transcript</a></div>

    <script>
        jQuery(document).ready(function($) {
            const form = $('#upload-form');
            const fileInput = $('#file-input');
            const submitButton = $('#submit-button');

            // Enable submit button only when a file is selected
            fileInput.on('change', function() {
                if (fileInput.val()) {
                    submitButton.prop('disabled', false);
                } else {
                    submitButton.prop('disabled', true);
                }
            });

            form.on('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const spinner = $('#spinner');
                const status = $('#status');
                const transcriptLink = $('#transcript-link');
                const transcriptUrl = $('#transcript-url');

                spinner.show();
                status.text('File uploading...\nPlease be patient, it may take a while...');
                transcriptLink.hide();

                try {
                    const response = await $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(result) {
                            spinner.hide();
                            if (result.success) {
                                status.text('Success!');
                                transcriptUrl.attr('href', result.data.transcript_url);
                                transcriptLink.show();
                            } else {
                                status.text('Error during processing');
                            }
                        },
                        error: function() {
                            spinner.hide();
                            status.text('Network or server error');
                        }
                    });
                } catch (error) {
                    spinner.hide();
                    status.text('Network or server error');
                }
            });
        });
    </script>
</body>
</html>
