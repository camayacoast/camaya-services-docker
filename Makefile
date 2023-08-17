setup:
	@make build
	@make up 
	@make composer-update
	@npm-install
build:
	docker-compose build --no-cache --force-rm
stop:
	docker-compose stop
up:
	docker-compose up -d

composer-update:
	docker exec backend-laravel sh -c "composer update"

composer-dump:
	docker exec backend-laravel sh -c "composer dump autoload"	
npm-install:
	docker exec frontend-react sh -c "npm install"

front:
	docker exec frontend-react sh -c "npm run dev"
optimize: 	
	docker exec backend-services sh -c "php artisan optimize:clear"
	
fresh-data:
	docker exec backend-services sh -c "php artisan migrate:fresh"

data:
	docker exec backend-services sh -c "php artisan migrate"
	docker exec backend-services sh -c "php artisan db:seed"

route-list:
	docker exec backend-services sh -c "php artisan route:list"

migrate:
	docker exec backend-services sh -c "php artisan migrate"

generate-key:
	docker exec backend-services sh -c "php artisan key:generate"