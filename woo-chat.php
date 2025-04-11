<?php
/*
Plugin Name: WooCommerce Chatbot with RAG
Description: A WooCommerce extension plugin that implements a chatbot using Retrieval-Augmented Generation (RAG).
Version: 1.0
Author: Omkarkulkarni7
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOO_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once WOO_CHAT_PLUGIN_DIR . 'includes/class-woo-chat-init.php';

// Initialize the plugin
add_action('plugins_loaded', ['WooChatInit', 'init']);