FROM netresearch/akeneo-php:5 as builder

ARG AKENEO_VERSION
ARG AKENEO_DIRECTORY
ARG PACKAGES_DIRECTORY

COPY ./build-akeneo /usr/local/bin
RUN chmod +x /usr/local/bin/build-akeneo
RUN build-akeneo
WORKDIR ${AKENEO_DIRECTORY}

FROM alpine
ARG AKENEO_DIRECTORY
ARG PACKAGES_DIRECTORY
COPY --from=builder /usr/local/bin/build-akeneo /opt/akeneo/bin/build-akeneo
COPY --from=builder ${PACKAGES_DIRECTORY} ${PACKAGES_DIRECTORY}
COPY --from=builder ${AKENEO_DIRECTORY} ${AKENEO_DIRECTORY}