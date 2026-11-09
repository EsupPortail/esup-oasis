ARG APP_VERSION=latest
FROM ghcr.io/esupportail/esup-oasis/backend:${APP_VERSION}

# perso apogée
COPY ./personnalisation/config/apogee/* /app/config/apogee/

# perso templates / images / css
COPY ./personnalisation/templates/mail/* /app/templates/mail/
COPY ./personnalisation/templates/Decisions/* /app/templates/Decisions/
COPY ./personnalisation/templates/ServicesFaits/* /app/templates/ServicesFaits/
COPY ./personnalisation/public/css/* /app/public/css/
COPY ./personnalisation/public/images/* /app/public/images/
COPY ./personnalisation/SiScol/* /app/src/Service/SiScol/
COPY ./personnalisation/Signature/* /app/src/Service/Signature/
