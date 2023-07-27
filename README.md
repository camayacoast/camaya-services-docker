
## Requirements
All the requirements need to be installed to install this app on your local machine

- Docker
- Makefile

## Installing Docker
This app uses Docker. to install docker you need to go to this link [Docker](https://www.docker.com/)
After you install  you need to install Make File to make it easy to configure and install all needed on this app

## Install Make File in Windows
To install Make File in windows you need to open power shell and run this command: Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

After you run the command in power shell just type choco install make to install makefile

to get all the details on how to install make file in windows just go on this website [Makefile Installation Windows Guide ](https://earthly.dev/blog/makefiles-on-windows/)

## Install Make File in Mac Os/Linux
to install Make file in MacOs and in Linux just open the terminal and type sudo apt install make

## Give Permission to Application Folder
Clone this application. after you clone this app you need to go terminal and locate backend/laravel-app and type sudo chmod o+w ./laravel-app/ -R a

## How to install 
To install this app you just need to type docker-compose up -d and docker will download all the packages and drivers that you needed

## Ports that has been used 
All ports that has been used in this app

- 8084 = nginx
- 8025 = react app/frontend
- 9000 = laravel app/backend
- 9001  = Phpmyadmin
- 3600  = Mysql

To see all the details about ports just type docker ps.

## Settings
All settings that has been used or modified in this app was

- make data = to create an table and run database seeder/run php artisan migrate and php artisan db:seed
- make fresh-data = to reset all the tables/run php artisan migrate:fresh
- make composer-update = to install/update all the dependencies of laravel
- make up = to up/run docker-compose file
- make stop = to stop docker-compose file
- make build = to build docker-compose file
- make setup = to run make buid make up and make composer-update at once
- make key-generate = to generate new key


