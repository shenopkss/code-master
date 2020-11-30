# Code-Master 
**一款使用Composer拼装的轻量级代码生成器**
### 依赖
* PHP
* Composer
* Twig
* Eloquent ORM

### 安装
```bash
git clone https://github.com/shenopkss/code-master
cd ~/.code-master
composer install
```
### 配置
```bash
mv .env.example .env
vim .env

```
```php
DB_HOST=localhost
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
TABLES=*     #全表：*，指定表：table1,table2
```

### 使用

```bash
php app.php laravel/start.twig
```

### Vim Plugin
[https://github.com/shenopkss/code-master-vim](https://github.com/shenopkss/code-master-vim)
