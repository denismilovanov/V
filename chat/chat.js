var express = require('express');
var app = express();
var server = require('http').createServer(app);
var io = require('socket.io')(server);
var port = process.env.PORT || 5000;
var redis_socket_io = require('socket.io-redis');
var redis = require('redis');
var pg = require('pg');
var amqp = require('amqp');
var log4js = require('log4js');

server.listen(port, function () {
    Chat.logger.info('Server listening at port', port);
});

app.use(express.static(__dirname + '/'));

Chat = {
    env: process.env.ENV || 'dev',
    logger: log4js.getLogger(),

    NEED_AUTHORIZE: 2,
    SUCCESS: 1,
    ERROR: 0,

    sockets_info: {},

    redis_client: null,
    pg_client: null,
    rabbit_client: null,

    push_queue_name: 'push_messages',

    init: function() {
        Chat.redis_client = redis.createClient();

        Chat.pg_client = new pg.Client({
            'port': process.env.DB_PORT || 5432,
            'user': process.env.DB_USER || 'vmeste',
            'password': process.env.DB_PASS || 'vmeste',
            'database': process.env.DB_NAME || 'vmeste'
        });

        Chat.pg_client.connect(function(err) {
            if (! err) {
                Chat.logger.info('DB READY');
            } else {
                Chat.logger.info('DB ERR', err);
            }
        });

        Chat.rabbit_client = amqp.createConnection();

        Chat.rabbit_client.on('ready', function () {
            Chat.logger.info('RABBIT READY');
        });

        if (Chat.env == 'test') {
            Chat.push_queue_name = '__test_push_messages';
        }
    },

    pg_query: function(query, data, success, error) {
        error = error || function(err) {};
        Chat.pg_client.query(query, data, function(err, result) {
            if (err) {
                error(err);
            } else {
                success(result);
            }
        });
    },

    emit: function (socket, type, data) {
        if (socket) {
            Chat.logger.info('EMIT:', type, socket.id, data);
            socket.emit(type, data);
        } else {
            Chat.logger.info('TRYING TO SEND TO ABSENT SOCKET:', type, data)
        }
    },

    save_socket: function (socket, key, user_id) {
        Chat.logger.info('SAVING SOCKET:', socket.id, key);
        Chat.sockets_info[socket.id] = {
            'key': key,
            'is_authorized': true,
            'socket': socket,
            'user_id': user_id
        };
        Chat.redis_client.hset('users_ids_to_socket_ids', user_id, socket.id);
    },

    get_socket_info_by_socket_id: function(id) {
        var socket_info = Chat.sockets_info[id];
        if (socket_info) {
            Chat.logger.info('GOT SOCKET:', socket_info.socket.id, socket_info.user_id, socket_info.key)
        } else {
            Chat.logger.info('THERE IS NO SOCKET WITH ID:', id)
            return null;
        }
        return socket_info;
    },

    get_socket_by_user_id: function(user_id, on_get_socket) {
        Chat.redis_client.hget('users_ids_to_socket_ids', user_id, function(err, socket_id) {
            if (socket_id) {
                var socket_info = Chat.get_socket_info_by_socket_id(socket_id);
                on_get_socket(socket_info ? socket_info.socket : null);
                return;
            }
            Chat.logger.info('THERE IS NO SOCKET FOR USER WITH ID:', user_id)
            on_get_socket(null);
        });
    },

    authorize: function(data, socket, on_authorize) {
        var key = data['key'];

        Chat.pg_query('SELECT user_id FROM users_devices WHERE key = $1::varchar;', [key], function(result) {
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

    get_like: function(from_user_id, to_user_id, on_get_like) {
        Chat.pg_query("SELECT 1 AS c, coalesce(is_blocked, false) AS is_blocked FROM public.likes WHERE user1_id = $1::int AND user2_id = $2::int LIMIT 1;",
            [to_user_id, from_user_id],
            function(result) {

                var is_blocked = true, is_liked = false;
                try {
                    is_blocked = result.rows[0].is_blocked
                    is_liked = true
                } catch (e) {
                    ;
                }

                on_get_like(is_liked, is_blocked);

            });
    },

    add_message: function(from_user_id, to_user_id, message, i, on_add_message) {
        Chat.pg_query("SELECT public.add_message($1::int, $2::int, $3::varchar, $4::boolean) AS message_id",
            [from_user_id, to_user_id, message, i],
            function(result) {
                var message_id = null;
                try {
                    message_id = result.rows[0].message_id
                } catch (e) {
                    ;
                }
                on_add_message(message_id);
            });
    },

    save_message: function(from_user_id, to_user_id, message, on_save_message) {
        Chat.logger.info('SAVE MESSAGE:', from_user_id, to_user_id, message);

        // check if like exists
        Chat.get_like(from_user_id, to_user_id, function(is_liked1, is_blocked1) {
            if (! is_liked1) {
                // there is no like
                on_save_message(null, null, Chat.ERROR);
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
                    on_save_message(null, null, Chat.ERROR);
                }
            });
        });

    },

    send_push: function(me_id, user_id, message) {
        Chat.logger.info('PUSH:', Chat.push_queue_name, me_id, user_id, message);
        Chat.rabbit_client.publish(Chat.push_queue_name, {
            'data': {
                'from_user_id': me_id,
                'to_user_id': user_id,
                'message': message
            }
        });
    },

    message: function(data, socket, on_save_message) {
        var key = data['key'];
        var user_id = data['user_id'];
        var message = data['message'];
        var s = Chat.get_socket_info_by_socket_id(socket.id);

        if (! s) {
            // send reauth signal
            on_save_message(null, null, null, null, Chat.NEED_AUTHORIZE);
            return;
        }

        var me_id = s['user_id']

        Chat.get_socket_by_user_id(user_id, function(destination_socket) {
            // save message to db
            Chat.save_message(me_id, user_id, message, function(message_id, destination_message_id, status) {
                // for push notification
                Chat.send_push(me_id, user_id, message);

                // send ack and message in Chat.sockets_info
                on_save_message(destination_socket, message_id, destination_message_id, me_id, status);
            });
        });
    },

    read: function(data, socket, on_read) {
        var s = Chat.get_socket_info_by_socket_id(socket.id);

        if (! s) {
            return;
        }

        var user_id = data['user_id'];
        var message_id = data['message_id'];

        Chat.logger.info('READ:', s.user_id, user_id);

        Chat.pg_query("UPDATE public.messages_new SET is_new = FALSE WHERE me_id = $1::int AND buddy_id = $2::int AND is_new;",
            [s.user_id, user_id],
            function(result) {

                Chat.pg_query("UPDATE public.messages_dialogs SET is_new = FALSE WHERE me_id = $1::int AND buddy_id = $2::int AND is_new;",
                    [s.user_id, user_id],
                    function(result) {

                        Chat.get_socket_by_user_id(user_id, function (to_socket) {
                            if (! to_socket) {
                                return;
                            }

                            on_read(s.user_id, message_id, to_socket);
                        });
                });
        });
    },

    start_writing: function(data, socket, f) {
        var user_id = data['user_id'];
        var my_socket = Chat.get_socket_info_by_socket_id(socket.id);

        Chat.get_socket_by_user_id(user_id, function (to_socket) {
            if (! my_socket || ! to_socket) {
                return;
            }

            f(my_socket.user_id, to_socket);
        });
    },

    stop_writing: function(data, socket, f) {
        var user_id = data['user_id'];
        var my_socket = Chat.get_socket_info_by_socket_id(socket.id);

        Chat.get_socket_by_user_id(user_id, function (to_socket) {
            if (! my_socket || ! to_socket) {
                return;
            }

            f(my_socket.user_id, to_socket);
        });
    },

    disconnect: function(socket) {
        var s = Chat.get_socket_info_by_socket_id(socket.id);
        if (s) {
            Chat.logger.info('DISCONNECT SOCKET:', socket.id, s.user_id);
            delete Chat.sockets_info[socket.id];
            Chat.redis_client.hdel('users_ids_to_socket_ids', s.user_id);
        }
    }
}

Chat.init();

io.adapter(redis_socket_io({ host: 'localhost', port: 6379 }));

io.on('connection', function (socket) {
    //Chat.logger.info(socket.id)
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

    socket.on('read', function (data) {
        Chat.read(data, socket, function(me_id, message_id, destination_socket) {
            Chat.emit(destination_socket, 'read', {
                user_id: me_id,
                message_id: message_id
            });
        });
    });

    socket.on('start_writing', function (data) {
        Chat.start_writing(data, socket, function(me_id, destination_socket) {
            Chat.emit(destination_socket, 'start_writing', {
                user_id: me_id
            });
        });
    });

    socket.on('stop_writing', function (data) {
        Chat.stop_writing(data, socket, function(me_id, destination_socket) {
            Chat.emit(destination_socket, 'stop_writing', {
                user_id: me_id
            });
        });
    });

    socket.on('disconnect', function () {
        Chat.disconnect(socket)
    });
});



