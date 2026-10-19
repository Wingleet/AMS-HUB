# Frontend Dockerfile - React/Vite Application

# Development stage - runs Vite dev server
FROM node:20-alpine AS development

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source files
COPY . .

# Expose Vite dev server port
EXPOSE 5173

# Start Vite dev server
CMD ["npm", "run", "dev"]

# Build stage
FROM node:20-alpine AS build

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source files
COPY . .

# Accept VITE_* build args so they get baked into the bundle
# Defaut VIDE, volontairement, comme VITE_DEFAULT_SERVER_DB juste en dessous :
# apiClient.ts retombe alors sur window.location.origin et le bundle marche sur
# n'importe quel domaine. Un defaut non vide gagne des que le build-arg est vide,
# car Docker traite un build-arg vide comme non fourni -- c'est ce qui a fige
# http://localhost dans le bundle de staging le 2026-09-05.
ARG VITE_API_URL=
ENV VITE_API_URL=$VITE_API_URL
ARG VITE_DEFAULT_SERVER_DB=
ENV VITE_DEFAULT_SERVER_DB=$VITE_DEFAULT_SERVER_DB

# Build the application
RUN npm run build

# Production stage - serves built app with Caddy
FROM caddy:2-alpine AS production

WORKDIR /app

# Copy built assets from build stage
COPY --from=build /app/dist /app/dist

# Copy Caddy configuration
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80
EXPOSE 443

CMD ["caddy", "run", "--config", "/etc/caddy/Caddyfile"]
