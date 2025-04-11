<?php
class WooChatInit {

    public static function init() {
        // Register chatbot shortcode
        add_shortcode('woo_chatbot', [__CLASS__, 'render_chatbot']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);

        // Register AJAX handler for processing chat queries
        add_action('wp_ajax_process_chat_query', [__CLASS__, 'process_chat_query']);
        add_action('wp_ajax_nopriv_process_chat_query', [__CLASS__, 'process_chat_query']);
    }

    public static function render_chatbot() {
        // Render a simple chatbot interface
        return '<div id="woo-chatbot">
                    <textarea id="woo-chat-input" placeholder="Type your query..."></textarea>
                    <button id="woo-chat-submit">Send</button>
                    <div id="woo-chat-response"></div>
                </div>';
    }

    public static function enqueue_scripts() {
        // Enqueue JavaScript for chatbot functionality
        wp_enqueue_script('woo-chatbot-js', WOO_CHAT_PLUGIN_URL . 'assets/js/woo-chatbot.js', ['jquery'], '1.0', true);

        // Enqueue CSS for chatbot styling
        wp_enqueue_style('woo-chatbot-css', WOO_CHAT_PLUGIN_URL . 'assets/css/woo-chatbot.css', [], '1.0');

        // Localize the script with the AJAX URL
        wp_localize_script('woo-chatbot-js', 'wooChatbot', [
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    public static function process_chat_query() {
        // Check if query is provided
        if (!isset($_POST['query']) || empty($_POST['query'])) {
            wp_send_json_error('No query provided.');
        }

        $user_query = sanitize_text_field($_POST['query']);

        // Fetch all product data from WooCommerce database
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT post_title, meta_value AS price FROM {$wpdb->prefix}posts p
             JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
             WHERE p.post_type = 'product' AND pm.meta_key = '_price'"
        );

        if (empty($results)) {
            wp_send_json_error('No products found in the store.');
        }

        // Prepare data for LLM
        $product_data = [];
        foreach ($results as $product) {
            $product_data[] = $product->post_title . ' - $' . $product->price;
        }

        $prepared_query = $user_query . ' | Products: ' . implode(', ', $product_data);

        // Send the prepared query to Hugging Face LLM
        $response = self::send_to_llm($prepared_query);

        wp_send_json_success($response);
    }

    private static function send_to_llm($query) {
        // Path to the Python script
        $python_script = WOO_CHAT_PLUGIN_DIR . 'huggingface_bridge.py';

        // Escape the query to safely pass it as a command-line argument
        $escaped_query = escapeshellarg($query);

        // Use the Python executable from the virtual environment
        $python_executable = WOO_CHAT_PLUGIN_DIR . 'venv/bin/python3';

        // Execute the Python script and capture the output
        $command = "$python_executable $python_script $escaped_query 2>&1"; // Redirect stderr to stdout
        $output = shell_exec($command);

        // Log the raw output and command for debugging
        error_log('Command executed: ' . $command);
        error_log('Raw output from Python script: ' . $output);

        // Decode the JSON response from the Python script
        $response = json_decode($output, true);

        // Log the decoded response for debugging
        if ($response === null) {
            error_log('Decoded response is null. Raw output: ' . $output);
            return 'Error: Invalid response from the Python script. Check debug log for details.';
        }

        error_log('Decoded response from Python script: ' . print_r($response, true));

        // Check for errors in the Python script response
        if (isset($response['error'])) {
            return 'Error: ' . $response['error'];
        }

        // Ensure the response key exists and return it
        if (isset($response['response'])) {
            return $response['response'];
        }

        return 'Error: Unexpected response structure from the Python script.';
    }
}
