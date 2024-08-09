<?php
/*
Plugin Name: Spirit Transcriber
Description: A plugin to transcribe audio and video files using AssemblyAI.
Version: 1.0
Author: Your Name
*/

require 'vendor/autoload.php';

use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Mp3;

defined('ABSPATH') or die('No script kiddies please!');

function spirit_transcriber_enqueue_scripts() {
    wp_enqueue_style('spirit-transcriber-style', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('spirit-transcriber-script', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'spirit_transcriber_enqueue_scripts');

function spirit_transcriber_shortcode() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'templates/upload.php';
    return ob_get_clean();
}
add_shortcode('spirit_transcriber', 'spirit_transcriber_shortcode');

function spirit_transcriber_upload() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
        $file = $_FILES['file'];
        $upload_dir = wp_upload_dir();
        $output_folder = $upload_dir['basedir'] . '/spirit-transcriber-output';

        if (!file_exists($output_folder)) {
            mkdir($output_folder, 0755, true);
        }

        $file_path = $output_folder . '/' . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $file_path);

        // Check if the file is a video and convert it to audio if necessary
        $audio_path = $file_path;
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), ['mp4', 'mov', 'avi', 'mkv'])) {
            $ffmpeg = FFMpeg::create();
            $video = $ffmpeg->open($file_path);
            $audio_path = $output_folder . '/' . basename($file['name'], '.' . $extension) . '.mp3';

            // Convert video to MP3 audio
            $video->save(new Mp3(), $audio_path);
        }

        // Make the audio file publicly accessible
        $public_audio_url = $upload_dir['baseurl'] . '/spirit-transcriber-output/' . basename($audio_path);

        // Call AssemblyAI API with the public audio URL
        $api_key = constant('AAI_API_KEY');
        $data = array(
            'audio_url' => $public_audio_url,
            'speaker_labels' => true,
            'timestamps' => true
        );

        $options = array(
            'http' => array(
                'header'  => "Content-Type: application/json\r\nAuthorization: $api_key\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents('https://api.assemblyai.com/v2/transcript', false, $context);
        if ($result === FALSE) {
            wp_send_json_error();
        }

        $response = json_decode($result, true);
        $transcript_id = $response['id'];

        // Polling to check the status of the transcription
        do {
            sleep(5);
            $result = file_get_contents("https://api.assemblyai.com/v2/transcript/$transcript_id", false, $context);
            $response = json_decode($result, true);
        } while ($response['status'] !== 'completed');

        $transcript_path = $output_folder . '/' . basename($file['name'], "." . $extension) . '_transcript.txt';
        $transcript = '';
        foreach ($response['words'] as $word) {
            $start_time = $word['start'];
            $text = $word['text'];
            $transcript .= sprintf("[%s] %s\n", gmdate("H:i:s", $start_time / 1000), $text);
        }
        file_put_contents($transcript_path, $transcript);

        $transcript_url = $upload_dir['baseurl'] . '/spirit-transcriber-output/' . basename($transcript_path);
        wp_send_json_success(array('transcript_url' => $transcript_url));
    }
    wp_send_json_error();
}
add_action('wp_ajax_spirit_transcriber_upload', 'spirit_transcriber_upload');
add_action('wp_ajax_nopriv_spirit_transcriber_upload', 'spirit_transcriber_upload');
