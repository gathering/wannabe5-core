# Playwright runner for wannabe5 local-stack browser e2e.
# Browsers are preinstalled in the Microsoft image.
FROM mcr.microsoft.com/playwright:v1.55.0-jammy

WORKDIR /e2e

COPY package.json package-lock.json* ./
# Browsers/deps already present in the base image; install project deps only.
RUN npm install --ignore-scripts

COPY . .

ENV BASE_URL=http://wannabe5.gathering.org:3000
ENV CORE_URL=http://wannabe5.gathering.org:8000

CMD ["npx", "playwright", "test"]
