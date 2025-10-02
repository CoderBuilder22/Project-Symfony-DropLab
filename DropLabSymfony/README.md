# DropLab - Beat Marketplace

DropLab is a modern web application for buying and selling beats, similar to BeatStars. Built with Symfony, it provides a platform for music producers to showcase and sell their beats to artists and content creators.

## Features

- User authentication (registration, login, logout)
- Beat upload and management
- Beat browsing and preview
- Secure payment processing
- Order management
- User profiles
- Modern, responsive design

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL/MariaDB
- Web server (e.g., Apache, Nginx)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/droplab.git
cd droplab
```

2. Install dependencies:
```bash
composer install
```

3. Configure your environment variables by copying `.env` to `.env.local` and updating the database URL:
```bash
cp .env .env.local
```

4. Create the database and run migrations:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Create the upload directories:
```bash
mkdir -p public/uploads/beats public/uploads/covers public/uploads/profiles
```

6. Start the development server:
```bash
symfony server:start
```

## Usage

1. Register a new account at `/register`
2. Log in at `/login`
3. Upload beats at `/beats/new`
4. Browse beats at `/beats`
5. Purchase beats using the integrated payment system

## Directory Structure

- `src/Controller/` - Application controllers
- `src/Entity/` - Doctrine entities
- `src/Form/` - Form types
- `src/Repository/` - Doctrine repositories
- `templates/` - Twig templates
- `public/uploads/` - Uploaded files storage

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Built with Symfony Framework
- Styled with Bootstrap
- Icons from Font Awesome 