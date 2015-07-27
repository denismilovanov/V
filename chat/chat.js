var express = require('express');
var app = express();
var server = require('http').createServer(app);
var io = require('socket.io')(server);
var port = process.env.PORT || 5000;
var redis_socket_io = require('socket.io-redis');
var redis = require('redis');
var pg = require('pg');

server.listen(port, function () {
    console.log('Server listening at port %d', port);
});

app.use(express.static(__dirname + '/'));

redis_client = redis.createClient();

var client = new pg.Client({
    'port': process.env.DB_PORT || 5432,
    'user': process.env.DB_USER || 'vmeste',
    'password': process.env.DB_PASS || 'vmeste',
    'database': process.env.DB_NAME || 'vmeste'
});

client.connect(function(err) {
    console.log('DB ERR', err);
});

Chat = {
    NEED_AUTHORIZE: 2,
    SUCCESS: 1,
    ERROR: 0,

    sockets_info: {},
    users_ids_to_socket_ids: {},

    emit: function (socket, type, data) {
        if (socket) {
            console.log('EMIT:', type, socket.id, data);
            socket.emit(type, data);
        } else {
            console.log('TRYING TO SEND TO ABSENT SOCKET:', type, data)
        }
    },

    save_socket: function (socket, key, user_id) {
        console.log('SAVING SOCKET:', socket.id, key);
        Chat.sockets_info[socket.id] = {
            'key': key,
            'is_authorized': true,
            'socket': socket,
            'user_id': user_id
        };
        Chat.users_ids_to_socket_ids[user_id] = socket.id;
        redis_client.hset('users_ids_to_socket_ids', user_id, socket.id);
    },

    get_socket_info_by_socket_id: function(id) {
        var s = Chat.sockets_info[id];
        if (s) {
            console.log('GOT SOCKET:', s.socket.id, s.user_id, s.key)
        } else {
            console.log('THERE IS NO SOCKET WITH ID:', id)
        }
        return s;
    },

    get_socket_by_user_id: function(user_id) {
        var socket_id = Chat.users_ids_to_socket_ids[user_id];
        if (socket_id) {
            return Chat.get_socket_info_by_socket_id(socket_id).socket;
        }
        console.log('THERE IS NO SOCKET FOR USER WITH ID:', user_id)
        return null;
    },

    authorize: function(data, socket, on_authorize) {
        var key = data['key'];

        client.query('SELECT user_id FROM users_devices WHERE key = $1::varchar;', [key], function(err, result) {
            var user_id = null;
            try {
                user_id = result.rows[0].user_id;
                Chat.save_socket(socket, key, user_id);
            } catch (e) {
                ;
            }
            on_authorize(user_id);
        });
    },

    get_like: function(from_user_id, to_user_id, f) {
        client.query("SELECT 1 AS c, coalesce(is_blocked, false) AS is_blocked FROM public.likes WHERE user1_id = $1::int AND user2_id = $2::int LIMIT 1;",
            [to_user_id, from_user_id],
            function(err, result) {

                var is_blocked = true, is_liked = false;
                try {
                    is_blocked = result.rows[0].is_blocked
                    is_liked = true
                } catch (e) {
                    ;
                }

                f(is_liked, is_blocked);

            });
    },

    add_message: function(from_user_id, to_user_id, message, i, f) {
        client.query("SELECT public.add_message($1::int, $2::int, $3::varchar, $4::boolean) AS message_id",
            [from_user_id, to_user_id, message, i],
            function(err, result) {
                var message_id = null;
                try {
                    message_id = result.rows[0].message_id
                } catch (e) {
                    ;
                }
                f(message_id);
            });
    },

    save_message: function(from_user_id, to_user_id, message, on_save_message) {
        console.log('SAVE MESSAGE:', from_user_id, to_user_id, message);

        // check if like exists
        Chat.get_like(from_user_id, to_user_id, function(is_liked1, is_blocked1) {
            if (! is_liked1) {
                // there is no like
                on_save_message(message_id, destination_message_id, Chat.ERROR);
                return;
            }

            // check if counter like exists
            Chat.get_like(to_user_id, from_user_id, function(is_liked2, is_blocked2) {

                if (is_liked2) {

                    // add first message in pair
                    Chat.add_message(from_user_id, to_user_id, message, true, function(message_id) {

                        if (! is_blocked2) {

                            // add second message in pair
                            Chat.add_message(to_user_id, from_user_id, message, false, function(destination_message_id) {
                                // all conditions are met
                                on_save_message(message_id, destination_message_id, Chat.SUCCESS);
                            });

                        } else {

                            // there is no second message in pair
                            on_save_message(message_id, null, Chat.SUCCESS);

                        }

                    });

                } else {
                    // there is no mutual like
                    on_save_message(message_id, destination_message_id, Chat.ERROR);
                }
            });
        });

    },

    message: function(data, socket, on_save_message) {
        var key = data['key'];
        var user_id = data['user_id'];
        var message = data['message'];
        var destination_socket = Chat.get_socket_by_user_id(user_id);
        var s = Chat.get_socket_info_by_socket_id(socket.id);

        if (! s) {
            // send reauth signal
            on_save_message(null, null, null, null, Chat.NEED_AUTHORIZE);
            return;
        }

        var me_id = s['user_id']

        // save message to db
        Chat.save_message(me_id, user_id, message, function(message_id, destination_message_id, status) {
            // send ack and message in Chat.sockets_info
            on_save_message(destination_socket, message_id, destination_message_id, me_id, status);
        });
    },

    start_writing: function(data, socket, f) {
        var user_id = data['user_id'];
        var my_socket = Chat.get_socket_info_by_socket_id(socket.id);
        var to_socket = Chat.get_socket_by_user_id(user_id);

        if (! my_socket || ! to_socket) {
            return;
        }

        f(my_socket.user_id, to_socket);
    },

    stop_writing: function(data, socket, f) {
        var user_id = data['user_id'];
        var my_socket = Chat.get_socket_info_by_socket_id(socket.id);
        var to_socket = Chat.get_socket_by_user_id(user_id);

        if (! my_socket || ! to_socket) {
            return;
        }

        f(my_socket.user_id, to_socket);
    },

    disconnect: function(socket) {
        var s = Chat.get_socket_info_by_socket_id(socket.id);
        if (s) {
            console.log('DISCONNECT SOCKET:', socket.id);
            delete Chat.users_ids_to_socket_ids[s.user_id];
            delete Chat.sockets_info[socket.id];
        }
    }
}

io.adapter(redis_socket_io({ host: 'localhost', port: 6379 }));

io.on('connection', function (socket) {
    //console.log(socket.id)
    socket.on('authorize', function (data) {
        Chat.authorize(data, socket, function(user_id) {
            status = user_id != null ? Chat.SUCCESS : Chat.ERROR;
            Chat.emit(socket, 'authorize', {
                status: status
            });
        });
    });

    socket.on('message', function (data) {
        Chat.message(data, socket, function(destination_socket, message_id, destination_message_id, from_user_id, status) {
            Chat.emit(socket, 'message_ack', {
                id: data.id,
                message_id: message_id,
                status: status
            });
            if (destination_socket && destination_message_id) {
                Chat.emit(destination_socket, 'message', {
                    message: data['message'],
                    message_id: destination_message_id,
                    user_id: from_user_id
                });
            }
        });
    });

    socket.on('start_writing', function (data) {
        Chat.start_writing(data, socket, function(me_id, destination_socket) {
            if (destination_socket) {
                Chat.emit(destination_socket, 'start_writing', {
                    user_id: me_id
                });
            }
        });
    });

    socket.on('stop_writing', function (data) {
        Chat.stop_writing(data, socket, function(me_id, destination_socket) {
            if (destination_socket) {
                Chat.emit(destination_socket, 'stop_writing', {
                    user_id: me_id
                });
            }
        });
    });

    socket.on('disconnect', function () {
        Chat.disconnect(socket)
    });
});



