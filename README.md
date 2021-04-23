# wp-cache-db
WordPress数据库缓存,零SQL查询缓存。

- A
最近又重新使用WordPress开发一些东西，这个我是几年前开发。当时不是很满意，所有没有放出来。现在实现了零SQL查询缓存，
放出来大家一起享用。

- B
[![截图](//github.com/midoks/wp-cache-db/blob/master/Screenshot/Screenshot.png)](//github.com/midoks/wp-cache-db/blob/master/Screenshot/Screenshot.png)

* 本地/memcached(本地)[测试可用]
* 其他没有测试,之前的SAE和BAE。

- C
```
开启触发更新,可以加长缓存时间。每次更新都自动删除相应表的数据缓存值!
* 以数据表名为组，每当后台更新数据时，就会删除相应的表所有缓存数据。
```

