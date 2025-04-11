jQuery(document).ready(function ($) {
    $('#woo-chat-submit').on('click', function () {
        const userQuery = $('#woo-chat-input').val();

        if (!userQuery) {
            alert('Please enter a query.');
            return;
        }

        // Clear the input field
        $('#woo-chat-input').val('');

        // Show a loading message
        $('#woo-chat-response').html('<p>Loading...</p>');

        // Send the query to the server via AJAX
        $.ajax({
            url: wooChatbot.ajax_url,
            method: 'POST',
            data: {
                action: 'process_chat_query',
                query: userQuery
            },
            success: function (response) {
                $('#woo-chat-response').html('<p>' + response.data + '</p>');
            },
            error: function () {
                $('#woo-chat-response').html('<p>There was an error processing your request.</p>');
            }
        });
    });
});