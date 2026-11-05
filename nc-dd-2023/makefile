USER = lyos
CMD = ""

# подключаем другие мейкфайлы
-include makefiles/*.mk

#################################################################
# 				Управление работой приложения				    #
#################################################################
compose-up:
	cd docker && docker-compose --project-name ncdecision up -d

compose-kill:
	cd docker && docker-compose --project-name ncdecision down

composer-install:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		composer install --ignore-platform-reqs --no-interaction --prefer-dist --no-scripts

composer-update:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		composer update --ignore-platform-reqs

composer-dump:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		composer dump-autoload

art:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		php artisan ${CMD}

cache-clear:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		php artisan cache:clear

view-clear:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		php artisan view:clear
