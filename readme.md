# Kanban

A Task management system with time tracking feature!
## Guía rápida para levantar el proyecto

### Requisitos previos
- PHP >= 8.0
- Composer
- Node.js y npm/yarn
- MySQL o MariaDB

### Instalación
1. Clona el repositorio:
	```bash
	git clone https://github.com/EnocVZ/morantkanban-app.git
	cd morantkanban-app
	```
2. Instala dependencias de PHP:
	```bash
	composer install
	```
3. Instala dependencias de Node.js:
	```bash
	yarn install # o npm install
	```
4. Copia el archivo de entorno y configúralo:
	```bash
	cp .env.example .env
	```
	Modifica las variables de entorno según tu configuración de base de datos y correo.
5. Genera la clave de la aplicación:
	```bash
	php artisan key:generate
	```
6. Ejecuta las migraciones y seeders:
	```bash
	php artisan migrate --seed
	```

### Compilar assets
Ejecuta el siguiente comando para compilar los archivos front-end:
```bash
yarn run dev # o npm run dev
```

### Levantar el servidor de desarrollo
```bash
php artisan serve
```
El proyecto estará disponible en http://localhost:8000

### Otros comandos útiles
- Ejecutar pruebas: `php artisan test`
- Compilar assets en modo watch: `yarn watch`

---
¿Problemas? Revisa la configuración de tu entorno y permisos de carpetas (`storage`, `bootstrap/cache`).
