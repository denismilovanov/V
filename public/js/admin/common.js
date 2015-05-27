Messager = {

    flash: function(message) {
        $('#message').text(message);
        $('#message-wrapper').show();
    },

    hideFlash: function() {
        $('#message-wrapper').hide();
        $('#message').text('');
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
        }).fail(function() {
            Messager.hideFlash();
            //Messager.alert(0, 'Ошибка во время запроса.');
        });
    }

};
