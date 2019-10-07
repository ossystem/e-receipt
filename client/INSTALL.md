Установка и настройка:

1) Создание пользователя mysql (если надо):

1.1) mysql -u root -p
1.2) GRANT ALL PRIVILEGES ON *.* TO 'username'@'localhost' IDENTIFIED BY 'password';

* Далее username - либо созданный выше, либо root

2) Создание БД:

2.1) mysql -u username -p
2.2) CREATE DATABASE ereceipt;

3) Импорт данных из бекапа:

3.1) mysql -u username -p ereceipt < path_to_project/client/ereceipt.sql

3.2) Редактируем настройку подключения к базе (настройка соединения mysql производится в файле path_to_project/client/models/BaseModel.php)

4) Создание виртуального хоста:

4.1) Скопировать файл path_to_project/api/ereceipt.tst в папку /etc/nginx/sites-available
     cp /path_to_project/api/ereceipt.tst /etc/nginx/sites-available 
     
4.2) Отредактировать его в соответствии с путями и названиями папок в текущей системе

* При этом в /etc/nginx/nginx.conf должна присутствовать строчка include "/etc/nginx/sites-enabled/*;" (как в примере - path_to_project/api/nginx.conf)	

4.3) Создаем симлинк на /etc/nginx/sites-available/ereceipt.tst в папке /etc/nginx/sites-enabled :
      sudo ln -s /etc/nginx/sites-available/ereceipt.tst /etc/nginx/sites-enabled/

4.4) Добавляем хосты для api и проекта в файл /etc/hosts:
      127.0.0.1       api.ereceipt.tst
      127.0.0.1       ereceipt.tst
      
4.5) Перезапускаем nginx: 
      sudo service nginx restart      
      
5) Запускаем composer install

6) Редактирование настроек подключения к серверам прооизводится в файлах:
   path_to_project/api/classes/CurlHelper.php,
   path_to_project/client/classes/CurlHelper.php
   
7) sudo chmod -R 777 path_to_project/client/json
   
      