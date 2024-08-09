jQuery(document).ready(function($) {
    const form = $('#upload-form');
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
            const transcriber = $('#transcriber').val();
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
