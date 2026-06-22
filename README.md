# Invite App

Laravel, Inertia, React, and PostgreSQL application for creating events and collecting RSVPs.

## Local development

The project uses the existing Docker environment in `../docker`. From this `src` directory, start it with:

```bash
../docker/up.sh
```

The script builds the PHP container, starts PostgreSQL and Nginx, configures Laravel, and runs the migrations.

- Application: <http://localhost:8080>
- Vite: <http://localhost:5173>
- PostgreSQL: `localhost:5432`

To start Vite inside the running application container:

```bash
docker exec -it invite-app-app-1 npm run dev -- --host 0.0.0.0
```

To stop the environment without deleting PostgreSQL data:

```bash
../docker/down.sh
```

## Checks

```bash
docker exec invite-app-app-1 composer test
docker exec invite-app-app-1 ./vendor/bin/pint --test
docker exec invite-app-app-1 npm run build
```
