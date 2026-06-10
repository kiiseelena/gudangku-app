FROM node:18-alpine

# Set working directory
WORKDIR /usr/src/app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install --production

# Copy application source files
COPY . .

# Expose server port
EXPOSE 3000

# Start app
CMD [ "npm", "start" ]
