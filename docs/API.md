# API 说明


# 创建短链

`POST /__API__/`

支持以 `application/x-www-form-urlencoded` 或 `application/json` 的方式提交数据，参数如下：

| Name | Data Type | Description |
| :--- | :-------: | :---------- |
| app | string | 应用程序 UUID |
| timestamp | int | 当前时间戳 |
| key | string | 动态密码 |
| target | string | 目标地址 |
| expired_at | string | (optional)过期时间，字符串形式，格式灵活 |
| expired_in | int | (optional)多长时间后过期，单位秒。若同时设置 expired_at，则此项无效 |
| duplicable | bool | (optional)默认为 false，表示尽可能同一个目标返回同一个 token |

接口的客户端将被发放一个 UUID 和一个静态密码 PASSPHRASE，动态密码的生成规则是，将参数中的时间戳转成字符串 TIMESTAMP，将 UUID、TIMESTAMP 和 PASSPHRASE 连接起来，再计算 SHA256 的结果，即：

```
key = sha256(UUID + TIMESTAMP + PASSPHRASE)
```

如果成功，返回形如如下格式的 JSON 信息：

```
{
    "status": 200,
    "message": "OK",
    "data": {
        "token": "wkfYIY7",
        "target": "https://www.google.com/",
        "expired_at": "2020-06-29T10:27:57.651489Z"
    }
}
```

其中 `data.token` 的值为生成的短链 token，将短链服务的 BASE_URI 加在前面即可使用。


# 清理过期短链

`POST /_/task?name=ClearObsoletes`

可以设置定期任务通过 curl 或类似机制定期触发。
