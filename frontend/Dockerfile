# Development image: source is bind-mounted by docker-compose.yml.
FROM node:22-alpine

WORKDIR /app

# Installed at build time so the first start is fast.
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

EXPOSE 5173

# The node_modules volume outlives image rebuilds, so sync it with the lockfile on start.
CMD ["sh", "-c", "npm install --no-audit --no-fund && exec npm run dev"]
