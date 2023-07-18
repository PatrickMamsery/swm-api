# Smart Water Meter API

Application Programming Interface for Smart Water Meters.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Additional Configuration](#additional-configuration)

<!-- Embed screenshot that's in a public folder in the source codes -->
<!-- ![Screenshot](public/images/screenshot.png) -->

## Prerequisites
- PHP (version 7.4 or higher)
- Composer
- Postman/Curl (For Development and Testing Purposes)
- MySQL

## Installation

### Step 1: Working with source codes

Unzip the contents of your file into a desired folder, or clone this repository

```https://github.com/PatrickMamsery/swm-api.git```

### Step 2: Install dependencies

Navigate to the project directory and install the project dependencies by running the following command:

```composer install```


### Step 3: Create a copy of the .env file

Create a copy of the `.env.example` file and name it `.env`. You can use the following command:

```cp .env.example .env```


### Step 4: Generate the application key

Generate the application key by running the following command:

```php artisan key:generate```


### Step 5: Configure the database

Open the `.env` file and update the following lines with your database credentials:

```
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```


### Step 6: Start the development server

You can start the development server by running the following command:

```php artisan serve```


The application will be accessible at `http://localhost:8000` in your web browser.

### Step 7: Setup Laravel Passport for authentication

Install Laravel Passport (for API authentication tokens) using the following command:

```php artisan passport:install```

### Step 8: For a new installation

For a new installation of the project then run the new migrations to setup tables for Passport, namely, OAuth Tables.

```php artisan migrate```

## Testing and Development

Use Postman for testing and development of the API endpoints.

The endpoints are defined in the `api.php` file found by the following procedure

To locate the `api.php` file, you can use the following command in your terminal:

$ cd path/to/your/project/routes
$ open api.php






