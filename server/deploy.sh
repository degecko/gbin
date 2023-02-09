git fetch origin main \
    && git reset --hard origin/main \
    && git pull origin main

bash server/finish-deploy.sh
