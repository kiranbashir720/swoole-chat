# Swoole Chat API

## Start Server
```bash
php server.php
```

---

## WebSocket — Create User
```
URL: ws://127.0.0.1:9502
```
Message:
```json
{"type":"create_user","username":"ali_raza","email":"ali@gmail.com"}
```

---

## HTTP — Fetch Users

**All Users:**
```
GET http://127.0.0.1:9502/users
```

**Single User:**
```
GET http://127.0.0.1:9502/users/1
```
