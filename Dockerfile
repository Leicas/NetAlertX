FROM alpine:3.20 AS builder

ARG INSTALL_DIR=/app

ENV PYTHONUNBUFFERED=1

# Install build dependencies
RUN apk add --no-cache bash python3 python3-dev gcc musl-dev libffi-dev openssl-dev \
    && python -m venv /opt/venv
    

# Enable venv
ENV PATH="/opt/venv/bin:$PATH"

COPY . ${INSTALL_DIR}/


RUN pip install graphene flask netifaces tplink-omada-client pycryptodome requests paho-mqtt scapy cron-converter pytz json2table dhcp-leases pyunifi speedtest-cli chardet python-nmap dnspython librouteros  \
    && bash -c "find ${INSTALL_DIR} -type d -exec chmod 750 {} \;" \
    && bash -c "find ${INSTALL_DIR} -type f -exec chmod 640 {} \;" \
    && bash -c "find ${INSTALL_DIR} -type f \( -name '*.sh' -o -name '*.py'  -o -name 'speedtest-cli' \) -exec chmod 750 {} \;"

# second stage
FROM alpine:3.20 AS runner

ARG INSTALL_DIR=/app

COPY --from=builder /opt/venv /opt/venv

# Enable venv
ENV PATH="/opt/venv/bin:$PATH" 

# default port and listen address
ENV PORT=20211 LISTEN_ADDR=0.0.0.0 

# needed for s6-overlay
ENV S6_CMD_WAIT_FOR_SERVICES_MAXTIME=0

# ❗ IMPORTANT - if you modify this file modify the /install/install_dependecies.sh file as well ❗ 

RUN apk update --no-cache \
    && apk add --no-cache bash zip lsblk gettext-envsubst sudo mtr tzdata s6-overlay \
    && apk add --no-cache curl arp-scan iproute2 iproute2-ss nmap nmap-scripts traceroute nbtscan avahi avahi-tools openrc dbus net-tools net-snmp-tools bind-tools awake ca-certificates  \
    && apk add --no-cache sqlite php83 php83-fpm php83-cgi php83-curl php83-sqlite3 php83-session \
    && apk add --no-cache python3 nginx \
    && apk add --no-cache dcron \
    && ln -s /usr/bin/awake /usr/bin/wakeonlan \
    && bash -c "install -d -m 750 -o nginx -g www-data ${INSTALL_DIR} ${INSTALL_DIR}" \
    && rm -f /etc/nginx/http.d/default.conf

COPY --from=builder --chown=nginx:www-data ${INSTALL_DIR}/ ${INSTALL_DIR}/

# Add crontab file
COPY install/crontab /etc/crontabs/root

# Start all required services
RUN ${INSTALL_DIR}/dockerfiles/pre-setup.sh

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=2 \
  CMD curl -sf -o /dev/null ${LISTEN_ADDR}:${PORT}/api/app_state.json

ENTRYPOINT ["/init"]
