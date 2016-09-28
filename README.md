# Code-Master 
**一款使用Composer拼装的轻量级代码生成器**
# 依赖
* PHP
* Composer
* Twig
* Eloquent ORM

# 安装
```bash
git clone https://github.com/shenopkss/code-master ~/.code-master
cd ~/.code-master
composer install
ln -s ~/.code-master/app.php /usr/local/bin/code
```
# 配置
```bash
mv .env.example .env
vim .env
```
```php
DB_HOST=localhost
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

# 使用
## CLI
```bash
code '{% for column in db.tables[0].columns %}
{{column.name}}
{% endfor %}
'
```
## Vim Plugin
[https://github.com/shenopkss/code-master-vim](https://github.com/shenopkss/code-master-vim)
