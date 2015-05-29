Messager = {

    flash: function(message) {
        $('#message').text(message);
        $('#message-wrapper').show();
    },

    hideFlash: function() {
        $('#message-wrapper').hide();
        $('#message').text('');
    },

    info: function(message) {
        $('#message').text(message);
        $('#message-wrapper').show();
        setTimeout("$('#message-wrapper').hide();", 1000);
    }

};

Request = {

    request: function(method, url, data, success) {
        Messager.flash('Делаем запрос...');
        $.ajax({
            url: url,
            dataType: "json",
            type: method,
            data: data
        }).done(function(data) {
            Messager.hideFlash();
            success(data);
        }).fail(function(e) {
            Messager.hideFlash();
            var error = 'неизвестная ошибка, код ' + e.status;
            if (e.status == 200) {
                error = 'ответ не в формате JSON';
            } else if (e.status == 404) {
                error = 'контроллер не найден';
            } else if (e.status == 500) {
                error = 'внутренняя ошибка сервера';
            }
            Messager.info('Ошибка во время запроса: ' + error);
        });
    }

};
