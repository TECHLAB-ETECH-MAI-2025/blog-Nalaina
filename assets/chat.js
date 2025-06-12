import $ from 'jquery';
$(document).ready(function() {
    function loadMessages() {
        $.ajax({
            url: '/chat/messages',
            type: 'GET',
            success: function(response) {
                $('#chat-messages').html(response);
            },
            error: function() {
                console.error('Erreur lors du chargement des messages');
            }
        });
    }

    // Charger les messages toutes les 2 secondes
    setInterval(loadMessages, 2000);

    // Envoyer un message
    $('#send-message').click(function(e) {
        e.preventDefault();
        
        var content = $('#message-content').val();
        if (!content.trim()) return;

        $.ajax({
            url: '/chat/send',
            type: 'POST',
            data: {
                content: content,
                receiver: $('#receiver-id').val()
            },
            success: function() {
                $('#message-content').val('');
                // Forcer le rafraîchissement des messages
                loadMessages();
            },
            error: function() {
                alert('Erreur lors de l\'envoi du message');
            }
        });
    });
});