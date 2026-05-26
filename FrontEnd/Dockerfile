# Estágio 1: Instalação de dependências
FROM node:20-alpine AS deps
RUN apk add --no-cache libc6-compat
WORKDIR /app

# Copiar ficheiros de definição de pacotes
COPY package.json pnpm-lock.yaml* package-lock.json* yarn.lock* ./
RUN \
  if [ -f pnpm-lock.yaml ]; then npm install -g pnpm && pnpm i --no-frozen-lockfile; \
  elif [ -f package-lock.json ]; then npm ci; \
  elif [ -f yarn.lock ]; then yarn --frozen-lockfile; \
  else echo "Lockfile not found." && npm install; \
  fi

# Estágio 2: Desenvolvimento
FROM node:20-alpine AS development
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .
ENV NEXT_TELEMETRY_DISABLED=1
ENV NODE_ENV=development
EXPOSE 3000
CMD ["npm", "run", "dev"]

# Estágio 3: Build de Produção
FROM node:20-alpine AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .

# Desativar telemetria e forçar ambiente de produção
ENV NEXT_TELEMETRY_DISABLED=1
ENV NODE_ENV=production

# Executar build (output standalone configurado em next.config.mjs)
RUN npm run build

# Verificar conteúdo para debug (ajuda a ver se a pasta standalone foi criada)
RUN ls -la .next/

# Estágio 4: Servidor de Produção Otimizado
FROM node:20-alpine AS runner
WORKDIR /app

ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

RUN addgroup --system --gid 1001 nodejs
RUN adduser --system --uid 1001 nextjs

# Copiar recursos estáticos e o servidor standalone
COPY --from=builder /app/public ./public
COPY --from=builder /app/.next/static ./.next/static

# Nota: O standalone copia o servidor e as node_modules necessárias
# Se o standalone não existir, este passo falhará - indicando que o build não gerou o output esperado
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./

USER nextjs
EXPOSE 3000
ENV PORT 3000
ENV HOSTNAME "0.0.0.0"

CMD ["node", "server.js"]
