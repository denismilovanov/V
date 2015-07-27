package main

import (
    "log"
    "encoding/json"
    "net/http"
    "database/sql"
    _ "github.com/lib/pq"
    "github.com/googollee/go-socket.io"
)

// go get github.com/lib/pq

func authorize(key string) int {
    var userId int
    Vars.db.QueryRow("SELECT user_id FROM users_devices WHERE key = $1;", key).Scan(& userId)
    return userId
}

func messagesDb(userId int) *sql.DB {
    return Vars.db
}

func likesDb(userId int) *sql.DB {
    return Vars.db
}

func addMessage(fromUserId, toUserId int, message string) (int, int) {
    var messageId, counterMessageId int
    var isLiked1, isLiked2, isBlocked2 bool

    likesDb(fromUserId).QueryRow("SELECT count(*) > 0 FROM public.likes WHERE user1_id = $1 AND user2_id = $2;",
        fromUserId, toUserId).Scan(& isLiked1)

    likesDb(fromUserId).QueryRow("SELECT count(*) > 0, max(is_blocked::integer)::boolean FROM public.likes WHERE user1_id = $1 AND user2_id = $2;",
        toUserId, fromUserId).Scan(& isLiked2, & isBlocked2)

    //log.Println(isLiked1, isLiked2, isBlocked2)

    if isLiked1 && isLiked2 {
        // there are 2 likes
        messagesDb(fromUserId).QueryRow("SELECT public.add_message($1, $2, $3, TRUE) AS message_id",
            fromUserId, toUserId, message).Scan(& messageId)
    }

    if messageId != 0 && ! isBlocked2 {
        // there is no block
        messagesDb(toUserId).QueryRow("SELECT public.add_message($1, $2, $3, FALSE) AS message_id",
            toUserId, fromUserId, message).Scan(& counterMessageId)
    }

    return messageId, counterMessageId
}

func getUserId(key string, so socketio.Socket) int {
    var userId = Vars.keys[key]
    if userId == 0 {
        userId = authorize(key)
        if userId != 0 {
            log.Println("authed from db", userId)
            Vars.sockets[userId] = so
            Vars.keys[key] = userId
            Vars.ids[so.Id()] = key
        } else {
        }
    } else {
        log.Println("authed from memory", userId)
    }
    return userId
}

type InMessage struct {
    Key string
    UserId int`json:"user_id"`
    Message string
    Id string
}

type InAuthorize struct {
    Key string
}

type OutAuthorize struct {
    Status int `json:"status"`
}
func (c OutAuthorize) String() string {
    b, _ := json.Marshal(c)
    return string(b)
}

type OutMessage struct {
    UserId int `json:"user_id"`
    Message string `json:"message"`
    MessageId int `json:"message_id"`
}
func (c OutMessage) String() string {
    b, _ := json.Marshal(c)
    return string(b)
}

type OutMessageAck struct {
    Id string `json:"id"`
    Status int `json:"status"`
    MessageId int `json:"message_id"`
}
func (c OutMessageAck) String() string {
    b, _ := json.Marshal(c)
    return string(b)
}

func onInMessage(msg string, so socketio.Socket) {
    var m InMessage
    json.Unmarshal([]byte(msg), &m)
    var key = m.Key

    var userId int = getUserId(key, so)

    var oack OutMessageAck
    oack.Id = m.Id

    if userId > 0 {
        var toUserId = m.UserId
        var message = m.Message
        log.Println(userId, "sends to", toUserId, "message", message)

        var messageId, counterMessageId = addMessage(userId, toUserId, message)

        if messageId != 0 {
            var toSocket = Vars.sockets[toUserId]
            if toSocket != nil && counterMessageId != 0 {
                var o OutMessage
                o.Message = message
                o.MessageId = counterMessageId
                o.UserId = userId
                toSocket.Emit("message", o)
            }
            oack.Status = 1
            oack.MessageId = messageId
        } else {
           oack.Status = 0
        }
    } else {
        oack.Status = 0
    }

    so.Emit("message_ack", oack)
}

func onAuthorize(msg string, so socketio.Socket) {
    var m InAuthorize
    json.Unmarshal([]byte(msg), &m)

    var userId int = getUserId(m.Key, so)

    var o OutAuthorize

    if userId != 0 {
        o.Status = 1
    } else {
        o.Status = 0
    }
    so.Emit("authorize", o)
}

var Vars struct {
    sockets map[int]socketio.Socket
    keys map[string]int
    ids map[string]string
    db *sql.DB
}


func main() {
    server, err := socketio.NewServer(nil)
    if err != nil {
        log.Fatal(err)
    }

    db, err := sql.Open("postgres", "user=vmeste dbname=vmeste")
    if err != nil {
        log.Fatal(err)
    }

    Vars.sockets = map[int]socketio.Socket{}
    Vars.keys = map[string]int{}
    Vars.ids = map[string]string{}
    Vars.db = db

    server.On("connection", func(so socketio.Socket) {
        log.Println("on connection")

        so.On("message", func(msg string) {
            onInMessage(msg, so)
        })

        so.On("authorize", func(msg string) {
            onAuthorize(msg, so)
        })

        so.On("disconnection", func() {
            var id = so.Id()
            var key = Vars.ids[id]
            var userId = Vars.keys[key]
            delete(Vars.ids, id)
            delete(Vars.keys, key)
            delete(Vars.sockets, userId)
            log.Println("on disconnect", userId)
        })
    })
    server.On("error", func(so socketio.Socket, err error) {
        log.Println("error:", err)
    })

    http.Handle("/socket.io/", server)
    http.Handle("/", http.FileServer(http.Dir("/home/denis/work/v/chat/")))
    log.Println("Serving at localhost:5000...")
    log.Fatal(http.ListenAndServe(":5000", nil))
}
