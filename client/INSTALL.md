# Установка и настройка

1\. Создание БД:
```
mysql -u <username> -p
CREATE DATABASE <database_name>;
exit;
```

2\. Импорт данных из бекапа:
```
mysql -u <username> -p <database_name> < <path_to_project>/client/ereceipt.sql
```

3\. Редактирование параметров для создания виртуального хоста в файле `<path_to_project>/api/ereceipt.tst`:

3.1 Заменить путь на реальный, куда был склонирован проект (строки 5 и 62):
```
root /var/www/ereceipt.tst/client;
```
и
```
root /var/www/ereceipt.tst/api;
```

3.2 Заменить параметры подключения к БД (строки 20-24):
```
fastcgi_param MYSQL_HOST    localhost;
fastcgi_param MYSQL_PORT    3306;
fastcgi_param MYSQL_DB      ereceipt;
fastcgi_param MYSQL_USER    root;
fastcgi_param MYSQL_PASS    123456;
```

3.3 Заменить URL доступа к API (строка 26):
```
fastcgi_param API_SERVER    http://seleznyov9300.ossystem.ua/;
```
на
```
fastcgi_param API_SERVER    http://api.ereceipt.tst;
```

3.4 Заменить параметры вызова сервиса для шифрования данных (строки 77-78):
```
fastcgi_param CRYPT_SERVER      http://192.168.1.172;
fastcgi_param CRYPT_SERVER_PORT 3100;
```

3.5 Если в системе по умолчанию выбрана версия РНР, отличная от 7.2, то не обходимо дополнительно поменять параметры (строки 13 и 70):
```
fastcgi_pass unix:/var/run/php/php7.2-fpm.sock;
```

4\. Создание виртуального хоста:

4.1 Скопировать файл настроек виртуального хоста в целевую папку `nginx`:
```
sudo cp <path_to_project>/api/ereceipt.tst /etc/nginx/sites-available
```

4.2 Активировать виртуальный хост:
```
sudo ln -s /etc/nginx/sites-available/ereceipt.tst /etc/nginx/sites-enabled/
```

4.3 Добавить данные о виртуальных хостах в список разрешенных для локальной машины:
```
sudo nano /etc/hosts
```
Добавить:
```
127.0.0.1       api.ereceipt.tst
127.0.0.1       ereceipt.tst
```

4.4 Рестарт nginx:
```
sudo service nginx restart
```

Убедиться, что в консоли не появилось информации об ошибке. При необходимости проверить статус `nginx`:
```
sudo service nginx status
```

5\. Установить зависимости Composer:
```
cd <path_to_project>/client
composer install
```

6\. Изменить доступ к папке в проекте следующим образом:
```
sudo chmod -R 777 <path_to_project>/client/json
```