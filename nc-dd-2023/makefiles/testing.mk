##################################################
#                 Тестирование                   #
##################################################
group=""

# команда для запуска Unit тестов
test-unit:
	make test group=unit

# команда для запуска Feature тестов
test-feature:
	make test group=feature

# Базовая команда для запуска тестов
test:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		php artisan test --filter=${group} --env=testing

# Команда для создания нового теста
test-create:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		php artisan make:test ${name}
