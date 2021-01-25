# 准备数据库

我们使用 MySQL 数据库保存数据。

如果尚未创建数据库，使用下面命令创建一个：

```mysql
CREATE DATABASE `shortener` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

在该数据库中，创建如下三个表：

```
CREATE TABLE `setting` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `app` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `uuid` varchar(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disabled` tinyint(4) NOT NULL DEFAULT '0',
  `key` varchar(100) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profile` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_UNIQUE` (`uuid`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `url` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(10) unsigned NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `token` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `target` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `disabled` tinyint(4) NOT NULL DEFAULT '0',
  `profile` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_UNIQUE` (`token`),
  KEY `created` (`created`),
  KEY `fk_url_app_id` (`app_id`),
  KEY `expired_at` (`expired_at`),
  KEY `target` (`target`(64)),
  CONSTRAINT `fk_url_app_id` FOREIGN KEY (`app_id`) REFERENCES `app` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

```

我们需要插入一条初始记录以便后续工作，将下面语句 VALUES 部分的 `UUID` 替换为一个 UUID 字符串（形如 `12345678-90ab-cdef-1234-567890abcdef`），将 `KEY` 替换为任意 ASCII 字符串，建议 32 字节长。


```mysql
INSERT INTO `app`
  (`uuid`, `name`, `key`, `profile`)
VALUES
  (
    'UUID',
    'Default Application',
    'KEY',
    '{"version": "1.0.0"}'
  )
;
```
