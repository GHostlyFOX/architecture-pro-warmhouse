# Инструкция по сборке и запуску всего проекта.

## Шаг 1: Предварительные требования
Убедитесь, что на вашем компьютере установлены и работают:

- Docker
- Docker Compose
- PHP и Composer (для генерации lock-файлов)

## Шаг 2: Получение кода
Убедитесь, что у вас последняя версия кода из репозитория, включая все мои последние изменения.

## Шаг 3: Генерация composer.lock файлов (Важный шаг!)
Этот шаг нужно выполнить вручную перед сборкой Docker-образов.
Это обеспечит стабильность зависимостей в PHP-сервисах.

Откройте терминал в корневой папке проекта и выполните следующие команды:

### Для temperature-api:

```
cd apps/temperature-api
composer install
cd ../..
```

### Для switcher-api:

```
cd apps/switcher-api
composer install
cd ../..
```

После выполнения этих команд в папках **apps/temperature-api** и **apps/switcher-api** появятся файлы **composer.lock**.

## Шаг 3: Сборка и запуск всех сервисов
Теперь, когда все готово, можно запустить весь проект.

Перейдите в директорию apps:

```
cd apps
```

Запустите Docker Compose. Команда соберет образы для всех сервисов и запустит их в фоновом режиме:

```
docker compose up --build -d
```

Примечание: в зависимости от вашей системы, может потребоваться sudo перед командой (sudo docker compose up --build -d).

Процесс сборки может занять несколько минут.

## Шаг 4: Проверка работы
После завершения предыдущей команды все сервисы должны быть запущены.

Проверить запущенные контейнеры:

```
docker ps
```

Вы должны увидеть контейнеры для всех сервисов (monolith-nginx, temperature-api-nginx, switcher-api-nginx, kafka, zookeeper и др.).

Просмотреть логи сервиса:

```
# находясь в папке apps
docker compose logs -f <имя_сервиса>
# Например, для монолита:
docker compose logs -f monolith
```

Доступные API и порты:

- Монолит: http://localhost:8080
- Temperature API: http://localhost:8081
- Switcher API: http://localhost:8082


### Swagger-документация:

Вы можете найти статические файлы с документацией в папке docs/swagger/.
Тестовый токен:

Для тестирования API, требующих авторизации (например, switcher-api), используйте Bearer токен: test-token.

### C4 документация

Вы можете найти С4 документацию в папке docs

- [Context](docs/C4_Context.puml)
- [Container](docs/C4_Container.puml)
- [Component](docs/C4_Component.puml)
- [Code](docs/С4_Code.puml)

### ER диаграмма

- [ER диаграмма](docs/ER_diagram.puml)