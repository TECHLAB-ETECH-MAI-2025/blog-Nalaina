import $ from 'jquery';
$(function() {
    const receiverId = $('#receiver-id').val();

    function loadMessages() {
        $.ajax({
            url: '/chat/messages?receiver=' + receiverId,
            type: 'GET',
            success: function(response) {
                $('#chat-messages').html(response);
                $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
            },
            error: function() {
                console.error('Erreur lors du chargement des messages');
            }
        });
    }

    // Charger les messages toutes les 2 secondes
    setInterval(loadMessages, 2000);

    // Envoyer un message
    $('#send-message').on('click', function(e) {
        e.preventDefault();
        
        var content = $('textarea[name$="[content]"]').val();
        if (!content || !content.trim()) return;


        $.ajax({
            url: '/chat/send',
            type: 'POST',
            data: {
                content: content,
                receiver: receiverId
            },
            success: function() {
                $('textarea[name$="[content]"]').val('');
                // Forcer le rafraîchissement des messages
                loadMessages();
            },
            error: function() {
                alert('Erreur lors de l\'envoi du message');
            }
        });
    });
});