# Local development commands
.PHONY: up down build logs shell wp db-import db-export test

# Start containers
up:
	cd ops/docker && docker compose up -d

# Stop containers
down:
	cd ops/docker && docker compose down

# Build containers
build:
	cd ops/docker && docker compose build

# Show logs
logs:
	cd ops/docker && docker compose logs -f

# Shell into WordPress container
shell:
	cd ops/docker && docker compose exec wordpress bash

# WP-CLI shortcut (uses local wp-cli)
wp:
	@cd bedrock && wp $(filter-out $@,$(MAKECMDGOALS))

# Import database
db-import:
	@read -p "Enter SQL file path: " sqlfile; \
	cd bedrock && wp db import $$sqlfile

# Export database
db-export:
	@mkdir -p ~/projects/amae/backup
	@TIMESTAMP=$$(date +%Y%m%d_%H%M%S); \
	cd bedrock && wp db export /tmp/backup_$$TIMESTAMP.sql && \
	tar -czf ~/projects/amae/backup/db_backup_$$TIMESTAMP.tar.gz -C /tmp backup_$$TIMESTAMP.sql && \
	rm /tmp/backup_$$TIMESTAMP.sql && \
	echo "Database exported to ~/projects/amae/backup/db_backup_$$TIMESTAMP.tar.gz"

# Run tests
test:
	@echo "Running HTTP check..."
	@curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost:8080
	@echo "Checking WordPress REST API..."
	@curl -s http://localhost:8080/wp-json/ | head -n 5

%:
	@: