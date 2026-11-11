
edit db-listener.service for your machine

- `User=YOUR_USERNAME`
- `WorkingDirectory=/home/YOUR_USERNAME/YOUR_REPO_FOLDER`
- `ExecStart=/usr/bin/php /home/YOUR_USERNAME/YOUR_REPO_FOLDER/database_listener.php`

installing and enabling the service

-sudo cp db-listener.service /etc/systemd/system/
-sudo systemctl daemon-reload  
-sudo systemctl enable --now db-listener

to check if its working

systemctl status db-listener

