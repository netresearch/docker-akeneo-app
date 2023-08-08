FROM netresearch/akeneo-php:5 as builder

ARG AKENEO_VERSION
ARG AKENEO_DIRECTORY
ARG BOOTSTRAP_VERSION
ARG PACKAGES_DIRECTORY

COPY ./bin/akeneo-project /usr/local/bin
RUN sed -i "s#unset AKENEO_VERSION#AKENEO_VERSION='${AKENEO_VERSION}'#" /usr/local/bin/akeneo-project
RUN sed -i "s#unset BOOTSTRAP_VERSION#BOOTSTRAP_VERSION='${AKENEO_VERSION}'#" /usr/local/bin/akeneo-project
RUN chmod +x /usr/local/bin/akeneo-project
RUN akeneo-project create \
    -i "${AKENEO_DIRECTORY}" \
    -p "${PACKAGES_DIRECTORY}" \
    -b "${BOOTSTRAP_VERSION}"

FROM alpine:3.18.3
ARG AKENEO_DIRECTORY
COPY --from=builder /usr/local/bin/akeneo-project /opt/akeneo-bootstrap/bin/akeneo-project
COPY --from=builder ${AKENEO_DIRECTORY} ${AKENEO_DIRECTORY}
WORKDIR ${AKENEO_DIRECTORY}