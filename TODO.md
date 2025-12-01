# TODO: Fix Dockerfile for Laravel Project

## Steps to Complete
- [ ] Correct the supervisor config file copy from websockets.ini to websockets.conf in Dockerfile
- [ ] Add composer install command after copying application files
- [ ] Add npm install and npm run build commands for frontend assets
- [ ] Set proper permissions for Laravel storage directories
- [ ] Handle .env file (copy from .env.example if exists)
- [ ] Test Docker build and verify application runs correctly
