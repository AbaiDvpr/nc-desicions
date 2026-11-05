############################################
#            Миграция            #
############################################

migrate:
	make art CMD=migrate

migrate-status:
	make art CMD=migrate:status

migrate-rollback:
	make art CMD="migrate:rollback --step=1"

seed:
	cd docker && docker-compose --project-name ncdecision \
		run desktop \
		php artisan seed
