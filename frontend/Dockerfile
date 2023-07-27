FROM node:18.15.0

WORKDIR /app

COPY /frontend/react-app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ADD ./frontend/react-app .


RUN npm i -g vite
RUN npm install --include=dev


ENTRYPOINT ["/entrypoint.sh"]



CMD ["npm", "run", "dev"]