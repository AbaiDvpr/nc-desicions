############################################
#            Статика приложения            #
############################################
NODE_IMAGE=node:16
CMD=""

# Сборка статики (однократная)
# make static-build app=admin
# make static-build app=site
# make static-build app=cabinet
static-prod:
	make static-install
	make node CMD=prod:${app}

# Сборка статики (пересборка при изменении)
# make static-watch app=admin
# make static-watch app=site
# make static-watch app=cabinet
static-watch:
	make static-install
	make node CMD=watch:${app}

# Установка nodejs пакетов
static-install:
	docker pull ${NODE_IMAGE}
	make node

# Остановить контейнер с nodejs
static-stop:
	docker stop nodejs

# Добавление и удаление npm пакетов
# make static-package command="add {package name}"
# make static-package command="remove {package name}"
static-package:
	docker pull ${NODE_IMAGE}
	make node CMD="${command}"

# Базовая команда nodejs
node:
	docker run --rm \
		--name=nodejs \
    	--entrypoint yarn \
    	--volume $(CURDIR):/nodejs \
    	--volume /etc/passwd:/etc/passwd:ro \
    	--volume /etc/group:/etc/group:ro \
    	--user ${shell id -u}:$(shell id -g) \
    	--workdir /nodejs/static \
    	--env HOME=/nodejs \
    	--interactive \
    	${NODE_IMAGE} ${CMD}

